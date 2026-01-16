<?php
/**
 * 3MF Profile Parser
 * Extracts print settings from .3mf files
 *
 * .3mf files are ZIP archives containing:
 * - 3D model data
 * - Print settings (slicing parameters)
 * - Thumbnails
 * - Metadata
 */

/**
 * Parse a .3mf file and extract print settings
 *
 * @param string $filepath Path to the .3mf file
 * @return array Extracted settings or error
 */
function parse3mfFile(string $filepath): array {
    if (!file_exists($filepath)) {
        return ['error' => 'File not found'];
    }

    // .3mf files are ZIP archives
    $zip = new ZipArchive();
    if ($zip->open($filepath) !== true) {
        return ['error' => 'Failed to open .3mf file'];
    }

    $result = [
        'settings' => [],
        'metadata' => [],
        'has_thumbnail' => false,
        'model_count' => 0
    ];

    try {
        // Parse [Content_Types].xml to understand structure
        $contentTypes = $zip->getFromName('[Content_Types].xml');

        // Parse 3D model file (.model)
        $modelXml = null;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $filename = $zip->getNameIndex($i);
            if (preg_match('/\.model$/i', $filename)) {
                $modelXml = $zip->getFromIndex($i);
                break;
            }
        }

        if ($modelXml) {
            $modelData = parseModelXml($modelXml);
            $result = array_merge($result, $modelData);
        }

        // Parse metadata files
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $filename = $zip->getNameIndex($i);

            // Bambu Studio metadata
            if (str_contains($filename, 'Metadata/') && str_contains($filename, '.xml')) {
                $metaXml = $zip->getFromIndex($i);
                $metadata = parseMetadataXml($metaXml);
                $result['metadata'] = array_merge($result['metadata'], $metadata);
            }

            // Bambu Studio config
            if (str_contains($filename, 'Metadata/plate_') && str_contains($filename, '.gcode')) {
                $gcodeSettings = extractGcodeSettings($zip->getFromIndex($i));
                $result['settings'] = array_merge($result['settings'], $gcodeSettings);
            }

            // Check for thumbnail
            if (str_contains($filename, 'thumbnail') || str_contains($filename, 'Metadata/plate_') && str_contains($filename, '.png')) {
                $result['has_thumbnail'] = true;
                $result['thumbnail_file'] = $filename;
            }
        }

        // Parse Bambu Studio specific config if present
        $bambuConfig = $zip->getFromName('Metadata/model_settings.config');
        if ($bambuConfig) {
            $bambuSettings = parseBambuConfig($bambuConfig);
            $result['settings'] = array_merge($result['settings'], $bambuSettings);
        }

        // Try to extract slice info (PrusaSlicer format)
        $sliceInfo = $zip->getFromName('Metadata/Slic3r_PE.config');
        if (!$sliceInfo) {
            $sliceInfo = $zip->getFromName('Metadata/PrusaSlicer.config');
        }
        if ($sliceInfo) {
            $sliceSettings = parseSlicerConfig($sliceInfo);
            $result['settings'] = array_merge($result['settings'], $sliceSettings);
        }

    } catch (Exception $e) {
        $result['error'] = 'Error parsing .3mf: ' . $e->getMessage();
    } finally {
        $zip->close();
    }

    return $result;
}

/**
 * Parse the main .model XML file
 */
function parseModelXml(string $xml): array {
    $result = [
        'model_count' => 0,
        'metadata' => []
    ];

    try {
        $dom = new DOMDocument();
        @$dom->loadXML($xml);

        // Count objects
        $objects = $dom->getElementsByTagName('object');
        $result['model_count'] = $objects->length;

        // Extract metadata
        $metadata = $dom->getElementsByTagName('metadata');
        foreach ($metadata as $meta) {
            $name = $meta->getAttribute('name');
            $value = $meta->nodeValue;
            if ($name) {
                $result['metadata'][$name] = $value;
            }
        }

    } catch (Exception $e) {
        // Ignore parsing errors
    }

    return $result;
}

/**
 * Parse metadata XML files
 */
function parseMetadataXml(string $xml): array {
    $metadata = [];

    try {
        $dom = new DOMDocument();
        @$dom->loadXML($xml);

        $items = $dom->getElementsByTagName('metadata');
        foreach ($items as $item) {
            $name = $item->getAttribute('name');
            $value = $item->nodeValue;
            if ($name) {
                $metadata[$name] = $value;
            }
        }

    } catch (Exception $e) {
        // Ignore parsing errors
    }

    return $metadata;
}

/**
 * Extract settings from G-code comments
 */
function extractGcodeSettings(string $gcode): array {
    $settings = [];

    // G-code comments contain settings
    $lines = explode("\n", $gcode);
    foreach ($lines as $line) {
        $line = trim($line);

        // Look for common settings in comments
        if (preg_match('/^;\s*(\w+)\s*=\s*(.+)/', $line, $matches)) {
            $key = $matches[1];
            $value = trim($matches[2]);

            // Map common settings
            $keyMap = [
                'layer_height' => 'layer_height',
                'first_layer_height' => 'first_layer_height',
                'infill_density' => 'infill_percentage',
                'fill_density' => 'infill_percentage',
                'support_material' => 'supports_required',
                'support_enable' => 'supports_required',
                'nozzle_temperature' => 'nozzle_temp',
                'bed_temperature' => 'bed_temp',
                'print_speed' => 'print_speed',
                'travel_speed' => 'travel_speed'
            ];

            if (isset($keyMap[$key])) {
                $settings[$keyMap[$key]] = parseSettingValue($value);
            }
        }
    }

    return $settings;
}

/**
 * Parse Bambu Studio config format
 */
function parseBambuConfig(string $config): array {
    $settings = [];

    try {
        // Bambu config is often JSON or INI format
        $json = @json_decode($config, true);
        if ($json) {
            // Extract common settings from JSON
            $settingMap = [
                'layer_height' => 'layer_height',
                'sparse_infill_density' => 'infill_percentage',
                'enable_support' => 'supports_required',
                'nozzle_temperature' => 'nozzle_temp',
                'bed_temperature' => 'bed_temp'
            ];

            foreach ($settingMap as $jsonKey => $ourKey) {
                if (isset($json[$jsonKey])) {
                    $settings[$ourKey] = parseSettingValue($json[$jsonKey]);
                }
            }
        } else {
            // Try INI format
            $lines = explode("\n", $config);
            foreach ($lines as $line) {
                if (preg_match('/^(\w+)\s*=\s*(.+)/', $line, $matches)) {
                    $key = $matches[1];
                    $value = trim($matches[2]);
                    $settings[$key] = parseSettingValue($value);
                }
            }
        }
    } catch (Exception $e) {
        // Ignore parsing errors
    }

    return $settings;
}

/**
 * Parse PrusaSlicer/SuperSlicer config format
 */
function parseSlicerConfig(string $config): array {
    $settings = [];

    $lines = explode("\n", $config);
    foreach ($lines as $line) {
        $line = trim($line);

        if (preg_match('/^(\w+)\s*=\s*(.+)/', $line, $matches)) {
            $key = $matches[1];
            $value = trim($matches[2]);

            // Map common slicer settings
            $keyMap = [
                'layer_height' => 'layer_height',
                'first_layer_height' => 'first_layer_height',
                'fill_density' => 'infill_percentage',
                'support_material' => 'supports_required',
                'temperature' => 'nozzle_temp',
                'bed_temperature' => 'bed_temp',
                'perimeter_speed' => 'print_speed'
            ];

            if (isset($keyMap[$key])) {
                $settings[$keyMap[$key]] = parseSettingValue($value);
            } else {
                // Keep all settings
                $settings[$key] = parseSettingValue($value);
            }
        }
    }

    return $settings;
}

/**
 * Parse a setting value and convert to appropriate type
 */
function parseSettingValue($value) {
    // Remove units
    $value = preg_replace('/\s*(mm|%|°C|C)$/', '', $value);

    // Convert percentage (e.g., "20%" to 20)
    if (str_contains($value, '%')) {
        return (int)str_replace('%', '', $value);
    }

    // Boolean values
    if (in_array(strtolower($value), ['true', 'yes', '1', 'on'])) {
        return true;
    }
    if (in_array(strtolower($value), ['false', 'no', '0', 'off'])) {
        return false;
    }

    // Numeric values
    if (is_numeric($value)) {
        return str_contains($value, '.') ? (float)$value : (int)$value;
    }

    return $value;
}

/**
 * Extract a thumbnail image from a .3mf file
 *
 * @param string $filepath Path to the .3mf file
 * @param string $outputPath Where to save the thumbnail
 * @return bool Success
 */
function extract3mfThumbnail(string $filepath, string $outputPath): bool {
    $zip = new ZipArchive();
    if ($zip->open($filepath) !== true) {
        return false;
    }

    $success = false;
    try {
        // Look for thumbnail files
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $filename = $zip->getNameIndex($i);

            // Bambu Studio thumbnails
            if (preg_match('/Metadata\/plate_\d+\.png$/i', $filename)) {
                $content = $zip->getFromIndex($i);
                if ($content) {
                    file_put_contents($outputPath, $content);
                    $success = true;
                    break;
                }
            }

            // Generic thumbnails
            if (preg_match('/thumbnail/i', $filename) && preg_match('/\.(png|jpg|jpeg)$/i', $filename)) {
                $content = $zip->getFromIndex($i);
                if ($content) {
                    file_put_contents($outputPath, $content);
                    $success = true;
                    break;
                }
            }
        }
    } catch (Exception $e) {
        $success = false;
    } finally {
        $zip->close();
    }

    return $success;
}

/**
 * Validate that a file is a valid .3mf file
 *
 * @param string $filepath Path to the file
 * @return array ['valid' => bool, 'error' => string]
 */
function validate3mfFile(string $filepath): array {
    // Check file exists
    if (!file_exists($filepath)) {
        return ['valid' => false, 'error' => 'File not found'];
    }

    // Check file extension
    if (!preg_match('/\.3mf$/i', $filepath)) {
        return ['valid' => false, 'error' => 'File must have .3mf extension'];
    }

    // Check it's a valid ZIP
    $zip = new ZipArchive();
    if ($zip->open($filepath) !== true) {
        return ['valid' => false, 'error' => 'Invalid .3mf file (not a valid ZIP archive)'];
    }

    // Check for required files
    $hasContentTypes = false;
    $hasModel = false;

    for ($i = 0; $i < $zip->numFiles; $i++) {
        $filename = $zip->getNameIndex($i);

        if ($filename === '[Content_Types].xml') {
            $hasContentTypes = true;
        }
        if (preg_match('/\.model$/i', $filename)) {
            $hasModel = true;
        }
    }

    $zip->close();

    if (!$hasContentTypes) {
        return ['valid' => false, 'error' => 'Invalid .3mf file (missing [Content_Types].xml)'];
    }

    if (!$hasModel) {
        return ['valid' => false, 'error' => 'Invalid .3mf file (missing .model file)'];
    }

    return ['valid' => true];
}

/**
 * Get a summary of print settings suitable for quick display
 *
 * @param array $settings Full settings array
 * @return array Simplified settings for display
 */
function getProfileSummary(array $settings): array {
    $summary = [];

    // Layer height
    if (isset($settings['layer_height'])) {
        $summary['layer_height'] = $settings['layer_height'];
    }

    // Infill
    if (isset($settings['infill_percentage'])) {
        $summary['infill'] = $settings['infill_percentage'] . '%';
    }

    // Supports
    if (isset($settings['supports_required'])) {
        $summary['supports'] = $settings['supports_required'] ? 'Yes' : 'No';
    }

    // Temperatures
    if (isset($settings['nozzle_temp'])) {
        $summary['nozzle_temp'] = $settings['nozzle_temp'] . '°C';
    }
    if (isset($settings['bed_temp'])) {
        $summary['bed_temp'] = $settings['bed_temp'] . '°C';
    }

    // Speed
    if (isset($settings['print_speed'])) {
        $summary['speed'] = $settings['print_speed'] . ' mm/s';
    }

    return $summary;
}
?>
