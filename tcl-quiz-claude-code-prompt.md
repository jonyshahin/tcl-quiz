# Claude Code Build Prompt — TCL VRF Interactive Quiz Web App

> **How to use this file:** Paste everything below the horizontal rule into Claude Code as your build brief. It is written as a single, self-contained instruction set. Keep the two source assets available in the project root under `resources/brand/`:
> - `Logo_of_the_TCL_Corporation.svg.webp` (the TCL logo)
> - `questions and answers.txt` (the question bank source)

---

## 0. Role & Objective

You are a senior full-stack Laravel engineer and UI/UX designer. Build a **production-ready, single-purpose interactive quiz web application** for **TCL VRF (air-conditioning) product knowledge**. The experience is a polished, fully-animated, **full-viewport** micro-site: a branded start screen → one question at a time with instant right/wrong feedback → an animated winner/loser results screen that captures the participant's contact details.

Deliver clean, tested, documented code and deploy it to **Laravel Cloud**.

**Non-negotiable UX rule:** every screen fills **100% width and 100% height of the viewport with no page scrolling at any breakpoint** (mobile, tablet, desktop). If content risks overflowing on small screens, scale it down / reflow it — never introduce a scrollbar. Use `100dvh` (dynamic viewport height) so mobile browser chrome does not create scroll.

---

## 1. Tech Stack (use exactly this)

- **Backend:** Laravel 12 (PHP 8.4), Eloquent, Form Requests, API resources where useful.
- **Frontend:** Inertia.js + **React 18 + TypeScript**, built with Vite.
- **Styling:** Tailwind CSS (v4) with a small custom design-token layer for the TCL palette.
- **Animation:** **Framer Motion** for page/question transitions and the results reveal; **canvas-confetti** for the winner celebration.
- **Icons:** `lucide-react`.
- **Fonts:** self-hosted via `@fontsource` (see Design System) — do not rely on runtime Google Fonts calls.
- **Database:** MySQL (Laravel Cloud managed MySQL in production; SQLite acceptable for local dev if you prefer, but keep migrations MySQL-safe).
- **Testing:** Pest.
- **Admin auth:** Laravel Breeze (Inertia/React) or Fortify — a single protected admin area. Keep it minimal.
- **Deploy target:** Laravel Cloud (push-to-deploy).

Scaffold with `laravel new` + the official Inertia/React starter (or Breeze Inertia-React). Do not hand-roll the SPA bridge.

---

## 2. Brand & Design System

### 2.1 Colors (derived from the TCL logo)

The TCL logo is a solid red rounded panel with a white "TCL" wordmark. Build the palette around it:

| Token | Hex | Use |
|---|---|---|
| `tcl-red` (primary) | `#E60012` | Primary brand, buttons, start panel, correct-progress |
| `tcl-red-dark` | `#B3000E` | Hover/pressed states, gradients |
| `tcl-red-deep` | `#7A0009` | Dark gradient stops, shadows |
| `tcl-white` | `#FFFFFF` | Wordmark, text on red, card surfaces |
| `ink` | `#1A1A1A` | Body text on light surfaces |
| `mist` | `#F5F6F8` | Light neutral background |
| `success` | `#1DB954` | Correct-answer feedback |
| `danger` | `#E5484D` | Wrong-answer feedback |
| `gold` | `#F5C518` | Winner accents / confetti |

Use a subtle diagonal red gradient (`#E60012 → #B3000E → #7A0009`) as the primary immersive background, with soft radial glows. Keep contrast AA-compliant (white text on red passes; verify with a checker).

### 2.2 Typography

- **Display / headings:** a modern geometric sans — **Poppins** (600/700) or **Montserrat**. 
- **Body / options:** **Inter** (400/500/600).
- Self-host both via `@fontsource/poppins` and `@fontsource/inter`. Fluid type scale using `clamp()` so text never forces overflow on small screens.

### 2.3 Layout primitives

- A single `FullscreenStage` layout component: `w-screen h-[100dvh] overflow-hidden` flex-centered container, with the branded background and an absolutely-positioned watermark logo.
- All content lives in a centered, max-width "card" that scales with viewport, never taller than the stage.
- Respect `prefers-reduced-motion`: gate non-essential motion behind it.

---

## 3. Data Model

Create migrations, models, factories, and seeders for:

### `questions`
- `id`
- `prompt` (text) — the question text
- `explanation` (text, nullable) — optional shown after answering
- `order` (unsigned int, default 0) — display order
- `is_active` (boolean, default true)
- timestamps

### `answer_options`
- `id`
- `question_id` (FK → questions, cascade delete)
- `label` (string) — e.g. "A", "B", "C", "D"
- `text` (text) — the option text
- `is_correct` (boolean, default false)
- `order` (unsigned int)
- timestamps

Exactly one `is_correct = true` per question (enforce in seeder + admin validation).

### `quiz_attempts`
- `id`
- `session_token` (uuid) — ties an anonymous run together
- `total_questions` (int)
- `correct_count` (int)
- `is_winner` (boolean) — true only if `correct_count === total_questions`
- `completed_at` (timestamp, nullable)
- timestamps

### `leads` (contact capture on results page)
- `id`
- `quiz_attempt_id` (FK → quiz_attempts, nullable)
- `name` (string)
- `email` (string)
- `phone` (string, nullable)
- `is_winner` (boolean) — snapshot of the attempt result
- `score` (string) — e.g. "2/2"
- timestamps

Add an index on `leads.email` and `quiz_attempts.session_token`.

---

## 4. Question Source & Seeder

Seed `questions` + `answer_options` from `resources/brand/questions and answers.txt`.

**File format** (repeats per question):
```
<question text>
<blank line>
A) <option A>
B) <option B>
C) <option C>
D) <option D>
<blank line>
<correct answer line — repeats the full correct option, e.g. "A) 10 times faster ...">
<blank line>
```
The final question block may omit a trailing blank line.

**Current content of the file (seed these two questions; the schema must support more):**

1. **What is the key advantage of the CAN bus communication protocol used in TCL VRF compared to traditional systems?**
   - A) 10 times faster and non-polar (not affected by reversing + and - wires) ✅ **correct**
   - B) Functions completely wirelessly without any cables
   - C) Requires complex fiber optic cabling
   - D) Needs a daily manual reboot

2. **What is the ultra-wide outdoor operating temperature range for the TCL VRF system during cooling?**
   - A) From 0°C to 40°C only
   - B) From -5°C to 56°C ✅ **correct**
   - C) From 20°C to 45°C
   - D) It stops working above 48°C

Write a robust parser (`QuestionSeeder` + a small `QuestionBankParser` service) that:
- Splits blocks, extracts the prompt, the four `A)–D)` options, and the correct-answer line.
- Matches the correct-answer line to an option by its `A)/B)/C)/D)` prefix (fall back to exact text match) and sets `is_correct`.
- Is **idempotent** (safe to re-run: match on prompt, upsert options).
- Skips malformed blocks with a logged warning rather than crashing.

The seeder is also the mechanism the "Database, seed-from-file" flow uses, but questions are primarily managed via the admin panel afterward.

---

## 5. Application Flow & Pages

All public pages are Inertia/React screens rendered inside `FullscreenStage`. **No scrolling anywhere.** Transitions between screens use Framer Motion (`AnimatePresence`, slide/fade, ~350–500ms, eased).

### 5.1 Start screen — `GET /`
- Centered TCL logo (animated entrance: fade + slight scale/spring).
- A short title/tagline (e.g. "TCL VRF Knowledge Challenge").
- A large primary **START** button (red, white text, hover lift, subtle pulse to draw attention).
- Clicking START creates a new `quiz_attempt` (server-side, returns `session_token`) and navigates to the first question.
- Full-bleed branded gradient background with animated ambient glow.

### 5.2 Question screen — `GET /quiz/{token}/q/{index}`
- Slim **progress indicator** at top (e.g. "Question 2 of 2" + a segmented/animated progress bar in TCL red).
- The question prompt, prominent and readable.
- The four options rendered as **custom radio buttons / selectable cards** (not raw browser radios): large tap targets, keyboard-accessible, clear focus rings.
- **Instant check on selection:** the moment the user picks an option, evaluate correctness **immediately** (client shows state instantly; confirm/record via a lightweight POST to the server so scoring can't be trivially gamed):
  - Correct → option turns `success` green with a check icon + a small positive micro-animation.
  - Wrong → chosen option turns `danger` red with an X + a shake micro-animation, **and** the correct option is highlighted green.
  - Once answered, options lock (no changing the answer).
  - Optionally reveal `explanation` text if present.
- A **NEXT** button appears/enables only after an answer is chosen.
  - On the last question, the button reads **SEE RESULTS** / **FINISH** and routes to the results screen.
- Each question entrance/exit is animated (slide-in from right, out to left).
- Persist per-question correctness to the attempt (server increments `correct_count`).

### 5.3 Results screen — `GET /quiz/{token}/result`
- Compute `is_winner = (correct_count === total_questions)` (**all correct = winner**, per spec).
- **Winner state:** celebratory — confetti burst (canvas-confetti), gold/red palette, a spring-scaling trophy/crown (lucide), congratulatory headline, animated score (e.g. count-up to "2/2").
- **Loser state:** encouraging, not punishing — tasteful animation (e.g. gentle fade + a "Try again" energy), the score, and a "Play again" path. Keep it on-brand and positive.
- **Lead-capture form on this screen** (per spec — capture happens on the results page):
  - Fields: **Name (required)**, **Email (required, validated)**, **Phone (optional)**.
  - Submit → POST creates a `leads` row linked to the `quiz_attempt`, snapshotting `is_winner` and `score`.
  - Validate with a Form Request; show inline field errors; success state (thank-you micro-animation) after submit.
  - Gracefully handle already-submitted (one lead per attempt).
- A **Play Again** button resets to the start screen (new attempt).
- Everything fits one viewport — on mobile the form and celebration must coexist without scroll (compact layout, reduce confetti density on small screens).

### 5.4 Admin panel — `/admin` (auth-protected)
- Login via Breeze/Fortify (single admin account seeded from env — document the credentials setup).
- **Questions CRUD:** list (with drag-or-number ordering + active toggle), create, edit, delete. Each question edits its 4 options inline and marks the correct one (radio; enforce exactly one correct).
- **Leads view:** read-only table of captured leads (name, email, phone, winner, score, date) with **CSV export**.
- Keep the admin UI clean and functional (Tailwind), it does not need the full-screen no-scroll treatment — normal scrollable admin layout is fine.

---

## 6. Routes / Controllers (suggested)

Public (Inertia):
- `GET /` → `StartController@show`
- `POST /quiz/start` → creates attempt, returns token, redirects to Q1
- `GET /quiz/{token}/q/{index}` → `QuizController@question`
- `POST /quiz/{token}/answer` → `QuizController@answer` (records correctness, returns feedback)
- `GET /quiz/{token}/result` → `QuizController@result`
- `POST /quiz/{token}/lead` → `LeadController@store` (Form Request validated)

Admin (Inertia, `auth` middleware):
- Resource routes for `questions`
- `GET /admin/leads` + `GET /admin/leads/export`

Guard against tampering: validate the `token` exists and isn't already completed where appropriate; compute score server-side, never trust a client-sent score.

---

## 7. Animation & Motion Spec (make it feel premium)

- **Screen transitions:** `AnimatePresence` with directional slide + fade; spring or `easeInOut`, 350–500ms.
- **Start button:** idle pulse/glow, hover lift + shadow, tactile press (scale 0.97).
- **Option select:** spring pop on select; shake on wrong; check-draw on correct.
- **Progress bar:** animated width fill in TCL red as the user advances.
- **Winner:** confetti + trophy spring-in + score count-up + subtle background shimmer.
- **Loser:** calm fade/scale, encouraging copy, no harsh red flashing.
- **Reduced motion:** respect `prefers-reduced-motion` — swap big motions for simple fades.
- Everything GPU-friendly (transform/opacity), 60fps target, no layout thrash.

---

## 8. Responsiveness & "No Scroll" Requirements (test explicitly)

- Every public screen: `w-screen h-[100dvh] overflow-hidden`.
- Fluid typography and spacing via `clamp()`; scale the central card with viewport.
- Test at minimum: 360×640 (small mobile), 390×844 (iPhone), 768×1024 (tablet), 1366×768 and 1920×1080 (desktop). **No scrollbars at any of these.**
- Handle landscape mobile (short height) — the two-question quiz and the results form must still fit; compact the layout in landscape.
- Touch targets ≥ 44px; keyboard navigable; visible focus states.

---

## 9. Accessibility & Quality

- Semantic HTML, ARIA on the custom radio group (`role="radiogroup"`/`radio`, `aria-checked`).
- Full keyboard flow (Tab/Arrow keys to choose, Enter to advance).
- AA color contrast (verify white-on-red and feedback states).
- Alt text on the logo.
- Lighthouse: aim ≥ 90 on Performance, Accessibility, Best Practices.

---

## 10. Testing (Pest)

- Feature test: starting a quiz creates an attempt.
- Feature test: answering all correct → `is_winner = true`; one wrong → `false`.
- Feature test: score is computed server-side and cannot be spoofed by the client payload.
- Feature test: lead submission validates name/email, stores snapshot, one lead per attempt.
- Unit test: `QuestionBankParser` correctly parses the provided `.txt` format, including the correct-answer matching and malformed-block handling.
- Admin: questions CRUD enforces exactly one correct option.

---

## 11. Laravel Cloud Deployment

Target **Laravel Cloud** (cloud.laravel.com), push-to-deploy from the connected Git repo.

- **PHP 8.4**, **Node 24** (Cloud defaults).
- **Build commands:**
  ```
  composer install --no-dev --optimize-autoloader
  npm ci && npm run build
  php artisan optimize
  ```
- **Deploy command:**
  ```
  php artisan migrate --force
  ```
  (Do **not** put `queue:restart`, `optimize:clear`, or `storage:link` in deploy commands — Cloud handles or these don't persist on the ephemeral filesystem.)
- **Resources:** attach a managed **MySQL** database; attach **Laravel Valkey (Redis-compatible)** and set `CACHE_STORE`, `SESSION_DRIVER`, and `QUEUE_CONNECTION` appropriately (use `database` or `redis` — never `file`, since the filesystem is ephemeral and per-replica).
- **Env vars:** `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL`, `APP_KEY` (generate), admin credentials, mail (if lead notifications are added later). Cloud auto-injects DB/Redis credentials for attached resources.
- **Seeding in production:** run the question seeder once via a deploy command guarded to be idempotent, or via the Cloud console (`php artisan db:seed --class=QuestionSeeder`). Document the exact step.
- Consider enabling **Octane (FrankenPHP)** for snappier responses; if you enable **Inertia SSR**, change the build to `npm run build:ssr`. Note both as optional toggles in the README, default off to keep the first deploy simple.
- Provide a short `DEPLOY.md` with the click-path: New application → connect repo → choose region → set PHP/Node → paste build/deploy commands → attach MySQL + Valkey → set env vars → Deploy → run question seeder.

---

## 12. Deliverables & Acceptance Criteria

Deliver a running app plus:
- Clean, typed React components; organized Laravel code (controllers thin, logic in services/actions).
- Seeded question bank from the provided file, matching the two questions above.
- A `README.md` (local setup, seeding, admin login) and a `DEPLOY.md` (Laravel Cloud steps).
- Passing Pest suite.

**Definition of done:**
1. Start screen fills the viewport with the TCL logo + START button, on-brand red, no scroll.
2. Questions show one at a time, custom radio options, **instant** correct/wrong feedback, NEXT advances, animated transitions.
3. Final question leads to an **animated winner/loser** screen; **all-correct = winner** with confetti; loser state is encouraging.
4. Results screen captures **name/email/phone** into `leads`, snapshotting the score/winner status.
5. Admin can manage questions and view/export leads.
6. Fully responsive, full-bleed, **zero scrollbars** at all tested breakpoints (incl. landscape mobile), reduced-motion respected.
7. Deploys cleanly to Laravel Cloud with MySQL + Valkey and the documented build/deploy commands.

---

## 13. Build Order (work in these milestones, verify each before moving on)

1. Scaffold Laravel 12 + Inertia/React/TS + Tailwind + Framer Motion; wire design tokens and fonts; build `FullscreenStage`.
2. Migrations + models + factories; `QuestionBankParser` + `QuestionSeeder`; unit-test the parser against the provided file.
3. Start screen + attempt creation.
4. Question screen with instant feedback + progress + animated transitions + server-side answer recording.
5. Results screen (winner/loser animations + confetti) + lead-capture form + validation.
6. Admin (auth + questions CRUD + leads table + CSV export).
7. Responsive/no-scroll pass across all breakpoints + accessibility + reduced-motion.
8. Pest suite green.
9. Laravel Cloud deploy + `DEPLOY.md`.

Ask me only if a decision is truly blocking; otherwise choose sensible, on-brand defaults and keep moving.
