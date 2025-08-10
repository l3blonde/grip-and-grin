#!/bin/bash

# Complete Server Setup Script for Grip and Grin
# Run this on your Linode server after basic installation

set -e

echo "🚀 Starting Grip and Grin server setup..."

# Colors for output
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

print_step() {
    echo -e "${BLUE}🔧 $1${NC}"
}

print_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

print_error() {
    echo -e "${RED}❌ $1${NC}"
}

# Check if running as root
if [ "$EUID" -ne 0 ]; then
    print_error "Please run this script as root"
    exit 1
fi

# Step 1: Configure MySQL
print_step "Setting up MySQL database..."
mysql -u root << 'MYSQL_SCRIPT'
CREATE DATABASE IF NOT EXISTS grip_and_grin_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'grip_user'@'localhost' IDENTIFIED BY 'grip_secure_password_2024';
GRANT ALL PRIVILEGES ON grip_and_grin_db.* TO 'grip_user'@'localhost';
FLUSH PRIVILEGES;
MYSQL_SCRIPT
print_success "MySQL database configured"

# Step 2: Configure Apache
print_step "Configuring Apache virtual host..."
cat > /etc/apache2/sites-available/grip-and-grin.conf << 'APACHE_CONFIG'
<VirtualHost *:80>
    ServerName 172.234.63.14
    DocumentRoot /var/www/html/public

    <Directory /var/www/html/public>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
        DirectoryIndex index.php index.html
    </Directory>

    <Directory /var/www/html/src>
        Require all denied
    </Directory>

    <Directory /var/www/html/templates>
        Require all denied
    </Directory>

    <Directory /var/www/html/database>
        Require all denied
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/grip-and-grin-error.log
    CustomLog ${APACHE_LOG_DIR}/grip-and-grin-access.log combined
</VirtualHost>
APACHE_CONFIG

a2ensite grip-and-grin.conf
a2dissite 000-default.conf
a2enmod rewrite headers
systemctl restart apache2
print_success "Apache configured"

# Step 3: Set up firewall
print_step "Configuring firewall..."
apt install -y ufw
ufw --force reset
ufw default deny incoming
ufw default allow outgoing
ufw allow ssh
ufw allow http
ufw allow https
ufw --force enable
print_success "Firewall configured"

# Step 4: Create application directories
print_step "Creating application directories..."
mkdir -p /opt/grip-and-grin
mkdir -p /var/www/html/public/uploads/{originals,thumbnails,medium,full}
chmod -R 755 /var/www/html/public/uploads
chown -R www-data:www-data /var/www/html/public/uploads
print_success "Directories created"

# Step 5: Create environment file
print_step "Creating environment configuration..."
cat > /var/www/html/.env << 'ENV_CONFIG'
# Application Configuration
APP_ENV=production
APP_DEBUG=false
APP_URL=http://172.234.63.14
APP_NAME="Grip and Grin"

# Database Configuration
DB_HOST=localhost
DB_NAME=grip_and_grin_db
DB_USER=grip_user
DB_PASSWORD=grip_secure_password_2024
DB_PORT=3306

# Session Configuration
SESSION_LIFETIME=7200
SESSION_SECURE=false
SESSION_NAME=grip_and_grin_session

# Upload Configuration
UPLOAD_MAX_SIZE=10M
UPLOAD_PATH=public/uploads
MAX_UPLOAD_SIZE=10485760
ALLOWED_EXTENSIONS=jpg,jpeg,png,gif,webp

# Security
JWT_SECRET=your_jwt_secret_key_here_change_in_production
ENCRYPTION_KEY=your_32_character_encryption_key_here
ENV_CONFIG

chown www-data:www-data /var/www/html/.env
chmod 600 /var/www/html/.env
print_success "Environment file created"

# Step 6: Create basic test page
print_step "Creating test page..."
cat > /var/www/html/public/index.php << 'PHP_TEST'
<?php
echo "<h1>🚗 Grip and Grin Server Ready!</h1>";
echo "<p><strong>Server IP:</strong> 172.234.63.14</p>";
echo "<p><strong>PHP Version:</strong> " . PHP_VERSION . "</p>";
echo "<p><strong>Server Time:</strong> " . date('Y-m-d H:i:s') . "</p>";

// Test database connection
try {
    $pdo = new PDO('mysql:host=localhost;dbname=grip_and_grin_db', 'grip_user', 'grip_secure_password_2024');
    echo "<p style='color: green;'>✅ Database connection successful!</p>";

    // Test if tables exist
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (count($tables) > 0) {
        echo "<p style='color: green;'>✅ Database tables found: " . implode(', ', $tables) . "</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ Database connected but no tables found. Run database migration.</p>";
    }

} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Database connection failed: " . $e->getMessage() . "</p>";
}

// Test file permissions
$uploadDir = '/var/www/html/public/uploads';
if (is_writable($uploadDir)) {
    echo "<p style='color: green;'>✅ Upload directory is writable</p>";
} else {
    echo "<p style='color: red;'>❌ Upload directory is not writable</p>";
}

// Show PHP modules
echo "<h2>PHP Modules</h2>";
$modules = get_loaded_extensions();
$required = ['pdo', 'pdo_mysql', 'gd', 'curl', 'mbstring', 'zip'];
foreach ($required as $module) {
    $status = in_array($module, $modules) ? '✅' : '❌';
    echo "<p>$status $module</p>";
}

phpinfo();
?>
PHP_TEST

chown -R www-data:www-data /var/www/html
chmod -R 755 /var/www/html
print_success "Test page created"

# Step 7: Install Composer dependencies (if composer.json exists)
if [ -f "/var/www/html/composer.json" ]; then
    print_step "Installing Composer dependencies..."
    cd /var/www/html
    composer install --no-dev --optimize-autoloader
    print_success "Composer dependencies installed"
fi

echo ""
echo -e "${GREEN}🎉 SERVER SETUP COMPLETED SUCCESSFULLY!${NC}"
echo ""
echo "📋 Summary:"
echo "• Server IP: 172.234.63.14"
echo "• Database: grip_and_grin_db"
echo "• Database User: grip_user"
echo "• Web Directory: /var/www/html"
echo "• Test URL: http://172.234.63.14"
echo ""
echo "🎯 Next Steps:"
echo "1. Visit http://172.234.63.14 to test"
echo "2. Upload your project files to /var/www/html"
echo "3. Run database migration: mysql -u grip_user -p grip_and_grin_db < database/schema.sql"
echo "4. Configure domain name (optional)"
echo "5. Set up SSL certificate (optional)"
echo ""
echo "🔐 Database Credentials:"
echo "• Host: localhost"
echo "• Database: grip_and_grin_db"
echo "• Username: grip_user"
echo "• Password: grip_secure_password_2024"
