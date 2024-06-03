## Laravel Project Setup Guidelines
```bash
> $ cp .env.example .env

> Update .env file :
    set DB_CONNECTION
    set DB_DATABASE
    set DB_USERNAME
    set DB_PASSWORD

> composer install

> php artisan key:generate

> php artisan migrate

> php artisan passport:install

> php artisan serve
```
> Register API Endpoint: http://127.0.0.1:8000/api/register

> Login API Endpoint: http://127.0.0.1:8000/api/login

> Task List API Endpoint: http://127.0.0.1:8000/api/task

> Task Create API Endpoint: http://127.0.0.1:8000/api/task/create
