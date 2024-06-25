#!/usr/bin/env php

echo "Running territorial division install script..."

php artisan make:cache-table --force
php artisan make:session-table --force
php artisan make:queue-table --force
php artisan make:queue-failed-table --force
php artisan make:queue-batches-table --force
php artisan make:notifications-table --force

php artisan telescope:install --force
php artisan install:api --passport --force
php artisan vendor:publish --provider="Laravel\Pulse\PulseServiceProvider" --force
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider" --force
php artisan vendor:publish --provider="Spatie\Activitylog\ActivitylogServiceProvider" --tag="activitylog-migrations" --force
php artisan vendor:publish --provider="Spatie\Activitylog\ActivitylogServiceProvider" --tag="activitylog-config" --force

php artisan migrate