# semantic-geoflow

AI semantic-cache enhanced GEO content engineering and multi-site distribution platform.

This repository is a secondary development version based on [GEOFlow](https://github.com/yaojingang/GEOFlow). It keeps the original Apache-2.0 license and adds an AI semantic cache module.

## What It Does

- Manage content materials, prompts, titles, keywords, images, authors, categories, and knowledge bases.
- Generate articles with AI models through OpenAI-compatible providers or Gemini.
- Reuse similar AI generation results through semantic cache matching.
- Support knowledge-base retrieval and embedding-based recall.
- Review, publish, and manage articles in an admin panel.
- Distribute content to GEOFlow Agent sites, WordPress REST endpoints, or generic HTTP APIs.
- Track tasks, queues, distribution logs, site views, and basic analytics.

## Tech Stack

- PHP 8.2+
- Laravel 12
- PostgreSQL with optional pgvector support
- Redis
- Laravel Horizon
- Laravel Reverb
- Vite
- Docker Compose

## Requirements

- PHP 8.2 or newer
- Composer
- Node.js and npm
- PostgreSQL
- Redis
- Docker and Docker Compose, if using the Docker setup

## Quick Start

```bash
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate --seed
npm install
npm run build
php artisan serve
```

For local development with the bundled Laravel script:

```bash
composer run dev
```

## Docker

```bash
cp .env.example .env
docker compose up -d --build
```

The default app URL in `.env.example` is:

```text
http://localhost:18080
```

## Admin Account

The default seed account is configured in `.env.example`:

```text
GEOFLOW_ADMIN_USERNAME=admin
GEOFLOW_ADMIN_EMAIL=admin@example.com
GEOFLOW_ADMIN_PASSWORD=password
```

Change these values before public deployment.

## Important Configuration

- `APP_URL`: public URL of the application.
- `ADMIN_BASE_PATH`: admin panel path.
- `GEOFLOW_CACHE_ENABLED`: enable or disable semantic cache.
- `GEOFLOW_CACHE_TTL`: cache lifetime in seconds.
- `GEOFLOW_HTTP_PROXY` / `GEOFLOW_HTTPS_PROXY`: optional outbound proxy for AI providers.
- `GEOFLOW_UPDATE_CHECK_ENABLED`: enable or disable version checks.

AI model provider settings are managed from the admin panel after installation.

## Tests

```bash
composer test
```

## Repository Notes

This project includes:

- Application source code in `app/`
- Routes in `routes/`
- Database migrations and seeders in `database/`
- Blade views and frontend assets in `resources/`
- Public assets in `public/`
- Deployment notes in `docs/deployment/`
- Distribution agent sample files in `docs/distribution/`

## License

Apache License 2.0. See [LICENSE](LICENSE).

## Credits

Original project: [yaojingang/GEOFlow](https://github.com/yaojingang/GEOFlow)

