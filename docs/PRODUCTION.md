# Palauteseinä (Feedback Wall) - Production Deployment Guide

## Sisällysluettelo

1. [Production Checklist](#1-production-checklist)
2. [Driver-suositukset](#2-driver-suositukset)
3. [Deployment-komennot](#3-deployment-komennot)
4. [Pre-flight tarkistukset](#4-pre-flight-tarkistukset)
5. [Go-live Step-by-step](#5-go-live-step-by-step)
6. [Vianetsintä](#6-vianetsintä)

---

## 1. Production Checklist

### 🔐 Turvallisuus

| Kohde | Tarkistus | Tila |
|-------|-----------|------|
| `APP_DEBUG` | Asetettu `false` | ☐ |
| `APP_ENV` | Asetettu `production` | ☐ |
| `APP_KEY` | Generoitu ja turvallisesti tallennettu | ☐ |
| HTTPS | Pakotettu käyttöön | ☐ |
| Trusted Proxies | Konfiguroitu Coolify/Caddy proxya varten | ☐ |
| Telescope | Rajattu pääsy (viewTelescope gate) | ☐ |
| `.env` | EI versionhallinnassa | ☐ |

### ⚡ Suorituskyky

| Kohde | Komento | Tila |
|-------|---------|------|
| Config cache | `php artisan config:cache` | ☐ |
| Route cache | `php artisan route:cache` | ☐ |
| View cache | `php artisan view:cache` | ☐ |
| Event cache | `php artisan event:cache` | ☐ |
| Autoloader optimointi | `composer install --optimize-autoloader --no-dev` | ☐ |
| OPcache | Enabled PHP-configissa | ☐ |
| Vite build | `npm run build` suoritettu | ☐ |

### 🗄️ Tietokanta & Storage

| Kohde | Tarkistus | Tila |
|-------|-----------|------|
| Migraatiot | Ajettu `--force` flagilla | ☐ |
| Storage link | `php artisan storage:link` | ☐ |
| Storage permissions | `www-data` omistaa `/storage` ja `/bootstrap/cache` | ☐ |
| Tietokantayhteys | Testattu tuotanto-DB:hen | ☐ |

### 🔄 Taustaprosessit

| Kohde | Tarkistus | Tila |
|-------|-----------|------|
| Queue worker | Käynnissä omana containerina | ☐ |
| Scheduler | Käynnissä omana containerina (cron) | ☐ |
| Supervisor/restart policy | `unless-stopped` tai `always` | ☐ |

### 📊 Monitorointi & Logging

| Kohde | Tarkistus | Tila |
|-------|-----------|------|
| LOG_CHANNEL | `stderr` (Docker best practice) | ☐ |
| Healthcheck | Konfiguroitu docker-composessa | ☐ |
| Telescope | Enabled vain virheille tuotannossa | ☐ |

---

## 2. Driver-suositukset

### Vaihtoehto A: Redis käytössä (suositeltu)

```env
# Cache - Nopein vaihtoehto, jaettu containerien välillä
CACHE_STORE=redis

# Session - Nopea, skaalautuva
SESSION_DRIVER=redis

# Queue - Luotettavin ja nopein
QUEUE_CONNECTION=redis

# Logging - Docker stdout/stderr
LOG_CHANNEL=stderr

# Filesystem - Paikallinen (container volume)
FILESYSTEM_DISK=local

# Mail - Tuotantomailer (SMTP/Mailgun/SES)
MAIL_MAILER=smtp
```

**Perustelut:**
- Redis on in-memory → erittäin nopea
- Jaettu tila containerien välillä
- Queue retry-logiikka toimii parhaiten
- Atomic operaatiot (ei race conditioneja)

### Vaihtoehto B: Ilman Redistä (database/file)

```env
# Cache - Tietokantapohjainen, hitaampi mutta toimii
CACHE_STORE=database

# Session - Tietokantapohjainen
SESSION_DRIVER=database

# Queue - Database driver
QUEUE_CONNECTION=database

# Logging - Docker stdout/stderr
LOG_CHANNEL=stderr

# Filesystem - Paikallinen
FILESYSTEM_DISK=local

# Mail - Tuotantomailer
MAIL_MAILER=smtp
```

**Huomiot ilman Redistä:**
- ✅ Yksinkertaisempi arkkitehtuuri
- ✅ Yksi vähemmän palvelu ylläpidettäväksi
- ⚠️ Hitaampi cache-operaatiot
- ⚠️ Session-lukot voivat aiheuttaa viiveitä
- ⚠️ Queue polling kuormittaa tietokantaa

### Vertailutaulukko

| Driver | Redis | Database | File |
|--------|-------|----------|------|
| Cache | ⭐⭐⭐ | ⭐⭐ | ⭐ (ei jaettu) |
| Session | ⭐⭐⭐ | ⭐⭐ | ⭐ (ei jaettu) |
| Queue | ⭐⭐⭐ | ⭐⭐ | ❌ (ei tuotantoon) |
| Skaalautuvuus | ⭐⭐⭐ | ⭐⭐ | ⭐ |

---

## 3. Deployment-komennot

### Ensimmäinen deploy (initial setup)

```bash
# 1. Asenna riippuvuudet (ilman dev-paketteja)
composer install --optimize-autoloader --no-dev --no-interaction

# 2. Generoi APP_KEY (vain kerran!)
php artisan key:generate --force

# 3. Aja migraatiot
php artisan migrate --force

# 4. Luo storage symlink
php artisan storage:link

# 5. Cacheta konfiguraatio
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# 6. (Telescope) Asenna assettit
php artisan telescope:publish
```

### Jokainen deploy (release)

```bash
# 1. Asenna/päivitä riippuvuudet
composer install --optimize-autoloader --no-dev --no-interaction

# 2. Aja uudet migraatiot
php artisan migrate --force

# 3. Tyhjennä vanhat cachet ja luo uudet
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# 4. Käynnistä queue workerit uudelleen (graceful)
php artisan queue:restart

# 5. (Valinnainen) Telescope assettit
php artisan telescope:publish --force
```

### Suositeltu entrypoint-skripti

Tämä skripti ajetaan containerin käynnistyksessä:

```bash
#!/bin/bash
set -e

# Odota tietokantaa
echo "Waiting for database..."
while ! php artisan db:monitor --max-attempts=30 2>/dev/null; do
    sleep 2
done

# Aja migraatiot (vain jos MIGRATE=true)
if [ "$RUN_MIGRATIONS" = "true" ]; then
    echo "Running migrations..."
    php artisan migrate --force
fi

# Cache komennot
echo "Caching configuration..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Storage link (idempotent)
php artisan storage:link 2>/dev/null || true

# Käynnistä pääprosessi
exec "$@"
```

---

## 4. Pre-flight tarkistukset

### Coolifyssä tarkistettavat asiat

#### Portit
- [ ] App container kuuntelee portissa 8080 (FrankenPHP)
- [ ] PostgreSQL/MariaDB portissa 5432/3306 (sisäinen)
- [ ] Redis portissa 6379 (sisäinen)
- [ ] Coolify proxy ohjaa HTTPS → app:8080

#### Healthcheckit
- [ ] App: `/up` endpoint palauttaa 200
- [ ] Database: `pg_isready` tai `mysqladmin ping`
- [ ] Redis: `redis-cli ping`

#### Lokit
- [ ] Container lokit näkyvät Coolify UI:ssa
- [ ] Laravel lokit menevät stderr:iin
- [ ] Ei "permission denied" virheitä

### Tarkistusskripti

Aja containerissa:

```bash
# Tietokantayhteys
php artisan db:monitor

# Cache toimii
php artisan tinker --execute="Cache::put('test', 'ok', 60); echo Cache::get('test');"

# Queue toimii
php artisan queue:work --once --stop-when-empty

# Ympäristö oikein
php artisan env

# Config cachattu
php artisan config:show app.env
```

---

## 5. Go-live Step-by-step

### Coolify + Docker Compose julkaisu (20 askelta)

#### Valmistelu (ennen julkaisua)

1. **Luo Coolify-projekti**
   - New Project → Docker Compose
   - Linkitä Git repository

2. **Konfiguroi environment variables Coolifyssa**
   - Mene Settings → Environment Variables
   - Lisää kaikki `.env.production` muuttujat
   - ⚠️ Älä lisää `.env` tiedostoa repoon!

3. **Generoi APP_KEY**
   ```bash
   php artisan key:generate --show
   ```
   - Kopioi Coolify env muuttujaksi

4. **Luo tietokanta-salasanat**
   - Generoi vahvat salasanat DB:lle ja Redisille
   - Tallenna Coolify secrets

5. **Konfiguroi domain Coolifyssa**
   - Settings → Domains → Lisää domain
   - Ota käyttöön HTTPS (Let's Encrypt)

6. **Tarkista trusted proxies**
   - Varmista `TrustProxies` middleware on konfiguroitu
   - Laravel 12: `$proxies = '*'` tai Coolify IP range

#### Build & Deploy

7. **Tarkista Dockerfile**
   - Multi-stage build (npm build → php image)
   - Oikea serversideup/php image

8. **Push koodi repoon**
   ```bash
   git add -A
   git commit -m "Production configuration"
   git push origin main
   ```

9. **Käynnistä deploy Coolifyssa**
   - Deploy → Manual Deploy tai auto-deploy

10. **Seuraa build-lokeja**
    - Tarkista että npm build onnistuu
    - Tarkista composer install onnistuu

#### Ensimmäinen käynnistys

11. **Odota containerien käynnistymistä**
    - Tarkista status: Healthy

12. **Aja ensimmäiset migraatiot**
    - Coolify: Execute Command → `php artisan migrate --force`
    - Tai aseta `RUN_MIGRATIONS=true` ensimmäisellä deploylla

13. **Tarkista storage permissions**
    - Execute: `ls -la storage/`
    - Pitäisi olla `www-data` omistaja

14. **Luo storage link**
    - Execute: `php artisan storage:link`

#### Verifiointi

15. **Testaa healthcheck**
    ```bash
    curl https://your-domain.com/up
    ```

16. **Testaa päätoiminnallisuus**
    - Avaa selaimessa
    - Testaa kirjautuminen (Google OAuth)
    - Testaa palautteen jättäminen

17. **Tarkista queue worker**
    - Coolify logs: queue container
    - Pitäisi näkyä "Processing" viestejä

18. **Tarkista scheduler**
    - Coolify logs: scheduler container
    - Tarkista cron toimii: `* * * * *`

#### Post-launch

19. **Konfiguroi Telescope pääsy**
    - Lisää admin emailit `TelescopeServiceProvider@gate()`
    - Deploy uudelleen

20. **Ota käyttöön monitorointi**
    - Coolify Notifications → Discord/Slack
    - Healthcheck alerts

---

## 6. Vianetsintä

### Yleiset ongelmat

#### ❌ Config cache vs .env muuttujat

**Oire:** `env()` palauttaa `null` vaikka muuttuja on asetettu.

**Syy:** Kun `config:cache` on ajettu, `env()` funktio EI lue `.env` tiedostoa!

**Ratkaisu:**
1. Käytä `env()` VAIN config-tiedostoissa
2. Koodissa käytä `config('app.key')` eikä `env('APP_KEY')`
3. Aja `php artisan config:clear` debugatessa
4. Muutoksen jälkeen aina `php artisan config:cache`

```php
// ❌ VÄÄRIN - ei toimi kun config on cachattu
$key = env('CUSTOM_KEY');

// ✅ OIKEIN - käytä aina config()
// config/services.php: 'custom_key' => env('CUSTOM_KEY'),
$key = config('services.custom_key');
```

#### ❌ Queue ei prosessoi jobeja

**Oireet:**
- Jobit jäävät `jobs` tauluun
- Ei virheitä mutta jobit ei etene

**Tarkistukset:**
1. Queue worker container käynnissä?
   ```bash
   docker compose ps
   ```

2. Worker lokitus:
   ```bash
   docker compose logs -f queue
   ```

3. Queue yhteys oikein?
   ```bash
   php artisan queue:work --once -v
   ```

4. Database-driver: onko `jobs` taulu olemassa?
   ```bash
   php artisan migrate:status
   ```

#### ❌ Scheduler ei aja taskeja

**Oireet:**
- Scheduled taskit ei aja
- `schedule:list` näyttää taskit mutta eivät suoriudu

**Tarkistukset:**
1. Scheduler container käynnissä?
2. Cron oikein konfiguroitu?
   ```bash
   # Container sisällä
   crontab -l
   ```

3. Manuaalitesti:
   ```bash
   php artisan schedule:run
   ```

4. Onko taskeja määritelty?
   ```bash
   php artisan schedule:list
   ```

#### ❌ Storage permissions

**Oire:** "Permission denied" virheitä lokeissa

**Ratkaisu:**
```bash
# Container sisällä
chown -R www-data:www-data /var/www/html/storage
chown -R www-data:www-data /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage
chmod -R 775 /var/www/html/bootstrap/cache
```

#### ❌ APP_KEY puuttuu tai väärä

**Oire:** 
- "No application encryption key"
- Session/cookie virheitä

**Ratkaisu:**
1. Generoi uusi avain:
   ```bash
   php artisan key:generate --show
   ```
2. Lisää Coolify env muuttujiin
3. Deploy uudelleen
4. ⚠️ Vanhat sessiot invalidoituvat!

#### ❌ Trusted proxies / HTTPS redirect loop

**Oire:**
- Infinite redirect loop
- Mixed content warnings
- `url()` palauttaa http:// vaikka HTTPS päällä

**Syy:** Laravel ei tiedä että Coolify proxy terminoi HTTPS:n

**Ratkaisu:**

1. Tarkista `app/Http/Middleware/TrustProxies.php`:
```php
class TrustProxies extends Middleware
{
    protected $proxies = '*'; // Luota kaikkiin (Docker verkko)
    
    protected $headers =
        Request::HEADER_X_FORWARDED_FOR |
        Request::HEADER_X_FORWARDED_HOST |
        Request::HEADER_X_FORWARDED_PORT |
        Request::HEADER_X_FORWARDED_PROTO |
        Request::HEADER_X_FORWARDED_AWS_ELB;
}
```

2. Aseta APP_URL oikein:
```env
APP_URL=https://your-domain.com
```

3. Pakota HTTPS `AppServiceProvider`:ssa (Laravel 12):
```php
public function boot(): void
{
    if (config('app.env') === 'production') {
        URL::forceScheme('https');
    }
}
```

#### ❌ OAuth callback virheet

**Oire:** Google OAuth ei toimi, callback URL virhe

**Tarkistukset:**
1. Google Cloud Console → Authorized redirect URIs
   - Lisää: `https://your-domain.com/auth/google/callback`
   
2. Env muuttujat oikein:
   ```env
   GOOGLE_CLIENT_ID=xxx
   GOOGLE_CLIENT_SECRET=xxx
   GOOGLE_REDIRECT_URI=https://your-domain.com/auth/google/callback
   ```

3. APP_URL oikein (ks. yllä)

---

## Lisäresurssit

- [Laravel Deployment Documentation](https://laravel.com/docs/deployment)
- [ServersideUp PHP Docker Images](https://serversideup.net/open-source/docker-php/)
- [Coolify Documentation](https://coolify.io/docs)

