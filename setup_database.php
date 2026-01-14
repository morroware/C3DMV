<?php
/**
 * FEC STL Vault - Database Setup Script
 * Run this once to create MySQL database schema
 * Compatible with cPanel shared hosting
 */

// Prevent direct access in production
if (!defined('SETUP_MODE')) {
    define('SETUP_MODE', true);
}

require_once __DIR__ . '/includes/db_config.php';

// Create database connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error . "\n\nPlease check your database credentials in includes/db_config.php");
}

echo "Connected to database successfully!\n\n";

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
    INDEX idx_model_id (model_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Model photos table (for multiple photos per model)
CREATE TABLE IF NOT EXISTS model_photos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    model_id VARCHAR(32) NOT NULL,
    filename VARCHAR(255) NOT NULL,
    is_primary BOOLEAN DEFAULT FALSE,
    photo_order INT DEFAULT 0,
    FOREIGN KEY (model_id) REFERENCES models(id) ON DELETE CASCADE,
    INDEX idx_model_id (model_id)
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

// Insert default categories
$categories = [
    ['arcade-parts', 'Arcade Parts', 'fa-gamepad', 'Buttons, joysticks, bezels, and arcade cabinet components'],
    ['redemption', 'Redemption Games', 'fa-ticket', 'Parts for ticket and prize redemption machines'],
    ['signage', 'Signage & Displays', 'fa-sign', 'Signs, toppers, marquees, and display pieces'],
    ['coin-op', 'Coin-Op & Tokens', 'fa-coins', 'Coin mechanisms, token holders, and cash handling'],
    ['maintenance', 'Maintenance Tools', 'fa-wrench', 'Tools and jigs for FEC maintenance'],
    ['prizes', 'Prize Displays', 'fa-gift', 'Prize shelving, holders, and display units'],
    ['accessories', 'Accessories', 'fa-puzzle-piece', 'Cup holders, phone stands, and misc accessories'],
    ['other', 'Other', 'fa-cube', 'Miscellaneous FEC-related prints']
];

$stmt = $conn->prepare("INSERT IGNORE INTO categories (id, name, icon, description, count) VALUES (?, ?, ?, ?, 0)");
foreach ($categories as $cat) {
    $stmt->bind_param("ssss", $cat[0], $cat[1], $cat[2], $cat[3]);
    $stmt->execute();
}
$stmt->close();
echo "✓ Default categories inserted!\n\n";

// Create default admin user
$adminId = 'admin';
$username = 'admin';
$email = 'admin@fecvault.com';
$password = password_hash('admin123', PASSWORD_DEFAULT);

$stmt = $conn->prepare("INSERT IGNORE INTO users (id, username, email, password, is_admin, bio, location, model_count, download_count) VALUES (?, ?, ?, ?, TRUE, 'Site Administrator', 'HQ', 0, 0)");
$stmt->bind_param("ssss", $adminId, $username, $email, $password);
$stmt->execute();
$stmt->close();
echo "✓ Default admin user created!\n";
echo "  Username: admin\n";
echo "  Password: admin123\n";
echo "  (Please change this password after first login!)\n\n";

$conn->close();

echo "========================================\n";
echo "Database setup complete!\n";
echo "========================================\n\n";
echo "Next steps:\n";
echo "1. If you have existing JSON data, run: php migrate_json_to_mysql.php\n";
echo "2. Update your file permissions if needed\n";
echo "3. Log in with admin credentials and change the password\n";
echo "4. Delete or restrict access to this setup file for security\n\n";
?>
