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

https://docs.google.com/document/d/1rdTDI5vMuI4--sfeMcswWKg8uqS1yP7kP_Gl6KI5inI/edit

https://docs.google.com/document/d/1YtSYjqg17TOjngyD4aftfaLWBD4P4yXXTvKraKdK6g4/edit