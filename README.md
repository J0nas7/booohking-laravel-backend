# Booohking – Booking System
Full-stack booking application built with **Laravel 12** (backend API) and **Next.js 16** (frontend).

---

## Tech Stack

### Backend (Laravel 12)
- JWT token authentication (`tymon/jwt-auth`)
- Redis caching (services, providers, working hours)
  *Bookings are not cached since they change frequently*
- SQLite (chosen for simplicity in Docker & testing)
  *Since it is self-hosted inside the Laravel project*
- Laravel validation rules
- Service classes for business logic
- Database migrations, seeders and factories
- Role middleware for checking user role.
- Feature tests + unit tests with PHPUnit (101 tests).

### Frontend (Next.js 16 + TypeScript)
- App Router
- TailwindCSS + SCSS Modules for styling
- Axios for API calls
- Redux Toolkit for authentication state
- React Context for domain data (bookings, providers, services etc.)
- TanStack Query for data fetching and caching
- Unit tests with Jest (99 tests).

---

## Repositories

Clone both repositories into the **same parent folder**:

- `booohking-laravel-backend`
- `booohking-nextjs-frontend`


---


## Setup the Project (Docker)

### Build the backend and frontend
```bash
cd booohking-laravel-backend
docker-compose up --build
```

This will start:
- Laravel backend (port 8080)
- Redis server
- Next.js frontend (port 3000)

### Setup Laravel environment

Once the container is running, you need to setup Laravel environment, by entering the Docker compose backend:
```bash
docker-compose exec backend bash
```

And then run inside container:
```bash
composer install
cp .env.example .env
php artisan jwt:secret
touch database/database.sqlite
chmod 777 database/database.sqlite
php artisan migrate --seed
```

After setup, all subsequent project starts is simply **docker-compose up**.

## Pre-seeded users you can sign in with:

# ADMIN

E-mail:
jonas-adm@booohking.com
Password:
abc123def

# USER

E-mail:
jonas-usr@booohking.com
Password:
abc123def

## Testing

Backend – PHPUnit
```bash
docker compose exec backend php artisan test
```

Frontend – Jest
```bash
docker compose exec frontend npx jest
```
