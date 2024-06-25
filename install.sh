#!/usr/bin/env php

# ANSI color code for red
RED='\033[0;31m'
NC='\033[0m' # No Color

echo "Running territorial division install script..."

if [ -f "artisan" ]; then
    echo "Artisan file found. Running custom installation script..."

    php artisan make:cache-table
    php artisan make:session-table
    php artisan make:queue-table
    php artisan make:queue-failed-table
    php artisan make:queue-batches-table
    php artisan make:notifications-table

    php artisan telescope:install
    php artisan install:api --passport
    php artisan vendor:publish --provider="Laravel\Pulse\PulseServiceProvider"
    php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
    php artisan vendor:publish --provider="Spatie\Activitylog\ActivitylogServiceProvider" --tag="activitylog-migrations"
    php artisan vendor:publish --provider="Spatie\Activitylog\ActivitylogServiceProvider" --tag="activitylog-config"

    php artisan migrate
else
    echo -e "${RED}Artisan file not found. Skipping custom installation script.${NC}"
fi