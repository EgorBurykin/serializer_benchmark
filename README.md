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
It will print you something like that:

```
Scenario 1:
 * Run-and-die process
 * One entity to serialize
 * Entity is not loaded to doctrine cache
Jett serializer is ~ 2.6x faster

Scenario 2:
 * Web-socket daemon (continuous execution)
 * One entity to serialize
 * Entity is loaded to doctrine cache once
Jett serializer is ~ 12.8x faster

Scenario 3:
 * Run-and-die process
 * Collection of entities to serialize
 * Collection is not loaded to doctrine cache
Jett serializer is ~ 4.6x faster

Scenario 4:
 * Web-socket daemon (continuous execution)
 * Collection of entities to serialize
 * Collection is loaded to doctrine cache once
Jett serializer is ~ 13.5x faster
```
