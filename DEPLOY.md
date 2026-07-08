# Deploying to Laravel Cloud

Push‑to‑deploy from a connected Git repository. This app is built to deploy
cleanly with managed **MySQL** and **Laravel Valkey** (Redis‑compatible).

## 1. Push the repo

```bash
git init
git add .
git commit -m "Initial commit: TCL VRF quiz"
git branch -M main
git remote add origin <your-git-remote>
git push -u origin main
```

## 2. Create the application (click‑path)

1. Go to <https://cloud.laravel.com> → **New application**.
2. **Connect repository** → pick this repo and the `main` branch.
3. Choose a **region**.
4. Set the runtime: **PHP 8.4**, **Node 24** (Cloud defaults are fine).
5. **Build commands:**
   ```
   composer install --no-dev --optimize-autoloader
   npm ci && npm run build
   php artisan optimize
   ```
6. **Deploy command:**
   ```
   php artisan migrate --force
   ```
   > Do **not** add `queue:restart`, `optimize:clear`, or `storage:link` here —
   > Cloud handles these or they don't persist on the ephemeral filesystem.

## 3. Attach resources

- Attach a managed **MySQL** database. Cloud auto‑injects the `DB_*` credentials.
- Attach **Laravel Valkey** (Redis‑compatible). Cloud auto‑injects `REDIS_*`.

## 4. Environment variables

Set these in the environment's **Variables** panel:

```dotenv
APP_NAME="TCL VRF Quiz"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.example

# Generate once and paste (or run `php artisan key:generate --show` locally):
APP_KEY=base64:...

# Never use `file` drivers on Cloud (ephemeral, per‑replica filesystem).
# Valkey is recommended; `database` also works.
SESSION_DRIVER=redis
CACHE_STORE=redis
QUEUE_CONNECTION=redis
REDIS_CLIENT=phpredis

# Seeded admin account (see step 6)
ADMIN_NAME="TCL Admin"
ADMIN_EMAIL=admin@your-domain.example
ADMIN_PASSWORD=a-strong-password
```

`DB_*` and `REDIS_*` values are injected automatically for the attached
resources — you don't set them by hand.

If you don't attach Valkey, use `database` for all three drivers instead
(a `sessions`, `cache`, and `jobs` table already ship in the migrations).

## 5. Deploy

Click **Deploy**. The build runs, then `php artisan migrate --force` creates the
schema on the managed MySQL database.

## 6. Seed the data (once)

The deploy command only migrates. Seed the question bank and the admin account
from the Cloud **Console** (or a one‑off command run):

```bash
php artisan db:seed --force
```

This runs both `QuestionSeeder` (idempotent — safe to re‑run after content
changes) and `AdminUserSeeder` (creates/promotes the admin from `ADMIN_*`). To
seed only the questions:

```bash
php artisan db:seed --class=QuestionSeeder --force
```

Updating questions later? Edit `resources/brand/questions and answers.txt`, push,
and re‑run the seeder — or just manage questions in the admin panel at `/admin`.

## Optional toggles (default off for a simple first deploy)

- **Octane (FrankenPHP)** — enable in the Cloud runtime settings for snappier
  responses. No code changes required.
- **Inertia SSR** — if you enable SSR, change the build to `npm run build:ssr`
  and start the SSR server per Cloud's Inertia SSR guidance. Left off by default.

## Post‑deploy smoke check

1. Open `APP_URL` → the branded start screen loads (no scrollbars).
2. Play through: pick answers → winner/loser screen → submit the lead form.
3. Sign in at `/login` with the admin account → `/admin/questions` and
   `/admin/leads` load; **Export CSV** downloads the leads.
