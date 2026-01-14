<?php
/**
 * FEC STL Vault - JSON to MySQL Migration Script
 * Migrates existing JSON data files to MySQL database
 * Run this AFTER setup_database.php
 */

// Prevent direct access in production
if (!defined('MIGRATION_MODE')) {
    define('MIGRATION_MODE', true);
}

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db_config.php';

// JSON file paths
define('OLD_USERS_FILE', DATA_DIR . 'users.json');
define('OLD_MODELS_FILE', DATA_DIR . 'models.json');
define('OLD_CATEGORIES_FILE', DATA_DIR . 'categories.json');

echo "FEC STL Vault - JSON to MySQL Migration\n";
echo "========================================\n\n";

// Check if JSON files exist
$hasData = false;
if (file_exists(OLD_USERS_FILE)) {
    echo "✓ Found users.json\n";
    $hasData = true;
}
if (file_exists(OLD_MODELS_FILE)) {
    echo "✓ Found models.json\n";
    $hasData = true;
}
if (file_exists(OLD_CATEGORIES_FILE)) {
    echo "✓ Found categories.json\n";
    $hasData = true;
}

if (!$hasData) {
    die("\n❌ No JSON data files found in " . DATA_DIR . "\nNothing to migrate.\n");
}

echo "\nConnecting to database...\n";
$conn = getDbConnection();
echo "✓ Connected successfully!\n\n";

// Helper function to read JSON
function readJsonFile($file) {
    if (!file_exists($file)) return [];
    $content = file_get_contents($file);
    return json_decode($content, true) ?? [];
}

// Migrate Categories
echo "Migrating categories...\n";
$categories = readJsonFile(OLD_CATEGORIES_FILE);
$categoryCount = 0;

if (!empty($categories)) {
    $stmt = $conn->prepare("INSERT INTO categories (id, name, icon, description, count) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE name=VALUES(name), icon=VALUES(icon), description=VALUES(description), count=VALUES(count)");

    foreach ($categories as $cat) {
        $stmt->bind_param(
            "ssssi",
            $cat['id'],
            $cat['name'],
            $cat['icon'],
            $cat['description'] ?? '',
            $cat['count'] ?? 0
        );
        if ($stmt->execute()) {
            $categoryCount++;
            echo "  ✓ {$cat['name']}\n";
        }
    }
    $stmt->close();
}
echo "✓ Migrated $categoryCount categories\n\n";

// Migrate Users
echo "Migrating users...\n";
$users = readJsonFile(OLD_USERS_FILE);
$userCount = 0;

if (!empty($users)) {
    $stmt = $conn->prepare("INSERT INTO users (id, username, email, password, is_admin, avatar, bio, location, created_at, model_count, download_count) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE username=VALUES(username), email=VALUES(email)");

    foreach ($users as $user) {
        $isAdmin = $user['is_admin'] ?? false;
        $createdAt = $user['created_at'] ?? date('Y-m-d H:i:s');

        $stmt->bind_param(
            "ssssisssiii",
            $user['id'],
            $user['username'],
            $user['email'],
            $user['password'],
            $isAdmin,
            $user['avatar'],
            $user['bio'] ?? '',
            $user['location'] ?? '',
            $createdAt,
            $user['model_count'] ?? 0,
            $user['download_count'] ?? 0
        );

        if ($stmt->execute()) {
            $userCount++;
            echo "  ✓ {$user['username']}\n";

            // Migrate user favorites
            if (!empty($user['favorites'])) {
                $favStmt = $conn->prepare("INSERT IGNORE INTO favorites (user_id, model_id) VALUES (?, ?)");
                foreach ($user['favorites'] as $modelId) {
                    $favStmt->bind_param("ss", $user['id'], $modelId);
                    $favStmt->execute();
                }
                $favStmt->close();
            }
        }
    }
    $stmt->close();
}
echo "✓ Migrated $userCount users\n\n";

// Migrate Models
echo "Migrating models...\n";
$models = readJsonFile(OLD_MODELS_FILE);
$modelCount = 0;

if (!empty($models)) {
    $modelStmt = $conn->prepare("
        INSERT INTO models
        (id, user_id, title, description, category, tags, filename, filesize, file_count,
         thumbnail, photo, primary_display, license, print_settings,
         downloads, likes, views, featured, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE title=VALUES(title), description=VALUES(description)
    ");

    $fileStmt = $conn->prepare("
        INSERT INTO model_files
        (model_id, filename, filesize, original_name, extension, has_color, file_order)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    $photoStmt = $conn->prepare("
        INSERT INTO model_photos
        (model_id, filename, is_primary, photo_order)
        VALUES (?, ?, ?, ?)
    ");

    foreach ($models as $model) {
        // Prepare data
        $tagsJson = json_encode($model['tags'] ?? []);
        $printSettingsJson = json_encode($model['print_settings'] ?? []);

        $fileCount = $model['file_count'] ?? count($model['files'] ?? []);
        if ($fileCount == 0 && !empty($model['filename'])) $fileCount = 1;

        $createdAt = $model['created_at'] ?? date('Y-m-d H:i:s');
        $updatedAt = $model['updated_at'] ?? $createdAt;

        // Insert model
        $modelStmt->bind_param(
            "sssssssississiiisss",
            $model['id'],
            $model['user_id'],
            $model['title'],
            $model['description'] ?? '',
            $model['category'],
            $tagsJson,
            $model['filename'] ?? '',
            $model['filesize'] ?? 0,
            $fileCount,
            $model['thumbnail'],
            $model['photo'],
            $model['primary_display'] ?? 'auto',
            $model['license'] ?? 'CC BY-NC',
            $printSettingsJson,
            $model['downloads'] ?? 0,
            $model['likes'] ?? 0,
            $model['views'] ?? 0,
            $model['featured'] ?? false,
            $createdAt,
            $updatedAt
        );

        if ($modelStmt->execute()) {
            $modelCount++;
            echo "  ✓ {$model['title']}\n";

            // Migrate files
            if (!empty($model['files'])) {
                foreach ($model['files'] as $index => $file) {
                    $hasColor = $file['has_color'] ?? false;
                    $extension = $file['extension'] ?? pathinfo($file['filename'], PATHINFO_EXTENSION);

                    $fileStmt->bind_param(
                        "ssissii",
                        $model['id'],
                        $file['filename'],
                        $file['filesize'],
                        $file['original_name'] ?? $file['filename'],
                        $extension,
                        $hasColor,
                        $index
                    );
                    $fileStmt->execute();
                }
            } elseif (!empty($model['filename'])) {
                // Legacy single file
                $extension = pathinfo($model['filename'], PATHINFO_EXTENSION);
                $fileStmt->bind_param(
                    "ssissii",
                    $model['id'],
                    $model['filename'],
                    $model['filesize'] ?? 0,
                    $model['filename'],
                    $extension,
                    false,
                    0
                );
                $fileStmt->execute();
            }

            // Migrate photos
            if (!empty($model['photos'])) {
                foreach ($model['photos'] as $index => $photo) {
                    $isPrimary = ($index === 0);
                    $photoStmt->bind_param("ssii", $model['id'], $photo, $isPrimary, $index);
                    $photoStmt->execute();
                }
            } elseif (!empty($model['photo'])) {
                // Legacy single photo
                $photoStmt->bind_param("ssii", $model['id'], $model['photo'], true, 0);
                $photoStmt->execute();
            }
        }
    }

    $modelStmt->close();
    $fileStmt->close();
    $photoStmt->close();
}
echo "✓ Migrated $modelCount models\n\n";

// Update category counts
echo "Recalculating category counts...\n";
$conn->query("
    UPDATE categories c
    SET c.count = (
        SELECT COUNT(*)
        FROM models m
        WHERE m.category = c.id
    )
");
echo "✓ Category counts updated\n\n";

// Show statistics
echo "========================================\n";
echo "Migration complete!\n";
echo "========================================\n\n";

$totalUsers = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$totalModels = $conn->query("SELECT COUNT(*) as count FROM models")->fetch_assoc()['count'];
$totalCategories = $conn->query("SELECT COUNT(*) as count FROM categories")->fetch_assoc()['count'];
$totalFiles = $conn->query("SELECT COUNT(*) as count FROM model_files")->fetch_assoc()['count'];
$totalPhotos = $conn->query("SELECT COUNT(*) as count FROM model_photos")->fetch_assoc()['count'];
$totalFavorites = $conn->query("SELECT COUNT(*) as count FROM favorites")->fetch_assoc()['count'];

echo "Database Statistics:\n";
echo "  Users: $totalUsers\n";
echo "  Models: $totalModels\n";
echo "  Categories: $totalCategories\n";
echo "  Model Files: $totalFiles\n";
echo "  Model Photos: $totalPhotos\n";
echo "  Favorites: $totalFavorites\n\n";

echo "Next steps:\n";
echo "1. Test the application thoroughly\n";
echo "2. Backup the JSON files (they are no longer used)\n";
echo "3. You can delete or archive the old JSON files after verifying everything works\n";
echo "4. Consider removing the data/ directory after confirming migration success\n\n";

$conn->close();
?>
