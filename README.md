# TCL VRF Knowledge Challenge

A polished, full‑viewport interactive quiz micro‑site for **TCL VRF** (air‑conditioning)
product knowledge. Branded start screen → one question at a time with instant
right/wrong feedback → an animated winner/loser results screen that captures the
participant's contact details.

Every public screen fills **100% width and 100dvh with no scrolling** at any
breakpoint (mobile, tablet, desktop, and landscape mobile).

## Tech stack

| Layer | Choice |
|---|---|
| Backend | Laravel 13 (PHP 8.4), Eloquent, Form Requests, thin controllers + service layer |
| Frontend | Inertia.js + React 19 + TypeScript (Vite) |
| Styling | Tailwind CSS v4 with a TCL design‑token layer |
| Animation | Framer Motion (transitions/reveals) + canvas‑confetti (winner) |
| Icons / Fonts | lucide-react · self‑hosted Poppins + Inter via `@fontsource` |
| Auth (admin) | Laravel Fortify (login only; a single seeded admin) |
| Database | SQLite (local dev) · MySQL (production, MySQL‑safe migrations) |
| Tests | Pest |

> The official Laravel React starter kit ships React 19 + Fortify; the brief's
> "React 18 / Breeze" notes are satisfied by these current equivalents.

## Local setup

Prerequisites: PHP 8.4, Composer, Node 20+.

```bash
# 1. Install dependencies
composer install
npm install

# 2. Environment
cp .env.example .env
php artisan key:generate

# 3. Set the admin credentials in .env (used by AdminUserSeeder)
#    ADMIN_NAME="TCL Admin"
#    ADMIN_EMAIL=admin@example.com
#    ADMIN_PASSWORD=change-me

# 4. Create the SQLite database and run migrations + seeders
#    (DB_CONNECTION defaults to sqlite)
php artisan migrate --seed

# 5. Run the app (Vite + PHP dev server together)
composer run dev
```

Then open the URL Vite prints (typically <http://localhost:8000>).

### What the seeders do

- **`QuestionSeeder`** parses `resources/brand/questions and answers.txt` via the
  `QuestionBankParser` service and upserts the questions + options. It is
  **idempotent** — safe to re‑run; questions are matched on their prompt and
  options on `(question, label)`.
- **`AdminUserSeeder`** creates/promotes a single admin from the `ADMIN_*` env
  vars. Runs only when `ADMIN_EMAIL` and `ADMIN_PASSWORD` are set.

Re‑seed just the questions at any time:

```bash
php artisan db:seed --class=QuestionSeeder
```

## Admin panel

- Visit **`/admin`** (redirects to `/admin/questions`). You must sign in at
  `/login` with the seeded admin account. Non‑admin users get a 403.
- **Questions**: list, create, edit, delete. Each question edits its four options
  inline and marks exactly one correct (enforced by validation).
- **Leads**: read‑only table of captured contacts with **CSV export**
  (`/admin/leads/export`).

## Application flow

| Route | Screen |
|---|---|
| `GET /` | Start screen (creates an attempt on START) |
| `POST /quiz/start` | Creates a `quiz_attempt`, redirects to question 0 |
| `GET /quiz/{token}/q/{index}` | One question with instant feedback |
| `POST /quiz/{token}/answer` | Records an answer, returns authoritative correctness (JSON) |
| `GET /quiz/{token}/result` | Winner/loser screen + lead‑capture form |
| `POST /quiz/{token}/lead` | Stores the lead (one per attempt), snapshots score/winner |

**Scoring is authoritative server‑side.** The client only sends which option it
picked; correctness, `correct_count` and `is_winner` are computed on the server
and cannot be spoofed by the request payload. `is_winner` is true only when every
question is answered correctly.

## Data model

- `questions` — `prompt`, `explanation?`, `order`, `is_active`
- `answer_options` — belongs to a question; `label`, `text`, `is_correct`, `order`
- `quiz_attempts` — `session_token` (uuid), `total_questions`, `correct_count`,
  `is_winner`, `answers` (json), `completed_at`
- `leads` — `quiz_attempt_id?`, `name`, `email`, `phone?`, `is_winner`, `score`

## Tests

```bash
php artisan test
```

Covers: quiz start creates an attempt; all‑correct → winner, one wrong → not;
score is server‑side and un‑spoofable; answers lock once recorded; lead validation
+ one‑per‑attempt + snapshot; admin CRUD with exactly‑one‑correct enforcement and
the admin gate; and the `QuestionBankParser` (format parsing, correct‑answer
matching by prefix and by exact text, malformed‑block skipping).

## Quality commands

```bash
npm run types:check   # tsc --noEmit
npm run lint          # eslint --fix
npm run format        # prettier --write
./vendor/bin/pint     # PHP formatting
```

## Accessibility & motion

- Custom radio group with `role="radiogroup"`/`radio`, `aria-checked`, arrow‑key
  navigation and visible focus rings.
- White‑on‑red and feedback colours meet AA contrast.
- All non‑essential motion respects `prefers-reduced-motion`.

## Deployment

See [DEPLOY.md](DEPLOY.md) for the Laravel Cloud click‑path.
