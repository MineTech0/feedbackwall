# Palauteseinä (Feedback Wall)

Käyttäjäpalautesovellus, rakennettu Laravel 12:lla, Inertia.js:llä ja Svelte 5:llä.

## Tech Stack

- **Backend:** Laravel 12, PHP 8.2+
- **Frontend:** Svelte 5, Inertia.js, Tailwind CSS 4
- **Build:** Vite 7
- **Auth:** Laravel Socialite (Google OAuth)
- **Monitoring:** Laravel Telescope

## Kehitysympäristö

### Vaatimukset

- PHP 8.2+
- Composer
- Node.js 20+
- SQLite (kehitykseen) tai PostgreSQL/MariaDB (tuotantoon)

### Asennus

```bash
# Kloonaa repo
git clone <repo-url>
cd feedbackwall

# Asenna riippuvuudet
composer install
npm install

# Kopioi environment
cp .env.example .env

# Generoi avain ja aja migraatiot
php artisan key:generate
php artisan migrate

# Käynnistä kehityspalvelin
composer dev
```

### Kehityskomennot

```bash
# Käynnistä kaikki palvelut (server, queue, vite, logs)
composer dev

# Testit
composer test

# Koodin formatointi
./vendor/bin/pint
```

## 🚀 Tuotantoon vienti (Coolify)

Katso täydellinen deployment-opas: **[docs/PRODUCTION.md](docs/PRODUCTION.md)**

### Pikaohje

1. **Luo Coolify-projekti:** Docker Compose
2. **Lisää ympäristömuuttujat:** Katso `docs/env-production-template.txt`
3. **Deploy:** Push to Git → Coolify auto-deploy

### Tiedostot

| Tiedosto | Kuvaus |
|----------|--------|
| `docker-compose.production.yml` | Täysi tuotantokonfiguraatio (PostgreSQL + Redis) |
| `docker-compose.simple.yml` | Yksinkertaistettu (MariaDB, ei Redistä) |
| `Dockerfile.production` | Nginx + PHP-FPM image |
| `Dockerfile.frankenphp` | FrankenPHP image (vaihtoehto) |
| `docs/PRODUCTION.md` | Täydellinen deployment-dokumentaatio |
| `docs/env-production-template.txt` | Environment muuttujapohja |

### Docker-kuvan valinta

**Nginx + PHP-FPM (suositeltu):**
- `serversideup/php:8.3-fpm-nginx`
- Vakaa, hyvin dokumentoitu
- Käytä: `Dockerfile.production`

**FrankenPHP (modernimpi):**
- `serversideup/php:8.3-frankenphp`
- Yksi prosessi, Laravel Octane -yhteensopiva
- Käytä: `Dockerfile.frankenphp`

## Arkkitehtuuri

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Admin/          # Admin-toiminnot
│   │   ├── AuthController  # Google OAuth
│   │   ├── BoardController # Palautetaulut
│   │   └── FeedbackController
│   └── Middleware/
├── Models/
│   ├── Board.php
│   ├── Feedback.php
│   └── User.php
└── Services/
    ├── BoardService.php
    └── FeedbackService.php

resources/js/
├── Layouts/
│   └── Layout.svelte
└── Pages/
    ├── Home.svelte
    ├── Board.svelte
    └── Admin/...
```

## Lisenssi

MIT
