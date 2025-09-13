Usage notes:

- This skeleton must be used with Composer to install Laravel framework dependencies.
- After running `composer install`, run `php artisan migrate --seed` to create tables and seed data.
- The private storage disk is configured. Create folder storage/app/private and set permissions.
- Protect endpoints with Sanctum or other auth as needed.
