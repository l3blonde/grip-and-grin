#!/bin/bash

# Deploy Grip and Grin to Linode Server
# Run this script on your LOCAL machine (not the server)

set -e

SERVER_IP="172.234.63.14"
SERVER_USER="root"
PROJECT_DIR="/var/www/html"
GITHUB_REPO="https://github.com/l3blonde/grip-and-grin.git"

echo "🚀 Deploying Grip and Grin to Linode server..."

# Step 1: Connect to server and clone repository
echo "📥 Cloning repository to server..."
ssh $SERVER_USER@$SERVER_IP << 'ENDSSH'
# Remove existing files
rm -rf /var/www/html/*

# Clone the repository
cd /var/www
git clone https://github.com/l3blonde/grip-and-grin.git html

# Set proper permissions
chown -R www-data:www-data /var/www/html
chmod -R 755 /var/www/html
chmod -R 777 /var/www/html/public/uploads

# Install Composer dependencies
cd /var/www/html
if [ -f "composer.json" ]; then
    composer install --no-dev --optimize-autoloader
fi

# Create environment file
cp .env.example .env
sed -i 's/APP_ENV=development/APP_ENV=production/' .env
sed -i 's/APP_DEBUG=true/APP_DEBUG=false/' .env
sed -i 's/DB_HOST=localhost/DB_HOST=localhost/' .env
sed -i 's/DB_NAME=grip_and_grin_db/DB_NAME=grip_and_grin_db/' .env
sed -i 's/DB_USER=root/DB_USER=grip_user/' .env
sed -i 's/DB_PASSWORD=/DB_PASSWORD=grip_secure_password_2024/' .env

# Set environment file permissions
chown www-data:www-data .env
chmod 600 .env

echo "✅ Repository cloned and configured"
ENDSSH

# Step 2: Import database schema
echo "🗄️ Importing database schema..."
ssh $SERVER_USER@$SERVER_IP << 'ENDSSH'
cd /var/www/html
mysql -u grip_user -pgrip_secure_password_2024 grip_and_grin_db < database/schema.sql
echo "✅ Database schema imported"
ENDSSH

# Step 3: Test the deployment
echo "🧪 Testing deployment..."
curl -s http://$SERVER_IP | grep -q "Grip and Grin" && echo "✅ Website is responding" || echo "❌ Website test failed"

echo ""
echo "🎉 DEPLOYMENT COMPLETED!"
echo "🌐 Visit: http://$SERVER_IP"
echo "📊 Admin login: admin@gripandgrin.com / password123"
