php artisan make:notifications-table
php artisan make:queue-table
php artisan make:queue-failed-table
php artisan make:queue-batches-table
php artisan make:session-table
php artisan make:cache-table

php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
php artisan vendor:publish --provider="Laravel\Pulse\PulseServiceProvider"
php artisan telescope:install
php artisan install:api --passport

php artisan migrate