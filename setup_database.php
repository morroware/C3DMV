<?php
/**
 * Community 3D Model Vault - Database Setup Script
 * Run this once to create MySQL database schema
 * Compatible with cPanel shared hosting
 */

// Prevent direct access in production
if (!defined('SETUP_MODE')) {
    define('SETUP_MODE', true);
}

// Check if running from CLI or browser
$isCli = php_sapi_name() === 'cli';

// Function to output message (works in both CLI and browser)
function outputMessage($message, $isError = false) {
    global $isCli;
    if ($isCli) {
        echo $message . "\n";
    } else {
        $class = $isError ? 'error' : 'success';
        echo "<div class='message $class'>" . nl2br(htmlspecialchars($message)) . "</div>";
    }
}

// Function to show setup instruction page
function showSetupPage($error) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Community 3D Model Vault - Database Setup</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
                background: linear-gradient(135deg, #0a0a0f 0%, #1a1a2e 100%);
                color: #e0e0e0;
                padding: 20px;
                min-height: 100vh;
            }
            .container {
                max-width: 800px;
                margin: 40px auto;
                background: rgba(26, 26, 46, 0.8);
                border: 2px solid rgba(0, 240, 255, 0.3);
                border-radius: 12px;
                padding: 40px;
                box-shadow: 0 20px 60px rgba(0, 240, 255, 0.2);
            }
            h1 {
                font-family: 'Courier New', monospace;
                color: #00f0ff;
                margin-bottom: 10px;
                font-size: 2em;
            }
            h2 {
                color: #00f0ff;
                margin: 30px 0 15px;
                font-size: 1.3em;
            }
            .subtitle {
                color: #888;
                margin-bottom: 30px;
            }
            .message {
                padding: 15px 20px;
                margin: 15px 0;
                border-radius: 8px;
                border-left: 4px solid;
                background: rgba(0, 0, 0, 0.3);
            }
            .message.error {
                border-color: #ff3366;
                color: #ff3366;
            }
            .message.info {
                border-color: #00f0ff;
                color: #00f0ff;
            }
            code {
                background: rgba(0, 0, 0, 0.5);
                padding: 2px 6px;
                border-radius: 4px;
                color: #ff00aa;
                font-family: 'Courier New', monospace;
            }
            .btn {
                display: inline-block;
                padding: 12px 24px;
                background: linear-gradient(135deg, #00f0ff, #00aacc);
                color: #0a0a0f;
                text-decoration: none;
                border-radius: 6px;
                font-weight: bold;
                margin: 10px 10px 10px 0;
                transition: transform 0.2s;
            }
            .btn:hover {
                transform: translateY(-2px);
            }
            ol, ul {
                margin: 15px 0 15px 25px;
                line-height: 1.8;
            }
            li {
                margin: 8px 0;
            }
            pre {
                background: rgba(0, 0, 0, 0.5);
                padding: 15px;
                border-radius: 8px;
                overflow-x: auto;
                border: 1px solid rgba(0, 240, 255, 0.2);
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>⚙️ Database Setup Required</h1>
            <p class="subtitle">MySQL Configuration Needed</p>

            <div class="message error">
                <strong>❌ <?php echo htmlspecialchars($error); ?></strong>
            </div>

            <div class="message info">
                <strong>ℹ️ Your site is currently running in JSON mode</strong><br>
                The application works fine with JSON files, but MySQL provides better performance and scalability.
            </div>

            <h2>📋 Setup Steps</h2>
            <ol>
                <li><strong>Create MySQL Database in cPanel:</strong>
                    <ul>
                        <li>Go to cPanel → MySQL Databases</li>
                        <li>Create a new database (e.g., <code>fecvault_db</code>)</li>
                        <li>Create a new user (e.g., <code>fecvault_user</code>)</li>
                        <li>Assign user to database with ALL PRIVILEGES</li>
                    </ul>
                </li>
                <li><strong>Update Configuration File:</strong>
                    <p>Edit <code>includes/db_config.php</code> with your credentials:</p>
                    <pre>define('DB_HOST', 'localhost');
define('DB_NAME', 'your_cpanel_user_fecvault');
define('DB_USER', 'your_cpanel_user_dbuser');
define('DB_PASS', 'your_secure_password');</pre>
                    <p><strong>Note:</strong> In cPanel, database names and users are prefixed with your cPanel username.</p>
                </li>
                <li><strong>Run This Setup Script Again:</strong>
                    <p>After updating the configuration, refresh this page to create the database tables.</p>
                </li>
            </ol>

            <h2>🔗 Quick Links</h2>
            <a href="DATABASE_MIGRATION.md" class="btn">📖 Full Setup Guide</a>
            <a href="index.php" class="btn">🏠 Back to Site (JSON Mode)</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Load configuration
if (!file_exists(__DIR__ . '/includes/db_config.php')) {
    if (!$isCli) {
        showSetupPage("Database configuration file not found. Please copy db_config.sample.php to db_config.php first.");
    } else {
        die("ERROR: includes/db_config.php not found.\nPlease copy includes/db_config.sample.php to includes/db_config.php and update with your database credentials.\n");
    }
}

require_once __DIR__ . '/includes/db_config.php';

// Check if credentials are configured
if (!defined('DB_PASS') || DB_PASS === 'your_password_here') {
    if (!$isCli) {
        showSetupPage("Database credentials not configured. Please update includes/db_config.php with your MySQL database credentials.");
    } else {
        die("ERROR: Database credentials not configured.\nPlease edit includes/db_config.php and update with your actual database credentials.\n");
    }
}

// HTML header for browser
if (!$isCli) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Community 3D Model Vault - Database Setup</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
                background: linear-gradient(135deg, #0a0a0f 0%, #1a1a2e 100%);
                color: #e0e0e0;
                padding: 20px;
                min-height: 100vh;
            }
            .container {
                max-width: 800px;
                margin: 40px auto;
                background: rgba(26, 26, 46, 0.8);
                border: 2px solid rgba(0, 240, 255, 0.3);
                border-radius: 12px;
                padding: 40px;
                box-shadow: 0 20px 60px rgba(0, 240, 255, 0.2);
            }
            h1 {
                font-family: 'Courier New', monospace;
                color: #00f0ff;
                margin-bottom: 10px;
                font-size: 2em;
            }
            h2 {
                color: #00f0ff;
                margin: 30px 0 15px;
                font-size: 1.3em;
            }
            .subtitle {
                color: #888;
                margin-bottom: 30px;
            }
            .message {
                padding: 15px 20px;
                margin: 15px 0;
                border-radius: 8px;
                border-left: 4px solid;
                background: rgba(0, 0, 0, 0.3);
            }
            .message.success {
                border-color: #00ff88;
                color: #00ff88;
            }
            .message.error {
                border-color: #ff3366;
                color: #ff3366;
            }
            .message.info {
                border-color: #00f0ff;
                color: #00f0ff;
            }
            .stats {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
                gap: 15px;
                margin: 20px 0;
            }
            .stat {
                background: rgba(0, 240, 255, 0.1);
                padding: 15px;
                border-radius: 8px;
                text-align: center;
                border: 1px solid rgba(0, 240, 255, 0.3);
            }
            .stat-value {
                font-size: 2em;
                font-weight: bold;
                color: #00f0ff;
            }
            .stat-label {
                color: #888;
                margin-top: 5px;
            }
            code {
                background: rgba(0, 0, 0, 0.5);
                padding: 2px 6px;
                border-radius: 4px;
                color: #ff00aa;
                font-family: 'Courier New', monospace;
            }
            .btn {
                display: inline-block;
                padding: 12px 24px;
                background: linear-gradient(135deg, #00f0ff, #00aacc);
                color: #0a0a0f;
                text-decoration: none;
                border-radius: 6px;
                font-weight: bold;
                margin-top: 20px;
                transition: transform 0.2s;
            }
            .btn:hover {
                transform: translateY(-2px);
            }
            ol, ul {
                margin: 15px 0 15px 25px;
                line-height: 1.8;
            }
            li {
                margin: 8px 0;
            }
            .checkmark {
                color: #00ff88;
                margin-right: 8px;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>⚙️ Community 3D Model Vault Database Setup</h1>
            <p class="subtitle">MySQL Database Installation</p>
    <?php
}

// Try to connect
try {
    @$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    if ($conn->connect_error) {
        if (!$isCli) {
            ?>
            <div class="message error">
                <strong>❌ Connection Failed</strong><br>
                <?php echo htmlspecialchars($conn->connect_error); ?>
            </div>
            <h2>Troubleshooting Steps:</h2>
            <ol>
                <li>Verify your database exists in cPanel MySQL Databases</li>
                <li>Confirm the database user is assigned to the database</li>
                <li>Check credentials in <code>includes/db_config.php</code></li>
                <li>Ensure database name format: <code>cpaneluser_dbname</code></li>
                <li>Ensure username format: <code>cpaneluser_username</code></li>
            </ol>
            <a href="DATABASE_MIGRATION.md" class="btn">📖 View Setup Guide</a>
            </div>
            </body>
            </html>
            <?php
            exit;
        } else {
            die("Connection failed: " . $conn->connect_error . "\n\nPlease check your database credentials in includes/db_config.php\n");
        }
    }
} catch (Exception $e) {
    if (!$isCli) {
        ?>
        <div class="message error">
            <strong>❌ Connection Error</strong><br>
            <?php echo htmlspecialchars($e->getMessage()); ?>
        </div>
        </div>
        </body>
        </html>
        <?php
        exit;
    } else {
        die("Connection error: " . $e->getMessage() . "\n");
    }
}

outputMessage("✓ Connected to database successfully!", false);

// Check for reset parameter (clear database before setup)
$resetDatabase = isset($_GET['reset']) || (isset($argv) && in_array('--reset', $argv));

if ($resetDatabase) {
    outputMessage("🗑️ Clearing existing database tables...", false);

    // Drop tables in reverse order of dependencies
    $dropTables = [
        'profile_ratings',
        'user_filaments',
        'user_printers',
        'print_profiles',
        'filaments',
        'printers',
        'favorites',
        'model_photos',
        'model_files',
        'models',
        'categories',
        'users',
        'settings'
    ];

    foreach ($dropTables as $table) {
        $conn->query("DROP TABLE IF EXISTS `$table`");
    }

    outputMessage("✓ Existing tables cleared!", false);
}

// SQL for creating tables
$sql = "
-- Users table
CREATE TABLE IF NOT EXISTS users (
    id VARCHAR(32) PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    is_admin BOOLEAN DEFAULT FALSE,
    avatar VARCHAR(255) NULL,
    bio TEXT NULL,
    location VARCHAR(255) NULL,
    website VARCHAR(255) NULL,
    twitter VARCHAR(100) NULL,
    github VARCHAR(100) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    model_count INT DEFAULT 0,
    download_count INT DEFAULT 0,
    INDEX idx_username (username),
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Categories table
CREATE TABLE IF NOT EXISTS categories (
    id VARCHAR(50) PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    icon VARCHAR(50) DEFAULT 'fa-cube',
    description TEXT NULL,
    count INT DEFAULT 0,
    INDEX idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Models table
CREATE TABLE IF NOT EXISTS models (
    id VARCHAR(32) PRIMARY KEY,
    user_id VARCHAR(32) NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    category VARCHAR(50) NOT NULL,
    tags JSON NULL,
    filename VARCHAR(255) NOT NULL,
    filesize BIGINT DEFAULT 0,
    file_count INT DEFAULT 1,
    thumbnail VARCHAR(255) NULL,
    photo VARCHAR(255) NULL,
    primary_display VARCHAR(20) DEFAULT 'auto',
    license VARCHAR(50) DEFAULT 'CC BY-NC',
    print_settings JSON NULL,
    downloads INT DEFAULT 0,
    likes INT DEFAULT 0,
    views INT DEFAULT 0,
    featured BOOLEAN DEFAULT FALSE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category) REFERENCES categories(id) ON DELETE RESTRICT,
    INDEX idx_user_id (user_id),
    INDEX idx_category (category),
    INDEX idx_created_at (created_at),
    INDEX idx_downloads (downloads),
    INDEX idx_likes (likes),
    INDEX idx_featured (featured),
    FULLTEXT idx_search (title, description)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Model files table (for multiple files per model)
CREATE TABLE IF NOT EXISTS model_files (
    id INT AUTO_INCREMENT PRIMARY KEY,
    model_id VARCHAR(32) NOT NULL,
    filename VARCHAR(255) NOT NULL,
    filesize BIGINT NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    extension VARCHAR(10) NOT NULL,
    has_color BOOLEAN DEFAULT FALSE,
    file_order INT DEFAULT 0,
    FOREIGN KEY (model_id) REFERENCES models(id) ON DELETE CASCADE,
    INDEX idx_model_id (model_id),
    UNIQUE KEY unique_model_file (model_id, filename),
    UNIQUE KEY unique_model_file_order (model_id, file_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Model photos table (for multiple photos per model)
CREATE TABLE IF NOT EXISTS model_photos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    model_id VARCHAR(32) NOT NULL,
    filename VARCHAR(255) NOT NULL,
    is_primary BOOLEAN DEFAULT FALSE,
    photo_order INT DEFAULT 0,
    FOREIGN KEY (model_id) REFERENCES models(id) ON DELETE CASCADE,
    INDEX idx_model_id (model_id),
    UNIQUE KEY unique_model_photo (model_id, filename),
    UNIQUE KEY unique_model_photo_order (model_id, photo_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Favorites table (many-to-many relationship)
CREATE TABLE IF NOT EXISTS favorites (
    user_id VARCHAR(32) NOT NULL,
    model_id VARCHAR(32) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, model_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (model_id) REFERENCES models(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_model_id (model_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Printers table
CREATE TABLE IF NOT EXISTS printers (
    id VARCHAR(32) PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    manufacturer VARCHAR(100) NULL,
    build_volume_x INT NULL,
    build_volume_y INT NULL,
    build_volume_z INT NULL,
    nozzle_diameter DECIMAL(4,2) DEFAULT 0.4,
    description TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_manufacturer (manufacturer),
    INDEX idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Filaments table
CREATE TABLE IF NOT EXISTS filaments (
    id VARCHAR(32) PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    manufacturer VARCHAR(100) NULL,
    color VARCHAR(50) NULL,
    material_type VARCHAR(50) NOT NULL,
    description TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_material_type (material_type),
    INDEX idx_manufacturer (manufacturer)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Print profiles table
CREATE TABLE IF NOT EXISTS print_profiles (
    id VARCHAR(32) PRIMARY KEY,
    model_id VARCHAR(32) NOT NULL,
    user_id VARCHAR(32) NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    filename VARCHAR(255) NOT NULL,
    filesize BIGINT NOT NULL,
    settings JSON NULL,
    printer_id VARCHAR(32) NULL,
    filament_id VARCHAR(32) NULL,
    compatible_printers JSON NULL,
    compatible_materials JSON NULL,
    layer_height DECIMAL(4,2) NULL,
    infill_percentage INT NULL,
    supports_required BOOLEAN DEFAULT FALSE,
    print_time_minutes INT NULL,
    material_used_grams INT NULL,
    verified BOOLEAN DEFAULT FALSE,
    verification_method VARCHAR(50) NULL,
    quality_rating DECIMAL(3,2) DEFAULT 0,
    rating_count INT DEFAULT 0,
    successful_prints INT DEFAULT 0,
    failed_prints INT DEFAULT 0,
    downloads INT DEFAULT 0,
    views INT DEFAULT 0,
    featured BOOLEAN DEFAULT FALSE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (model_id) REFERENCES models(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (printer_id) REFERENCES printers(id) ON DELETE SET NULL,
    FOREIGN KEY (filament_id) REFERENCES filaments(id) ON DELETE SET NULL,
    INDEX idx_model_id (model_id),
    INDEX idx_user_id (user_id),
    INDEX idx_printer_id (printer_id),
    INDEX idx_verified (verified),
    INDEX idx_quality_rating (quality_rating),
    INDEX idx_created_at (created_at),
    INDEX idx_featured (featured)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Profile ratings table
CREATE TABLE IF NOT EXISTS profile_ratings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    profile_id VARCHAR(32) NOT NULL,
    user_id VARCHAR(32) NOT NULL,
    rating INT NOT NULL,
    print_successful BOOLEAN NULL,
    comment TEXT NULL,
    printer_used VARCHAR(32) NULL,
    filament_used VARCHAR(32) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (profile_id) REFERENCES print_profiles(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_profile_rating (user_id, profile_id),
    INDEX idx_profile_id (profile_id),
    INDEX idx_rating (rating)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User printers (user's printer garage)
CREATE TABLE IF NOT EXISTS user_printers (
    user_id VARCHAR(32) NOT NULL,
    printer_id VARCHAR(32) NOT NULL,
    nickname VARCHAR(100) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, printer_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (printer_id) REFERENCES printers(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User filaments (user's filament inventory)
CREATE TABLE IF NOT EXISTS user_filaments (
    user_id VARCHAR(32) NOT NULL,
    filament_id VARCHAR(32) NOT NULL,
    quantity INT DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, filament_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (filament_id) REFERENCES filaments(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

// Execute multi-query
if ($conn->multi_query($sql)) {
    do {
        // Consume all results
        if ($result = $conn->store_result()) {
            $result->free();
        }
    } while ($conn->next_result());

    echo "✓ Database tables created successfully!\n\n";
} else {
    die("Error creating tables: " . $conn->error);
}

// Run migrations for existing databases (adds new columns if they don't exist)
$migrations = [
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS website VARCHAR(255) NULL AFTER location",
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS twitter VARCHAR(100) NULL AFTER website",
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS github VARCHAR(100) NULL AFTER twitter",
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS profile_count INT DEFAULT 0 AFTER download_count",
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS profile_downloads INT DEFAULT 0 AFTER profile_count",
    "ALTER TABLE models ADD COLUMN IF NOT EXISTS profile_count INT DEFAULT 0 AFTER file_count"
];

foreach ($migrations as $migration) {
    if (!$conn->query($migration)) {
        // Ignore errors if column already exists (for MySQL versions without IF NOT EXISTS support)
        if (strpos($conn->error, 'Duplicate column') === false) {
            // Log but don't fail - these are optional schema updates
            error_log("Migration warning: " . $conn->error);
        }
    }
}
echo "✓ Schema migrations applied!\n\n";

// Insert default categories
$categories = [
    ['arcade-parts', 'Arcade Parts', 'fa-gamepad', 'Buttons, joysticks, bezels, and arcade cabinet components'],
    ['redemption', 'Redemption Games', 'fa-ticket', 'Parts for ticket and prize redemption machines'],
    ['signage', 'Signage & Displays', 'fa-sign', 'Signs, toppers, marquees, and display pieces'],
    ['coin-op', 'Coin-Op & Tokens', 'fa-coins', 'Coin mechanisms, token holders, and cash handling'],
    ['maintenance', 'Maintenance Tools', 'fa-wrench', 'Tools and jigs for maintenance and repairs'],
    ['prizes', 'Prize Displays', 'fa-gift', 'Prize shelving, holders, and display units'],
    ['accessories', 'Accessories', 'fa-puzzle-piece', 'Cup holders, phone stands, and misc accessories'],
    ['other', 'Other', 'fa-cube', 'Miscellaneous 3D printable models']
];

$stmt = $conn->prepare("INSERT IGNORE INTO categories (id, name, icon, description, count) VALUES (?, ?, ?, ?, 0)");
foreach ($categories as $cat) {
    $stmt->bind_param("ssss", $cat[0], $cat[1], $cat[2], $cat[3]);
    $stmt->execute();
}
$stmt->close();
echo "✓ Default categories inserted!\n\n";

// Insert default printers
$printers = [
    ['bambu-p1s', 'Bambu Lab P1S', 'Bambu Lab', 256, 256, 256, 0.4, 'High-speed CoreXY 3D printer with enclosed build chamber'],
    ['bambu-p1p', 'Bambu Lab P1P', 'Bambu Lab', 256, 256, 256, 0.4, 'High-speed CoreXY 3D printer'],
    ['bambu-x1c', 'Bambu Lab X1 Carbon', 'Bambu Lab', 256, 256, 256, 0.4, 'Premium multi-color 3D printer with AMS'],
    ['bambu-a1', 'Bambu Lab A1', 'Bambu Lab', 256, 256, 256, 0.4, 'Beginner-friendly CoreXY printer'],
    ['prusa-mk4', 'Prusa MK4', 'Prusa Research', 250, 210, 220, 0.4, 'Input shaper enabled i3-style printer'],
    ['prusa-mk3s', 'Prusa i3 MK3S+', 'Prusa Research', 250, 210, 210, 0.4, 'Popular reliable i3-style printer'],
    ['prusa-xl', 'Prusa XL', 'Prusa Research', 360, 360, 360, 0.4, 'Large format CoreXY with tool changer'],
    ['prusa-mini', 'Prusa Mini+', 'Prusa Research', 180, 180, 180, 0.4, 'Compact bowden printer'],
    ['ender3-v3', 'Creality Ender 3 V3', 'Creality', 220, 220, 250, 0.4, 'CoreXZ budget-friendly printer'],
    ['ender3-s1', 'Creality Ender 3 S1', 'Creality', 220, 220, 270, 0.4, 'Direct drive Ender 3 variant'],
    ['cr10-smart', 'Creality CR-10 Smart', 'Creality', 300, 300, 400, 0.4, 'Large format auto-leveling printer'],
    ['anycubic-kobra', 'AnyCubic Kobra 2', 'AnyCubic', 250, 220, 220, 0.4, 'High-speed budget printer'],
    ['elegoo-neptune', 'Elegoo Neptune 3', 'Elegoo', 225, 225, 280, 0.4, 'Direct drive auto-leveling printer'],
    ['qidi-xmax', 'QIDI X-Max 3', 'QIDI', 325, 325, 315, 0.4, 'Enclosed industrial printer'],
    ['voron-24', 'Voron 2.4', 'Voron Design', 350, 350, 350, 0.4, 'DIY CoreXY high-performance printer'],
    ['generic', 'Generic FDM Printer', 'Generic', 200, 200, 200, 0.4, 'Standard FDM 3D printer']
];

$stmt = $conn->prepare("INSERT IGNORE INTO printers (id, name, manufacturer, build_volume_x, build_volume_y, build_volume_z, nozzle_diameter, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
foreach ($printers as $printer) {
    $stmt->bind_param("sssiiids", $printer[0], $printer[1], $printer[2], $printer[3], $printer[4], $printer[5], $printer[6], $printer[7]);
    $stmt->execute();
}
$stmt->close();
echo "✓ Default printers inserted!\n\n";

// Insert default filaments
$filaments = [
    // Generic material types (no specific manufacturer)
    ['pla-generic', 'PLA (Generic)', null, null, 'PLA', 'Standard PLA filament - easy to print, biodegradable'],
    ['petg-generic', 'PETG (Generic)', null, null, 'PETG', 'Durable PETG - strong and weather resistant'],
    ['abs-generic', 'ABS (Generic)', null, null, 'ABS', 'ABS filament - strong and heat resistant'],
    ['tpu-generic', 'TPU (Generic)', null, null, 'TPU', 'Flexible TPU filament'],
    ['asa-generic', 'ASA (Generic)', null, null, 'ASA', 'ASA filament - UV and weather resistant'],
    ['nylon-generic', 'Nylon (Generic)', null, null, 'Nylon', 'Nylon filament - extremely durable'],
    ['pc-generic', 'Polycarbonate (Generic)', null, null, 'PC', 'PC filament - high strength and heat resistance'],

    // Bambu Lab filaments
    ['bambu-pla-basic', 'PLA Basic', 'Bambu Lab', 'Various', 'PLA', 'Bambu Lab PLA Basic filament'],
    ['bambu-pla-matte', 'PLA Matte', 'Bambu Lab', 'Various', 'PLA', 'Bambu Lab PLA Matte filament'],
    ['bambu-petg-basic', 'PETG Basic', 'Bambu Lab', 'Various', 'PETG', 'Bambu Lab PETG Basic filament'],
    ['bambu-abs', 'ABS', 'Bambu Lab', 'Various', 'ABS', 'Bambu Lab ABS filament'],
    ['bambu-asa', 'ASA', 'Bambu Lab', 'Various', 'ASA', 'Bambu Lab ASA filament'],
    ['bambu-tpu-95a', 'TPU 95A', 'Bambu Lab', 'Various', 'TPU', 'Bambu Lab TPU 95A flexible filament'],

    // Popular brands
    ['prusament-pla', 'Prusament PLA', 'Prusa Research', 'Various', 'PLA', 'High quality Prusament PLA'],
    ['prusament-petg', 'Prusament PETG', 'Prusa Research', 'Various', 'PETG', 'High quality Prusament PETG'],
    ['hatchbox-pla', 'Hatchbox PLA', 'Hatchbox', 'Various', 'PLA', 'Popular Hatchbox PLA filament'],
    ['esun-pla-plus', 'eSun PLA+', 'eSun', 'Various', 'PLA', 'Enhanced PLA+ with better layer adhesion'],
    ['overture-petg', 'Overture PETG', 'Overture', 'Various', 'PETG', 'Reliable PETG filament']
];

$stmt = $conn->prepare("INSERT IGNORE INTO filaments (id, name, manufacturer, color, material_type, description) VALUES (?, ?, ?, ?, ?, ?)");
foreach ($filaments as $filament) {
    $stmt->bind_param("ssssss", $filament[0], $filament[1], $filament[2], $filament[3], $filament[4], $filament[5]);
    $stmt->execute();
}
$stmt->close();
echo "✓ Default filaments inserted!\n\n";

// Create default admin user
$adminId = 'admin';
$username = 'admin';
$email = 'admin@fecvault.com';
$password = password_hash('admin123', PASSWORD_DEFAULT);

$stmt = $conn->prepare("INSERT IGNORE INTO users (id, username, email, password, is_admin, bio, location, model_count, download_count) VALUES (?, ?, ?, ?, TRUE, 'Site Administrator', 'HQ', 0, 0)");
$stmt->bind_param("ssss", $adminId, $username, $email, $password);
$stmt->execute();
$stmt->close();

outputMessage("✓ Default admin user created!\n  Username: admin\n  Password: admin123\n  (Please change this password after first login!)");

$conn->close();

if (!$isCli) {
    ?>
    <div class="message success">
        <strong>✅ Database Setup Complete!</strong>
    </div>

    <h2>📊 What Was Created</h2>
    <div class="stats">
        <div class="stat">
            <div class="stat-value">13</div>
            <div class="stat-label">Database Tables</div>
        </div>
        <div class="stat">
            <div class="stat-value">8</div>
            <div class="stat-label">Default Categories</div>
        </div>
        <div class="stat">
            <div class="stat-value">16</div>
            <div class="stat-label">Default Printers</div>
        </div>
        <div class="stat">
            <div class="stat-value">18</div>
            <div class="stat-label">Default Filaments</div>
        </div>
        <div class="stat">
            <div class="stat-value">1</div>
            <div class="stat-label">Admin User</div>
        </div>
    </div>

    <div class="message info">
        <strong>🔐 Admin Credentials</strong><br>
        <strong>Username:</strong> admin<br>
        <strong>Password:</strong> admin123<br>
        <em>⚠️ Please change this password after first login!</em>
    </div>

    <h2>📝 Next Steps</h2>
    <ol>
        <li>If you have existing JSON data:
            <ul>
                <li>Run <code>migrate_json_to_mysql.php</code> to import your data</li>
            </ul>
        </li>
        <li>Test the application to verify everything works</li>
        <li>Log in and change the admin password</li>
        <li><strong>For security:</strong> Delete or restrict access to <code>setup_database.php</code></li>
    </ol>

    <div class="message info">
        <strong>💡 Tip:</strong> If you're experiencing duplicate models or need to start fresh, run
        <code>setup_database.php?reset=1</code> to clear all tables before recreating them,
        then run the migration script again.
    </div>

    <h2>🔗 Quick Links</h2>
    <a href="index.php" class="btn">🏠 Go to Site</a>
    <a href="login.php" class="btn">🔐 Log In</a>
    <a href="migrate_json_to_mysql.php" class="btn">📦 Migrate JSON Data</a>
    <a href="setup_database.php?reset=1" class="btn" onclick="return confirm('This will DELETE all data in the database. Are you sure?');">🗑️ Reset & Rebuild Database</a>

    </div>
    </body>
    </html>
    <?php
} else {
    echo "\n========================================\n";
    echo "Database setup complete!\n";
    echo "========================================\n\n";
    echo "Next steps:\n";
    echo "1. If you have existing JSON data, run: php migrate_json_to_mysql.php\n";
    echo "2. Update your file permissions if needed\n";
    echo "3. Log in with admin credentials and change the password\n";
    echo "4. Delete or restrict access to this setup file for security\n\n";
    echo "Tip: If experiencing duplicate models, run with --reset flag:\n";
    echo "     php setup_database.php --reset\n";
    echo "     This will clear all tables before recreating them.\n\n";
}
?>
