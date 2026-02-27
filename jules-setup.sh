#!/bin/bash
set -e

echo "Setting up environment for Laravel 12 + PHP 8.4..."

# Install PHP 8.4 and extensions
if ! command -v php8.4 &> /dev/null; then
    sudo add-apt-repository ppa:ondrej/php -y
    sudo apt-get update
    sudo apt-get install -y php8.4 php8.4-cli php8.4-common php8.4-mysql php8.4-zip php8.4-gd php8.4-mbstring php8.4-curl php8.4-xml php8.4-bcmath php8.4-intl php8.4-sqlite3 unzip git
fi

# Set PHP 8.4 as default
if command -v update-alternatives &> /dev/null; then
    sudo update-alternatives --set php /usr/bin/php8.4 || true
fi

# Verify PHP version
php -v

# Setup .env
if [ ! -f .env ]; then
    cp .env.example .env
fi

# Ensure SQLite configuration
if grep -q "DB_CONNECTION=mysql" .env; then
    echo "Switching .env to SQLite..."
    sed -i 's/DB_CONNECTION=mysql/DB_CONNECTION=sqlite/' .env
    # Remove MySQL specific vars
    sed -i '/DB_HOST/d' .env
    sed -i '/DB_PORT/d' .env
    sed -i '/DB_DATABASE/d' .env
    sed -i '/DB_USERNAME/d' .env
    sed -i '/DB_PASSWORD/d' .env
fi

# Ensure SQLite file exists
if [ ! -f database/database.sqlite ]; then
    touch database/database.sqlite
fi

# Install dependencies
composer install
npm install
npm run build

# Generate key if not set (or regenerate if needed, but safer to check)
if ! grep -q "APP_KEY=base64" .env; then
    php artisan key:generate
fi

# Migrate
php artisan migrate --force

echo "Environment setup complete!"
