# Rewrite: Laravel + React (strict TS) + Docker + better search

Date: 2026-10-27 · Branch: `refactor/laravel-react` · Status: approved by owner (2026-11-03)

## 1. Why

"Hình Sự Tra Cứu" helps ordinary people describe a situation in everyday Vietnamese and get the relevant article of the Vietnamese Criminal Code, the penalty, and the victim's rights to compensation. It also has personal notes, accounts and display preferences.

Today it is 12 static HTML pages plus 13 plain JS files with no build step. Accounts and notes are faked in each browser's `localStorage` (`js/db.js`). A half-started PHP backend (`backend/api/law.php`) only stores chatbot history. The team was partway through moving to PHP + MySQL on XAMPP. The owner finds the structure unmaintainable and the look poor (fonts, layout), and wants:

- a proper framework and strict TypeScript on the frontend,
- Docker in place of XAMPP, one setup that runs the same on any hardware, for local dev and for real deployment,
- a cleaner visual design,
- better search results.

The team is free to change the stack. Nobody else is editing the frontend right now, so this branch can replace it wholesale.

## 2. Goals

1. **Stack (owner's choice):** Laravel (PHP) backend; React frontend. Builder's choice within that: Laravel's **official React starter kit**: Laravel + Inertia.js + React + TypeScript + Vite + Tailwind CSS + shadcn/ui. Use the latest stable Laravel release and the starter kit version that ships with it.
2. **Strict TypeScript:** `"strict": true`, plus `noUncheckedIndexedAccess`, `noImplicitOverride`, `noFallthroughCasesInSwitch`. No `any` (ESLint `@typescript-eslint/no-explicit-any: error`). No `.js`/`.jsx` source files under `resources/js`. `tsc --noEmit` must pass in CI/compose.
3. **Real backend:** users, notes, preferences, contact messages, chat history and law data live in MySQL through Laravel (Eloquent, migrations, seeders). `localStorage` stays only for trivial UI conveniences, if at all.
4. **Docker, universal:** `docker compose up` starts everything. Images build for `linux/amd64` and `linux/arm64`. The same compose files serve dev and production (dev adds an override).
5. **Visual design: "A. Clean modern"** (picked by the owner from mockups): neutral grays, near-black primary, white cards with 1px light borders, rounded corners (~8–10px), pill tags, Inter font (full Vietnamese diacritic support). In practice this is shadcn/ui's default "neutral" theme. Light and dark mode both supported.
6. **Better search** (§6): measured against a larger evaluation set, combining the existing hand-tuned scorer with semantic (meaning-based) matching from a small multilingual embedding model.

## 3. Non-goals

- No new end-user features beyond what the current pages do (listed in §5).
- **No password recovery and no email verification for now** (owner: no email service yet). Remove the starter kit's forgot/reset-password and verify-email routes and pages, and send no mail. Keep the code easy to add back later; there is no mail service anywhere in the stack.
- No admin panel UI. `API_CONTRACT.md` §2.5 describes one but it was never built; leave it out.
- No change to the law content itself (the 18 articles and their clauses in `data/dieu_luat_data.php`).
- No SPA framework beyond React/Inertia; no separate API server; no GraphQL.
- No paid external AI APIs. The embedding model is self-hosted.
- Not merging to `main` as part of this work; the owner decides that after review.

## 4. Anti-goals (would be a failure even if it "works")

- Search gets **worse** on any of the existing 19+ test questions in `tests/scorer-test.js`.
- Vietnamese text breaks anywhere: diacritics, unaccented input (`toi bi danh`) no longer matching, wrong collation (must be `utf8mb4`).
- The site fails to start on ARM (e.g. Apple Silicon, Raspberry Pi 5, Graviton) or needs manual steps beyond `cp .env.example .env && docker compose up`.
- Search stops working entirely when the embedding service is down. It must fall back to the lexical scorer.
- TypeScript "strict" in name only (`any`, `@ts-ignore`, `as unknown as` sprinkled to silence errors).

## 5. Feature inventory to carry over

UI language stays **Vietnamese**. Routes can be English slugs or Vietnamese; builder's call, but keep them consistent.

| Current page | Becomes | Notes |
|---|---|---|
| `login`, `register` | starter kit auth pages, restyled | Login by email **or** username. Lockout after 5 failed attempts (Laravel's throttle). |
| `forgot-password` | **Dropped for now** | Owner: no email service yet (§3). The "activate email" banner goes too. |
| `index` (notes) | Notes page (home after login) | CRUD, delete confirmation, pin, real-time filter, color choice; pinned first then newest; limits from §5.1. |
| `tra-cuu` | Law search | Search by description, filter by chapter (`chuong`), results show score and penalty range. |
| `dieu-luat-chi-tiet` | Article detail | Clauses (`khoan`), victim rights, compensation basis, related articles. |
| `tu-van` | Chat advisor | Same answer shape as today's `Law.answer()` (overview vs. specific clause, role-dependent rights, follow-up question, "save as note"). Each question is logged to `lich_su_hoi_dap` history. |
| `ho-so` | Profile | Display name, avatar upload (resize to 256×256 JPEG server-side), delete account. |
| `doi-mat-khau` | Change password | |
| `tuy-chinh` | Settings | Theme (light/dark/system), font size (sm/md/lg/xl), default note color; stored per user in DB. |
| `gioi-thieu`, `lien-he` | About, Contact | Contact form saves to `contacts` table. |
| `database.html` | **Dropped** | It was a viewer for the fake localStorage DB; meaningless with a real DB. |

Access: everything except auth pages, About and Contact requires login (same as today).

**Demo account:** `demo` / `12345` is seeded as a real user, flagged `is_demo`: it cannot change name, avatar or password, or be deleted (today's behaviour, enforced server-side). Its password is shorter than the normal 8-char rule; the seeder bypasses validation.

### 5.1 Validation rules (from `docs/API_CONTRACT.md`, keep them)

email ≤254 lowercased · displayName 2–40 · password 8–72 with letters and digits · note title ≤120, content ≤5000, not both empty, max 200 notes/user · contact: name 2–60, valid email, optional phone `^\+?[0-9 .\-]{8,15}$`, message 10–2000 · avatar JPG/PNG/WebP ≤2MB ≥64×64.

## 6. Search

### 6.1 Where it runs

Search moves **server-side into Laravel**. The browser no longer downloads the law data or the scorer. The current `js/scorer.js` (concept dictionary + BM25 + Levenshtein), `js/nlu.js` and the answer-building logic in `js/law.js` are **ported to PHP** as a service class with the same behaviour. The frontend just calls it via Inertia/JSON. (This replaces the earlier idea of porting the scorer to TypeScript.)

### 6.2 Evaluation set first

Before changing the algorithm:

1. Port `tests/scorer-test.js` cases to a PHP test and confirm the PHP port reproduces today's results exactly. This is the baseline.
2. Grow the evaluation set to **~150 questions** in a data file (e.g. `tests/fixtures/search-eval.json`: question, expected article id, optional acceptable alternates, optional expected clause). Cover: everyday wording, no diacritics, typos, slang/teen-code, long rambling stories, questions that name two crimes, and a few that should return "not found".
3. **The builder writes the questions and the expected labels; they are final** (owner's decision). Mark the held-out third before tuning starts.
4. An artisan command (e.g. `php artisan search:eval`) prints **top-1 accuracy**, **top-3 accuracy**, and the failures, for the lexical scorer alone, embeddings alone, and the blend.

### 6.3 Semantic matching

- An **embedding service** container: a small Python (FastAPI) app serving `intfloat/multilingual-e5-small` via `sentence-transformers`, CPU only, built for amd64 + arm64. Budget about 1 GB RAM (owner approved). The model is downloaded at image build time so the container works offline.
- Each article and clause gets an embedding (title + summary + keywords + clause text) computed by an artisan command and stored in MySQL (JSON or binary column). With 18 articles there is no need for a vector database; cosine similarity in PHP is fine.
- At query time Laravel embeds the question (one HTTP call, short timeout ~1.5s) and blends: `final = w_lex · lexical + w_sem · semantic`, weights tuned on the eval set. The concept dictionary keeps priority for phrases it knows.
- **Fallback:** if the embedding service is unreachable or times out, use lexical only and log a warning. Search must never error because of it.

- **Chat uses the blend too** *(author's call)*. The search page and the chat advisor share one ranking service, as they share `Scorer.rank` today. The "not found" and "ambiguous" cut-offs get retuned for the blended score, and the follow-up and conversation tests from `tests/scorer-test.js` (62 checks today) are ported and must pass against the blend, not only the top-1 checks.
- **Avoid grading our own homework** *(author's call)*: about a third of the eval set is held out and never used for weight tuning; reported numbers are on the held-out part. Scoring rule: a two-crime question counts as correct if the expected article is top-1 (top-3 metric: any listed alternate within top 3); a "not found" question is correct only if the system returns not-found.

### 6.4 Done means

- All original test questions still pass (anti-goal 1).
- The blend's top-1 and top-3 on the 150-question set are **≥ the lexical baseline**, with numbers recorded in the PR/README.

## 7. Docker

Services in `compose.yaml`:

| Service | Image basis | Purpose |
|---|---|---|
| `app` | PHP 8.x FPM (official image) + extensions (pdo_mysql, gd/imagick for avatars, intl, mbstring) | Laravel |
| `web` | nginx | serves `public/` and built assets, proxies PHP to `app` |
| `db` | MySQL 8.4 LTS | data; named volume; `utf8mb4_unicode_ci` |
| `embedder` | python slim + model baked in | §6.3 |
| `queue` *(optional, builder's call)* | same image as `app` | only if embedding jobs need it |

- **Multi-stage `Dockerfile`:** Node stage runs `npm ci && npm run build`; Composer stage installs PHP deps (`--no-dev` for prod); final stage is PHP-FPM with only what's needed.
- **`compose.override.yaml` (dev, auto-loaded):** bind-mounts source, runs the Vite dev server with HMR, exposes MySQL on localhost.
- **Production (owner: no domain yet):** the server clones the repo and runs `docker compose -f compose.yaml up -d --build`. Images are built on the server itself (`build:` in compose); no registry, no CI pipeline. nginx serves plain HTTP on a configurable port (default 80). HTTPS is out of scope for now and will come from a proxy placed in front later; `APP_URL` and trusted proxies are configurable via `.env` so that needs no code change.
- **First start:** an entrypoint waits for MySQL, runs `php artisan migrate --force`, and seeds law data + demo user only if empty. Embeddings get generated on first start or lazily on first search.
- **Config:** `.env.example` documents all variables; `.env` is gitignored. `APP_KEY` must be generated on first setup (documented, or done by entrypoint if empty).
- **Multi-arch:** document a `docker buildx build --platform linux/amd64,linux/arm64` command. Every base image used must publish both architectures.
- **ARM proof** *(author's call)*: build both architectures with buildx (QEMU emulation is acceptable even if slow) and boot the arm64 stack under emulation at least once; record the result.
- The host is headless (a VM); dev ports must be reachable via SSH port forwarding. Document which ports (web, Vite).

## 8. Repository layout

The Laravel app lives at the **repo root** (standard Laravel layout: `app/`, `resources/js/` with `pages/`, `components/`, `layouts/`, `lib/`, `types/`, `routes/`, `database/migrations|seeders`, `tests/`). Embedding service under `docker/embedder/`.

Removed from the branch: `html/`, `js/`, `css/`, `index.html`, `backend/`, `tools/export_law.php`, `tests/run-in-browser.html`, `tests/e2e.py`, `tests/db-test.js`. Law source data `data/dieu_luat_data.php` moves to the seeder (e.g. `database/data/`). Hand-written SQL (`database/schema.sql`, `seed*.sql`, `migrations/*.sql`) is replaced by Laravel migrations/seeders, which **keep the same table and column names** (`dieu_luat`, `khoan`, `chuong`, `lich_su_hoi_dap`, …) where reasonable, so `docs/DATABASE.md` stays mostly true.

Docs: rewrite `README.md` (how to run with Docker, demo account, tests) and `docs/ARCHITECTURE.md`. Update `docs/API_CONTRACT.md` and `docs/DATABASE.md` to match. Leave team-process docs (`TASK_BOARD`, `TIMELINE`, `FILE_OWNERSHIP`, `PROJECT_AUDIT`, `TESTING_CHECKLIST`) untouched apart from a note at the top of the ones that are now outdated.

## 9. Testing

- **PHP:** Pest (starter kit default). Feature tests for auth, demo-account locks, notes rules (limit, ownership), contact, preferences, search service, answer builder.
- **TS:** `tsc --noEmit`, ESLint, and Vitest for any non-trivial frontend logic (e.g. note filtering, form helpers).
- **Search:** baseline parity test + `search:eval` (§6.2).
- All tests runnable inside Docker with one documented command each.

## 10. Order of work (suggested)

1. Laravel starter kit scaffold + Docker (app, web, db) running on both arches.
2. Migrations, models, seeders (law data, demo user).
3. Port scorer + answer logic to PHP; baseline parity tests.
4. Pages, one by one, in the clean-modern style.
5. Eval set → embedder service → blend + tune → record numbers.
6. Docs, cleanup of old files.

## 11. Left to the builder

Folder naming inside `resources/js`, component breakdown, route names, exact Tailwind tokens within the shadcn neutral theme, whether to use a queue worker, how embeddings are stored, blend weights (tuned on data), error page design.
