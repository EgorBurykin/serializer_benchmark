# How to run benchmark
Run in you shell:
```bash
composer install
# Create database
php bin/console d:d:c
# Update schema
php bin/console d:s:u --force
# Load fixtures
php bin/console d:f:l
php bin/console c:cl --env=prod
# Run benchmark
php bin/console app:benchmark
```
