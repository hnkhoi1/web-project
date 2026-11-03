# Laravel + React Rewrite Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the static HTML/JS site with a Dockerized Laravel + Inertia + React (strict TS) app, in the "Clean modern" look, with server-side search that blends the existing scorer with a self-hosted embedding model.

**Architecture:** One Laravel app at the repo root serves React pages through Inertia. Search is a PHP service (`app/Search`) ported from the old JS with golden-file parity, then blended with vectors from a small FastAPI `embedder` container, falling back to lexical when it is unavailable. Docker Compose runs `web` (nginx), `app` (PHP-FPM), `db` (MySQL), `embedder`, and a one-shot `setup`.

**Tech Stack:** Laravel 13, PHP 8.4, Fortify, Inertia 3, React 19, TypeScript, Tailwind 4, shadcn/ui, Vite+ (`vp`), pnpm, Wayfinder, PHPUnit, MySQL 8.4, Python + FastAPI + sentence-transformers (`intfloat/multilingual-e5-small`).

**Spec:** `docs/superpowers/specs/2026-10-27-laravel-react-rewrite-design.md`

## Global Constraints

- Work only on branch `refactor/laravel-react`. Never push or merge; the owner decides that.
- All PHP, Node and Composer commands run through Docker. The host has only Docker, a bare `node`, and no `php`, `npm` or `composer`.
- All UI copy stays in Vietnamese. MySQL uses `utf8mb4` / `utf8mb4_unicode_ci`.
- TypeScript: `"strict": true`, `noUncheckedIndexedAccess`, `noImplicitOverride`, `noFallthroughCasesInSwitch`, `allowJs: false`. No `any`, `@ts-ignore`, or `as unknown as`. No `.js`/`.jsx` source under `resources/js`.
- Validation: email ≤254, lowercased · display name 2–40 · password 8–72 with letters and digits · note title ≤120, content ≤5000, not both empty, max 200 notes/user · contact name 2–60, valid email, optional phone `^\+?[0-9 .\-]{8,15}$`, message 10–2000 · avatar JPG/PNG/WebP ≤2MB, ≥64×64.
- Visual: shadcn "neutral" theme, near-black primary, white cards with 1px light borders, radius ~8–10px, pill tags, Inter (Vietnamese subset), light and dark.
- Not built: password recovery, email verification, mail service, 2FA, passkeys, admin panel, CI, registry, HTTPS, paid AI APIs. Law content does not change.
- Never commit `.env`. Commit messages end with: `Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>`.

**Where the plan departs from the spec, because the current starter kit differs:**

| Spec says | Plan does | Why |
|---|---|---|
| Pest "starter kit default" | PHPUnit 12 | The kit now ships PHPUnit |
| `npm ci` | pnpm | The kit ships pnpm config |
| ESLint | `vp check` (Oxlint) with `typescript/no-explicit-any: error`, plus Vite+'s test runner (Vitest) | The kit's toolchain is Vite+ |
| Node build stage | Build stage has PHP + Node | The Wayfinder Vite plugin runs `artisan` during builds |
| `password_hash`, `display_name` columns | Kit's `password`, `name` | Fortify depends on them; other tables keep their names |

**Decisions the spec left open:**

- No queue worker.
- Avatars stay as a data URI in `users.avatar` (`MEDIUMTEXT`, as in today's schema), so no shared file volume is needed.
- The kit is pinned to commit `98d9b821baba4f4cc4120c96d35971366cec53e6`.
- Tests run on a real MySQL database, `hinh_su_test`.
- Pending chat context lives in the server session.
- `Scorer.explain` / `debug` is not ported. The chat UI never rendered it.
- **Role-detection bug fix.** In the old JS, `/\bbị\b/` never matches because `\b` is ASCII-only, so `tôi bị đánh` returns `chua_ro` instead of `nan_nhan` and victims don't see their rights first. The PHP port uses `/u` and returns `nan_nhan`, as the code comments intended. Golden parity therefore excludes `role`, and role has its own tests.

## Prerequisites (owner action, before Task 1)

- **Free disk: at least 15 GB.** The VM has 2.0 GB free, which is not enough for the images (the embedder alone is about 2 GB). Task 1 Step 1 stops if this is unmet.
- The docker group, buildx 0.13 and Compose 2.26 are already in place.

## Review Focus

- **Decomposed Vietnamese input (NFD, as macOS produces) or mixed case** in search, chat and login should behave exactly like the composed form. Tests: Tasks 2, 4.
- **Embedder down, slow, or returning the wrong dimension, or vectors missing or from another model** should give normal lexical results and original thresholds, never a 500. Test: Task 13.
- **A crafted request against another user's note, or against the demo account's locked actions,** should be refused server-side even with no UI path to it. Tests: Tasks 6, 7.
- **Bad avatars: a text file renamed `.jpg`, a 3 MB file, a 32×32 image, a transparent PNG, an EXIF-rotated photo** should give a Vietnamese validation error or a correct upright JPEG, never a 500 or an nginx 413 page. Tests: Tasks 1 (limits), 6.
- **Restarting: `down` then `up`, `setup` running twice** should keep data, keep `APP_KEY`, not duplicate seeds, and not reset the demo account. Tests: Tasks 3, 14.

---

### Task 1: Import starter kit and dev Docker stack

**Files:**
- Create: everything from the kit at the repo root (except its `README.md`, `.github/`, `chisel.php`, `chisel-paths.php`), `Dockerfile`, `compose.yaml`, `compose.override.yaml`, `docker/nginx/default.conf`, `docker/php/php.ini`, `docker/php/entrypoint.sh`, `docker/mysql/create-test-db.sql`, `.dockerignore`, `.env.example`
- Modify: `.gitignore` (kit's rules + `.superpowers/`), `composer.json` (remove `laravel/chisel`, `laravel/sail`), `vite.config.ts`, `phpunit.xml`

**Interfaces:**
- Produces: `docker compose exec app php artisan …`, `docker compose exec vite pnpm …`; services `web`, `app`, `db`, `vite`; test DB `hinh_su_test`.

- [ ] **Step 1: Check disk.** Run `df -h --output=avail . | tail -1`. Expected: ≥15G. If not, stop and report to the owner.

- [ ] **Step 2: Import the kit.** Clone `https://github.com/laravel/react-starter-kit` into the scratchpad, check out the pinned commit, and copy its tree into the repo root with `git archive | tar -x`. Do not overwrite `README.md`, `docs/`, or old `tests/*.js` and `tests/e2e.py`. Merge `.gitignore`. Delete the chisel files and all `@chisel-*` marker comments.

- [ ] **Step 3: Write `Dockerfile`** with stages:
  - `base`: `php:8.4-fpm-bookworm` with `pdo_mysql`, `intl`, `gd` (jpeg, webp, freetype), `opcache`.
  - `vendor`: Composer install.
  - `assets`: base + `/usr/local` copied from `node:22-bookworm-slim` + vendor + `corepack enable`, `pnpm install --frozen-lockfile`, `pnpm build` with a throwaway `APP_KEY`.
  - `dev`: base + Composer + Node, runs as `${UID:-1000}`.
  - `prod`: code, `--no-dev` vendor, built assets, `php.ini`.
  - `web`: `nginx` image with `public/`, including built assets.

  All base images must publish amd64 and arm64.

- [ ] **Step 4: Set upload limits.** `php.ini`: `upload_max_filesize=3M`, `post_max_size=4M`. nginx: `client_max_body_size 4m`. A 3 MB file must reach Laravel validation.

- [ ] **Step 5: Write `compose.yaml`** with `web` (port `${WEB_PORT:-80}:80`), `app` (`prod` target), and `db` (`mysql:8.4`, named volume `db_data`, `--character-set-server=utf8mb4 --collation-server=utf8mb4_unicode_ci`, healthcheck). Credentials come from `.env` via `${DB_PASSWORD:?}`.

- [ ] **Step 6: Write `compose.override.yaml`** (auto-loaded):
  - `app` and `web` use dev targets with bind-mounted source. `vendor` and `node_modules` are named volumes.
  - `vite` service runs `pnpm install && pnpm dev --host 0.0.0.0` on port 5173, with HMR host from `VITE_HMR_HOST` (default `localhost`).
  - `db` publishes `127.0.0.1:3306` and mounts `create-test-db.sql`, which creates `hinh_su_test` and grants access.

- [ ] **Step 7: Write `.env.example`** with working defaults: `DB_*`, `DB_CONNECTION=mysql`, `SESSION_DRIVER=database`, `CACHE_STORE=database`, `QUEUE_CONNECTION=sync`, `MAIL_MAILER=log`, empty `APP_KEY`, `LOCALE`/`APP_LOCALE=vi`, `WEB_PORT`, `TRUSTED_PROXIES`. Point `phpunit.xml` at MySQL `hinh_su_test` (host `db`).

- [ ] **Step 8: Write `docker/php/entrypoint.sh`.** If `APP_KEY` is empty, read it from the named volume `app_key` (`/run/app-key/key`), or generate and store it there first. Then export it. All PHP containers mount this volume.

- [ ] **Step 9: Bring up the stack.** Run `cp .env.example .env && docker compose up -d --build`, `docker compose exec app composer remove laravel/chisel laravel/sail`, `docker compose exec app php artisan key:generate --show` (must print a key, proving the app boots), then `docker compose exec app php artisan migrate`. Expected: `pnpm-lock.yaml` and `composer.lock` exist, and `curl -s -o /dev/null -w '%{http_code}' localhost/` prints `200`.

- [ ] **Step 10: Run the kit's tests.** `docker compose exec app php artisan test`. Expected: all pass, on MySQL.

- [ ] **Step 11: Commit.** `chore: import Laravel React starter kit and dev Docker stack`

### Task 2: Trim the kit; strict TS, Inter, accounts and demo user

**Files:**
- Modify: `config/fortify.php`, `app/Providers/FortifyServiceProvider.php`, `app/Providers/AppServiceProvider.php`, `app/Actions/Fortify/CreateNewUser.php`, `app/Models/User.php`, `database/migrations/0001_01_01_000000_create_users_table.php`, `routes/web.php`, `routes/settings.php`, `tsconfig.json`, `vite.config.ts`, `resources/css/app.css`, `resources/js/pages/auth/{login,register}.tsx`
- Delete: 2FA, passkey, forgot/reset/verify/confirm-password pages, their components, hooks, tests and migrations; `app/Actions/Fortify/ResetUserPassword.php`
- Create: `database/seeders/DemoUserSeeder.php`, `tests/Feature/Auth/LoginIdentifierTest.php`, `tests/Feature/Auth/RegistrationRulesTest.php`, `tests/Feature/Auth/RecoveryRemovedTest.php`

**Interfaces:**
- Produces: `users` columns `id, username (nullable unique), name, email, password, avatar (mediumText nullable), is_demo (bool)`; `User::isDemo(): bool`; login field named `login`; `DemoUserSeeder` (idempotent; username `demo`, email `demo@demo.local`, name `Tài khoản Demo`, password `12345`, `is_demo=true`); post-login home route `notes.index` at `/ghi-chu` (temporary inertia stub until Task 7).

- [ ] **Step 1: Write failing tests.**

```php
// LoginIdentifierTest (demo seeded)
test_logs_in_by_username: POST /login {login:'demo', password:'12345'} → authenticated
test_username_ignores_case_and_spaces: {login:'DEMO ', password:'12345'} → authenticated
test_logs_in_by_email_case_insensitive: {login:'Demo@Demo.local', ...} → authenticated
test_wrong_password_rejected: {login:'demo', password:'sai'} → guest, error on 'login'
test_locked_after_five_failures: 5 wrong, then correct → guest with throttle error
test_decomposed_username_matches: a user with username 'tuấn' logs in with {login: Normalizer::normalize('tuấn', Normalizer::FORM_D), ...}

// RegistrationRulesTest
test_registers_and_logs_in_with_lowercased_email: 'A@X.com' stored as 'a@x.com', authenticated, redirected to /ghi-chu
rejects duplicate email regardless of case; name 'A' and 41 chars; password 'abcdefgh', '12345678', 'abc1234', and 73 chars

// RecoveryRemovedTest
GET /forgot-password, /reset-password/x, /email/verify, /user/confirm-password → 404
migrating never creates passkeys table; users has no two_factor_secret column
```

- [ ] **Step 2: Run.** `docker compose exec app php artisan test --filter 'LoginIdentifier|RegistrationRules|RecoveryRemoved'`. Expected: FAIL.

- [ ] **Step 3: Implement backend.**
  - `config/fortify.php`: `'username' => 'login'`, `'home' => '/ghi-chu'`, features = `registration()` only.
  - `Fortify::authenticateUsing`: NFC-normalize, trim, lowercase the identifier; match `email` or `username`.
  - `Password::defaults`: `min(8)->max(72)->letters()->numbers()`.
  - `name`: `min:2|max:40`, reject control characters.
  - Drop `verified` from all route middleware.
  - Remove the passkey and two-factor deps and the `User` traits.
  - The seeder writes the demo user directly, bypassing validation, with `firstOrCreate`.

- [ ] **Step 4: Implement frontend.**
  - `tsconfig.json`: the Global Constraints flags.
  - `vite.config.ts`: `lint.rules['typescript/no-explicit-any'] = 'error'`; remove `bunny`.
  - Add `@fontsource-variable/inter`, import it in `app.tsx`, set `--font-sans: 'Inter Variable', …`.
  - Login page: a `login` field labelled "Tên đăng nhập hoặc email", no "forgot password" link.
  - All auth copy in Vietnamese.
  - Add `lang/vi/{auth,validation,passwords}.php` so validation errors are Vietnamese.

- [ ] **Step 5: Verify.** `php artisan test` passes. `docker compose exec vite sh -c 'pnpm exec tsc --noEmit && pnpm exec vp check'` exits 0, with any kit code broken by the new flags fixed rather than suppressed. `grep -rnE '@ts-ignore|as unknown as|: any\b' resources/js --exclude-dir=components/ui` prints nothing.

- [ ] **Step 6: Commit.** `feat(auth): trim kit to login and register, enforce strict TypeScript`

### Task 3: Domain schema and law seeders

**Files:**
- Move: `data/dieu_luat_data.php` → `database/data/dieu_luat_data.php` (via `git mv`; content unchanged)
- Create: migrations for `user_prefs`, `notes`, `contacts`, `chuong`, `dieu_luat`, `khoan`, `lich_su_hoi_dap`; models `Note`, `Contact`, `UserPref`, `ChatHistory`, `Chapter`, `Article`, `Clause`; `database/seeders/LawSeeder.php`; `app/Search/ArticleRepository.php`; `tests/Feature/Search/ArticleRepositoryTest.php`; `tests/Feature/Database/SeedersTest.php`
- Modify: `database/seeders/DatabaseSeeder.php`

**Interfaces:**
- Produces: `App\Search\ArticleRepository`, a singleton with `all(): list<array>` (ordered by id, keys identical to source data, plus `chuong` as a name string and `khoan` as a list), `find(int $id): ?array`, `chapters(): list<string>`, `related(array $article, int $n = 4): list<array>`.
- Tables keep the old columns from `database/schema.sql`, with these changes: `khoan` adds `cau_hoi_lam_ro TEXT NULL` and `so_nguoi_chet_max INT NULL`; `notes.color` allows `yellow|green|blue|rose|violet|gray`; no `password_resets` or `login_attempts` tables.

- [ ] **Step 1: Write failing tests.**

```php
// ArticleRepositoryTest (LawSeeder run)
test_round_trip: all() equals source file data, comparing every article and clause field, absent keys treated as null
test_numeric_types: gia_tri_tai_san_min is float|null, so_nguoi_chet_min is int|null (not DECIMAL strings)
test_count: count(all()) === 18; find(134)['tieu_de'] matches source; find(999) === null
test_vietnamese_roundtrip: stored tieu_de equals source byte-for-byte; column collation is utf8mb4_unicode_ci
test_related: related(find(134)) has ≤4, same chuong, excludes 134

// SeedersTest
test_running_twice: DatabaseSeeder twice → still 18 articles, 1 demo user, same chapter count
test_rerun_preserves_demo: demo's prefs and notes survive a re-run; password hash unchanged
```

- [ ] **Step 2: Run.** `php artisan test --filter 'ArticleRepository|Seeders'`. Expected: FAIL.

- [ ] **Step 3: Implement** migrations, models with casts, relations (`User hasMany Note`, `hasOne UserPref`) and FK behaviours from the old schema (cascade on notes and prefs, `SET NULL` on contacts and history), an idempotent `LawSeeder` (`updateOrCreate`; `so_thu_tu` = first-seen chapter order), and `ArticleRepository`.

- [ ] **Step 4: Run.** Same command. Expected: PASS.

- [ ] **Step 5: Commit.** `feat(db): add domain schema and law seeders`

### Task 4: Port the lexical scorer to PHP with golden parity

**Files:**
- Create: `tests/fixtures/generate-scorer-golden.cjs`, `tests/fixtures/scorer-golden.json`, `app/Search/data/concepts.json`, `app/Search/VietnameseText.php`, `app/Search/LexicalScorer.php`, `app/Search/RankResult.php`, `app/Search/RankedArticle.php`, `tests/Feature/Search/LexicalParityTest.php`, `tests/Unit/Search/VietnameseTextTest.php`

**Interfaces:**
- Consumes: `ArticleRepository::all()`.
- Produces:

```php
final readonly class RankedArticle { int $id; array $article; float $score; array $parts /* concept, lexical, fuzzy */; array $why; }
final readonly class RankResult {
  string $query; string $mode /* co_dau|khong_dau */; string $role; string $status /* ok|ambiguous|none */;
  array $results /* list<RankedArticle>, sorted desc */; array $concepts; ?int $missingTarget; string $engine /* lexical|blend */;
}
LexicalScorer::rank(string $query, array $articles, float $minScore = 0.20, float $margin = 0.10): RankResult
LexicalScorer::detectRole(string $query): string
VietnameseText::fold(string $s, bool $plain): string; split(string): list<string>; hasDiacritics(string): bool; lev(string $a, string $b, int $max): int
```

- [ ] **Step 1: Generate the golden file from the old JS** (still in the repo). The script loads `js/scorer.js`, `js/data.js`, `js/nlu.js`, `js/law.js` as `tests/scorer-test.js` does. It collects every question from that test file (arrays `C`, `added`, `availability`, `followUps`, `conversations` including their two-turn forms, the out-of-scope question), all chat suggestions (`SUG`) in `js/chatbot.js`, and these extras: `tôi bị đánh 20%`, `chém 3 người chết`, `trộm 2 tỷ`, `điều 134`.

  For each question, it writes `{question, rank: {mode, status, concepts, missingTarget, top5: [{key, score (9 d.p.), parts}]}, answer}`, where `answer` is `Law.answer()` without `debug` or `role`, plus `search`: `Law.search(q)` as `[{id, score}]`. It also dumps `Scorer.CONCEPTS` to `app/Search/data/concepts.json`.

  Run: `node tests/fixtures/generate-scorer-golden.cjs`. Expected: `node tests/scorer-test.js | tail -1` still prints `62/62`, and the fixture has ≥70 entries.

- [ ] **Step 2: Write failing tests.**

```php
// LexicalParityTest — dataProvider from scorer-golden.json
foreach golden: rank($q, $repo->all()):
  assertSame(mode, status, concepts, missingTarget, top5 keys in order)
  assertEqualsWithDelta(score, 1e-9) for each top5 score and part

test_role: 'tôi bị đánh'→nan_nhan; 'bị lừa mất tiền'→nan_nhan; 'tôi đánh bạc bị công an bắt'→nguoi_vi_pham;
           'tôi lỡ đánh người'→nguoi_vi_pham; 'thời tiết hôm nay'→chua_ro; 'toi bi danh'→chua_ro
test_nfd: Normalizer::normalize('tôi bị đánh', FORM_D) ranks identically to the NFC form, mode co_dau

// VietnameseTextTest
fold('Đánh Bạc', false)==='đánh bạc'; fold('Đánh Bạc', true)==='danh bac'
split('20% đánh-bạc!')===['20%','đánh','bạc']; hasDiacritics('toi')===false
lev('đánh','danh',2)===2 (code points, not bytes)
```

- [ ] **Step 3: Run.** `php artisan test --filter 'LexicalParity|VietnameseText'`. Expected: FAIL.

- [ ] **Step 4: Implement** by porting `js/scorer.js` lines 70–263 function by function, with the same constants: W 0.60/0.30/0.10, `k1=1.2`, `b=0.75`, `s/(s+3)`, `1-exp(-1.2·raw)`, typo ×0.7, `+0.25` per repeat hit, thresholds 0.20/0.10, the `{156,173,175}` missing-target rule.

  JS/PHP traps to get right:
  - Every regex needs `/u`.
  - Use `mb_*`, `mb_str_split` and `Normalizer` (never `strlen`, `$s[0]`, `levenshtein()`).
  - Multi-word keys keep insertion order.
  - `usort` must stay stable with `$b <=> $a`.
  - Numeric-looking tokens become int array keys, so cast when comparing.
  - `'p'` and `'d'` lexicons and indexes are cached per instance.

- [ ] **Step 5: Run.** Same command. Expected: PASS.

- [ ] **Step 6: Commit.** `feat(search): port lexical scorer to PHP with golden parity`

### Task 5: Search, answer and conversation services

**Files:**
- Create: `app/Search/RankingService.php`, `app/Search/ClauseTfIdf.php`, `app/Search/LawSearch.php`, `app/Search/LawAnswerer.php`, `app/Search/ChatConversation.php`, `config/search.php`, `tests/Feature/Search/LawAnswererParityTest.php`, `tests/Feature/Search/LawSearchTest.php`, `tests/Feature/Search/ChatConversationTest.php`

**Interfaces:**
- Consumes: `LexicalScorer`, `ArticleRepository`.
- Produces:

```php
RankingService::rank(string $query, ?array $articles = null): RankResult   // lexical now; blended in Task 13. The only entry point for ranking.
LawSearch::search(string $q, string $chuong = ''): list<array>             // article + optional 'score'
LawAnswerer::answer(string $question): array                               // same keys as Law.answer() minus debug
ChatConversation::resolve(string $message, ?array $pending): string        // the full question to answer
ChatConversation::nextPending(string $fullQuestion, array $answer): ?array // {question, unit, article_id} or null
```

`config/search.php` keys: `search_min_score` 0.08, `search_limit` 15, `new_topic_min_score` 0.25, `max_question_chars` 2000.

- [ ] **Step 1: Write failing tests.**

```php
// LawAnswererParityTest — dataProvider from scorer-golden.json
assert answer($q) matches golden 'answer' on every key (found, mode, score ±1e-9, alt, article, khoan,
khoan_all, ask, quyen_loi, can_cu, ghi_chu_quyen_loi, note_title, note_text)
test_followups: each of the 10 followUps in tests/scorer-test.js → mode 'overview', ask contains phrase
test_conversations: 'vay tiền bị quịt thì phải làm sao' overview; plus ' 80 triệu đồng' → article 175, mode specific;
                    'tôi bị đánh gây thương tích, giờ xử lý thế nào 20%' → 134, specific

// LawSearchTest
search('') → 18, no score; search('', <chapter>) → only that chapter
search('điều 134'), search('134'), search('Dieu 134') → exactly [134] with score 1
dataProvider from scorer-golden.json: search($q) ids in order and scores (±1e-9) match golden 'search'
search('đánh bạc') → first id 321, ≤15 results, all score > 0.08

// ChatConversationTest
resolve('hello', null) === 'hello'
pending {question:'vay tiền', unit:'triệu', article_id:175}: resolve('80', p) === 'vay tiền 80 triệu đồng'
pending unit '%': resolve('20', p) === 'q 20%'; resolve('2 tỷ', p) === 'q 2 tỷ đồng'
resolve('bị trộm xe máy', pending for 175) === 'bị trộm xe máy'   // new topic drops context
resolve('anh ấy không trả', pending for 175) === 'vay tiền. Thông tin bổ sung: anh ấy không trả'
result never exceeds max_question_chars
nextPending: overview → unit from ask text ('tỉ lệ|phần trăm|%'→'%', 'triệu|giá trị tài sản'→'triệu', else null); specific → null
```

- [ ] **Step 2: Run.** `php artisan test --filter 'LawAnswererParity|LawSearch|ChatConversation'`. Expected: FAIL.

- [ ] **Step 3: Implement** by porting `js/law.js` (`search`, `extractSoLieu`, `pickKhoan`, `answer`), `js/nlu.js` (`vnNormalize`, stopwords, `buildIndex`, `vectorize`, `cosineSim`, `semanticSearch` into `ClauseTfIdf`), and `continueQuestion` and the `pendingUnit` logic in `ask` from `js/chatbot.js`. All ranking goes through `RankingService`. Keep the regex `/(vô ý|lỡ tay|say rượu|hung khí)/iu` and all Vietnamese strings unchanged.

- [ ] **Step 4: Run.** Same command, then `php artisan test`. Expected: PASS.

- [ ] **Step 5: Commit.** `feat(search): port search, answer and conversation logic`

### Task 6: App shell and account pages

**Files:**
- Create: `app/Http/Middleware/BlockDemoAccount.php`, `app/Http/Controllers/Settings/PreferencesController.php`, `app/Services/AvatarProcessor.php`, `app/Http/Requests/Settings/AvatarRequest.php`, `resources/js/types/models.ts`, `tests/Feature/Settings/DemoLockTest.php`, `tests/Feature/Settings/AvatarTest.php`, `tests/Feature/Settings/PreferencesTest.php`, test image fixtures
- Modify: `routes/settings.php`, `routes/web.php`, `app/Http/Middleware/HandleInertiaRequests.php`, `app/Http/Controllers/Settings/{ProfileController,SecurityController}.php`, `resources/views/app.blade.php`, `resources/css/app.css`, `resources/js/hooks/use-appearance.tsx`, `resources/js/components/app-header.tsx`, `resources/js/layouts/*`, `resources/js/pages/settings/{profile,security,appearance}.tsx`, `resources/js/components/delete-user.tsx`

**Interfaces:**
- Produces:
  - Layout `AppLayout` (top nav: Tra cứu, Tư vấn, Ghi chú, Giới thiệu, Liên hệ, user menu; no sidebar).
  - Inertia shared props `auth.user {id, name, email, username, avatar, isDemo}` and `prefs {theme, font, noteColor}`.
  - `resources/js/types/models.ts` exports `User`, `Prefs`, `Note`, `Article`, `Clause`, `ChatAnswer`; later tasks add to it.
  - Route names: `profile.edit` `/ho-so`, `security.edit` `/doi-mat-khau`, `preferences.edit` `/tuy-chinh`.
  - Avatar routes: `profile.avatar.update` POST `/ho-so/avatar`, `profile.avatar.destroy` DELETE `/ho-so/avatar`.
  - Middleware alias `not-demo`.

- [ ] **Step 1: Write failing tests.**

```php
// DemoLockTest — as demo user, each returns redirect with error key 'demo' and changes nothing:
PATCH /ho-so {name:'X'}; POST /ho-so/avatar; DELETE /ho-so/avatar; PUT /doi-mat-khau; DELETE /ho-so {password:'12345'}
test_demo_can_save_prefs: PUT /tuy-chinh succeeds
test_regular_user_can_change_password_and_delete: deletion cascades notes, sets contacts/history user_id NULL
test_email_cannot_be_changed_via_profile: PATCH /ho-so {email:'new@x.com'} leaves email unchanged

// AvatarTest
valid 500×300 PNG → users.avatar starts 'data:image/jpeg;base64,' and decodes to 256×256
transparent PNG → white background (corner pixel white)
EXIF orientation 6 fixture → output upright (known pixel check)
text file named .jpg; GIF; 32×32 PNG; 3 MB JPEG → validation error on 'avatar', Vietnamese message, avatar unchanged

// PreferencesTest
PUT /tuy-chinh {theme:'dark',font:'lg',noteColor:'rose'} persists and is shared as prefs
invalid theme, font, or noteColor rejected; defaults system/md/yellow when no row
GET any page as a dark-pref user → root element has class 'dark' and data-font="lg" in the HTML
```

- [ ] **Step 2: Run.** `php artisan test --filter 'DemoLock|Avatar|Preferences'`. Expected: FAIL.

- [ ] **Step 3: Implement backend.**
  - Routes moved to the Vietnamese slugs.
  - `AvatarProcessor::toDataUri(UploadedFile): string`: GD reads, applies EXIF orientation, flattens on white, center-crops, resizes to 256×256, encodes JPEG.
  - Rules: `mimes:jpg,jpeg,png,webp|max:2048|dimensions:min_width=64,min_height=64`.
  - Remove email from the profile form and rules. Delete requires password.

- [ ] **Step 4: Implement frontend.**
  - Blade renders `dark` class and `data-font` from prefs, avoiding a theme flash; guests fall back to the system theme.
  - `useAppearance` reads and saves through `/tuy-chinh` for logged-in users.
  - CSS: `:root[data-font=sm|md|lg|xl]` sets note font sizes `.84/.95/1.1/1.3rem`.
  - The demo account sees locked controls disabled with a note explaining why.
  - Replace kit dashboard and welcome pages.

- [ ] **Step 5: Verify.** `php artisan test` passes; `tsc --noEmit` and `vp check` exit 0. Open `/ho-so`, `/doi-mat-khau`, `/tuy-chinh` in a browser or via curl and confirm 200 for a logged-in user.

- [ ] **Step 6: Commit.** `feat(account): add app shell, profile, avatar, password and preferences`

### Task 7: Notes

**Files:**
- Create: `app/Http/Controllers/NoteController.php`, `app/Http/Requests/NoteRequest.php`, `app/Policies/NotePolicy.php`, `resources/js/pages/notes/index.tsx`, `resources/js/components/notes/{note-card,note-dialog}.tsx`, `resources/js/lib/notes.ts`, `resources/js/lib/notes.test.ts`, `tests/Feature/NotesTest.php`
- Modify: `routes/web.php`, `resources/js/types/models.ts`

**Interfaces:**
- Consumes: `ArticleRepository::all()` (first 6 as "Điều luật nổi bật"), `AppLayout`, `prefs.noteColor`.
- Produces: routes `notes.index` GET `/ghi-chu`, `notes.store` POST, `notes.update` PATCH `/ghi-chu/{note}`, `notes.pin` PATCH `/ghi-chu/{note}/ghim`, `notes.destroy` DELETE. `POST /ghi-chu` accepts optional `source_type` (`article|chat`) and `source_ref`, and answers JSON when `wantsJson()` (used by Tasks 8–9). `filterNotes(notes: Note[], q: string): Note[]`.

- [ ] **Step 1: Write failing tests.**

```php
// NotesTest
list is pinned first then newest updated; only own notes
user B PATCH/pin/DELETE user A's note → 403 and unchanged
validation: both empty → error; title 121, content 5001 → error; color 'red' → error; defaults color to prefs
201st note → error 'title' with limit message; update at limit OK
XSS: stored '<script>' content returned verbatim in JSON (React escapes)
guest → redirect /login
```
```ts
// notes.test.ts
filterNotes(['Ghi chú A'], 'ghi chu') matches; 'GHI CHÚ' matches; 'đơn' matches 'don'; '' returns all; NFD query matches NFC note
```

- [ ] **Step 2: Run.** `php artisan test --filter Notes` and `docker compose exec vite pnpm exec vp test run resources/js/lib/notes.test.ts`. Expected: FAIL.

- [ ] **Step 3: Implement** the controller, policy, and page: card grid, greeting "Xin chào, {name}", shadcn Dialog for create/edit and delete confirmation, pin toggle, color picker, instant filter via `filterNotes`, source link ("Điều N" / "Từ chatbot"), empty state, popular articles section. Mobile: single column at 400px.

- [ ] **Step 4: Verify.** Both test commands pass; `tsc --noEmit` and `vp check` exit 0.

- [ ] **Step 5: Commit.** `feat(notes): add notes page`

### Task 8: Law search and article detail pages

**Files:**
- Create: `app/Http/Controllers/LawController.php`, `resources/js/pages/law/{search,show}.tsx`, `tests/Feature/LawPagesTest.php`
- Modify: `routes/web.php`, `resources/js/types/models.ts`

**Interfaces:**
- Consumes: `LawSearch::search`, `ArticleRepository`, `POST /ghi-chu` JSON.
- Produces: `law.search` GET `/tra-cuu?q=&chuong=`, `law.show` GET `/dieu-luat/{id}`.

- [ ] **Step 1: Write failing tests.**

```php
// LawPagesTest — assertInertia
GET /tra-cuu?q=đánh bạc → component law/search, results[0].id==321, has score; chapters length matches repo
GET /tra-cuu?chuong=unknown → treated as all; q longer than 200 chars truncated, not an error
GET /dieu-luat/134 → article with khoan, can_cu_boi_thuong, related (≤4)
GET /dieu-luat/999, /dieu-luat/abc → 404
guest → redirect /login
```

- [ ] **Step 2: Run.** `php artisan test --filter LawPages`. Expected: FAIL.

- [ ] **Step 3: Implement** the controller and pages per the "A" mockup: search box + "Tìm", chapter Select, filters reflected in the URL (Inertia `preserveState`), result cards with title, summary, chapter pill, penalty pill, "Phù hợp N%" pill, count line, empty state linking to `/tu-van`. Detail page: clauses, rights, compensation basis, related, "+ Lưu vào ghi chú" using the note text format in `js/detail.js`.

- [ ] **Step 4: Verify.** Tests pass; `tsc --noEmit`, `vp check` and `pnpm build` exit 0. `grep -rl 'tước đoạt tính mạng' public/build` prints nothing, proving law data isn't shipped to the browser.

- [ ] **Step 5: Commit.** `feat(law): add search and article detail pages`

### Task 9: Chat advisor

**Files:**
- Create: `app/Http/Controllers/ChatController.php`, `resources/js/pages/chat/index.tsx`, `resources/js/components/chat/answer-message.tsx`, `resources/js/lib/api.ts`, `tests/Feature/ChatTest.php`
- Modify: `routes/web.php`, `resources/js/types/models.ts`

**Interfaces:**
- Consumes: `ChatConversation`, `LawAnswerer`, `ChatHistory`, `POST /ghi-chu` JSON.
- Produces: `chat.index` GET `/tu-van` (clears session `chat.pending`), `chat.ask` POST `/tu-van/hoi` `{message}` → `ChatAnswer` JSON. `postJson<T>(url: string, body: unknown): Promise<T>` (sends the XSRF cookie token).

- [ ] **Step 1: Write failing tests.**

```php
// ChatTest
POST 'vay tiền bị quịt thì phải làm sao' → found, overview, 175; session has pending; a lich_su_hoi_dap row with user_id, cau_hoi, dieu_luat_id 175, diem
then POST '80' → 175, specific, row's cau_hoi contains '80 triệu đồng', khoan_so set; pending cleared
then POST unrelated topic while pending → new article
'thời tiết hôm nay thế nào' → found:false, no pending, history row with dieu_luat_id NULL and diem NULL (every question is logged)
POST '20' with no pending → found:false, 200 not 500
message empty → 422; 501 chars → 422; guest → 401/redirect
GET /tu-van clears pending
role: 'tôi bị đánh gây thương tích 20%' → role nan_nhan
client cannot influence stored article or score: extra body fields ignored
```

- [ ] **Step 2: Run.** `php artisan test --filter ChatTest`. Expected: FAIL.

- [ ] **Step 3: Implement** the controller and page: message list, welcome line and suggestion chips from `SUG` in `js/chatbot.js`, typing indicator, input disabled while waiting, network-error message with retry. `answer-message.tsx` follows `showAnswer` in `js/chatbot.js`: overview vs. specific, rights before penalty when `role==='nan_nhan'`, "Có thể bạn muốn hỏi" alt link, detail link, "+ Lưu vào ghi chú" → saved state, disclaimer.

- [ ] **Step 4: Verify.** Tests pass; `tsc --noEmit` and `vp check` exit 0.

- [ ] **Step 5: Commit.** `feat(chat): add chat advisor page`

### Task 10: About, contact and access rules

**Files:**
- Create: `app/Http/Controllers/ContactController.php`, `resources/js/pages/about.tsx`, `resources/js/pages/contact.tsx`, `resources/js/layouts/public-layout.tsx`, `tests/Feature/ContactTest.php`, `tests/Feature/AccessRulesTest.php`
- Modify: `routes/web.php`

**Interfaces:**
- Produces: `about` GET `/gioi-thieu`, `contact.create` GET `/lien-he`, `contact.store` POST `/lien-he`, `home` GET `/`.

- [ ] **Step 1: Write failing tests.**

```php
// ContactTest
guest submits valid → row with user_id NULL, trang_thai 'moi'; logged-in → user_id set
phone optional; '12ab', 7-digit → error; name 1 char, message 9 chars or 2001 → error; invalid email → error
// AccessRulesTest
guest: /gioi-thieu, /lien-he, /login, /register → 200; /ghi-chu, /tra-cuu, /dieu-luat/134, /tu-van, /ho-so, /doi-mat-khau, /tuy-chinh → /login
GET / → guest to /login; user to /ghi-chu
unknown URL → styled 404 page in Vietnamese
```

- [ ] **Step 2: Run.** `php artisan test --filter 'Contact|AccessRules'`. Expected: FAIL.

- [ ] **Step 3: Implement.** Copy About text from `html/gioi-thieu.html` and contact copy from `html/lien-he.html`. Add `resources/js/pages/errors/not-found.tsx` and the Inertia exception handler.

- [ ] **Step 4: Verify.** `php artisan test` passes; `tsc --noEmit`, `vp check` and `pnpm build` exit 0.

- [ ] **Step 5: Commit.** `feat: add about and contact pages and access rules`

### Task 11: Evaluation set and baseline

**Files:**
- Create: `tests/fixtures/search-eval.json`, `app/Console/Commands/SearchEval.php`, `app/Search/Evaluator.php`, `tests/Feature/Search/EvaluatorTest.php`, `tests/Unit/Search/EvalSetTest.php`

**Interfaces:**
- Consumes: `RankingService`, `LexicalScorer`.
- Produces: `php artisan search:eval --engine=lexical|semantic|blend --split=tune|holdout|all [--json]` (only `lexical` works until Task 13 adds the others; they exit non-zero with a clear message until then). `Evaluator::run(string $engine, string $split): array{top1: float, top3: float, total: int, failures: list<array>}`.
- Case format: `{id, question, expected: int|null, alternates: int[], clause: int|null, category, split: "tune"|"holdout"}`. `expected: null` means not found.

- [ ] **Step 1: Write failing tests.**

```php
// EvalSetTest
≥150 cases; unique questions and ids; every expected/alternate exists in the 18 articles
categories everyday, no_diacritics, typo, slang, rambling, two_crimes, not_found each ≥10
every article is expected in ≥4 cases
holdout share between 30% and 36%, and every category appears in holdout
no question is copied from tests/scorer-test.js

// EvaluatorTest — with a stub ranker
two_crimes: top-1 counts only for expected; top-3 counts if expected or any alternate is in top 3
not_found: correct only when status==='none', in both metrics
regular: top-1 correct and top-3 correct calculated separately; status none on a regular case is wrong
```

- [ ] **Step 2: Run.** `php artisan test --filter 'EvalSet|Evaluator'`. Expected: FAIL.

- [ ] **Step 3: Write the eval set before running any scoring.** Write questions as real people would ask them, from reading each article in `database/data/dieu_luat_data.php`, and don't consult the scorer's concept list. Assign `split` by taking every third case within each category as `holdout`. Labels are final (owner's decision).

- [ ] **Step 4: Implement** `Evaluator` and the command.

- [ ] **Step 5: Run the baseline.** `php artisan test --filter 'EvalSet|Evaluator'` passes, then `php artisan search:eval --engine=lexical --split=holdout`. Record its top-1 and top-3 in `tests/fixtures/search-baseline.json`. **After this step `search-eval.json` must not change.** Add a test asserting its sha256 matches a constant, and commit together.

- [ ] **Step 6: Commit.** `test(search): add evaluation set and lexical baseline`

### Task 12: Embedder service and stored embeddings

**Files:**
- Create: `docker/embedder/{Dockerfile,main.py,requirements.txt,test_main.py}`, migration for `law_embeddings`, `app/Models/LawEmbedding.php`, `app/Search/EmbeddingClient.php`, `app/Search/EmbeddingStore.php`, `app/Console/Commands/SearchEmbed.php`, `tests/Feature/Search/EmbeddingClientTest.php`, `tests/Feature/Search/SearchEmbedTest.php`
- Modify: `compose.yaml`, `config/search.php`, `.env.example`

**Interfaces:**
- Produces:
  - HTTP `POST /embed {texts: string[], kind: "query"|"passage"}` → `{model: string, dimension: int, vectors: float[][]}` (L2-normalized); `GET /health`.
  - `EmbeddingClient::embedQuery(string $text): ?array` (null on any failure); `embedPassages(array $texts): ?array`.
  - `EmbeddingStore::vectorsFor(string $model): array<int, list<list<float>>>` (article id → its vectors).
  - `php artisan search:embed [--wait=60]`.
  - Table `law_embeddings(id, dieu_luat_id FK cascade, so_khoan nullable, model, content_hash, vector JSON)`, unique `(dieu_luat_id, so_khoan, model)`.
  - Config: `embedder_url` (`http://embedder:8000`), `embedder_timeout` 1.5, `embedder_model`.

- [ ] **Step 1: Write failing tests.**

```python
# test_main.py (runs inside the embedder image)
health 200; embed returns dimension 384 and unit-length vectors
query vs passage differ in prefix: "query: " and "passage: " are applied
empty texts → 422; more than 64 texts → 422; non-string → 422
similar Vietnamese pair scores higher than unrelated pair:
  'người quen mượn xe rồi đem bán' closer to 'lạm dụng tín nhiệm chiếm đoạt tài sản' than 'tội đánh bạc'
```
```php
// EmbeddingClientTest with Http::fake
success returns vector; timeout, 500, malformed JSON, wrong dimension, model mismatch → null, warning logged, no exception
// SearchEmbedTest with Http::fake
creates a row per article (title+summary+keywords) and per clause (conditions+content+keywords)
second run: no writes (same content_hash); changed tu_khoa → that row only re-embedded
embedder down → exits 0 after --wait with a warning, leaving existing rows
```

- [ ] **Step 2: Run.** `php artisan test --filter 'EmbeddingClient|SearchEmbed'`. Expected: FAIL.

- [ ] **Step 3: Implement the service.** Install `torch` from `https://download.pytorch.org/whl/cpu`, which has amd64 and arm64 wheels. Bake the model into the image at build time and set `HF_HUB_OFFLINE=1` at runtime. Run with one worker, `mem_limit: 1500m`, healthcheck on `/health`. It exposes no host port.

- [ ] **Step 4: Implement** the Laravel side and add `embedder` to `compose.yaml`.

- [ ] **Step 5: Verify.** `docker compose up -d --build embedder`, then `docker compose exec embedder pytest` passes, and `docker compose exec app php artisan search:embed` creates rows with `SELECT COUNT(*)` = articles + clauses. Run `php artisan test`. Record `docker stats --no-stream` memory: expected ≤ ~1 GB.

- [ ] **Step 6: Commit.** `feat(search): add embedding service and stored vectors`

### Task 13: Blend, fallback, tuning and results

**Files:**
- Create: `app/Search/SemanticScorer.php`, `app/Console/Commands/SearchTune.php`, `tests/Feature/Search/RankingBlendTest.php`, `tests/Feature/Search/EmbedderFallbackTest.php`, `tests/Feature/Search/SearchRegressionTest.php`
- Modify: `app/Search/RankingService.php`, `app/Search/Evaluator.php`, `config/search.php`, `README.md` (results section only; final README in Task 15)

**Interfaces:**
- Consumes: `LexicalScorer`, `EmbeddingClient`, `EmbeddingStore`.
- Produces:
  - `SemanticScorer::scores(array $queryVector, array $vectorsByArticle): array<int, float>`, where each article's value is the max cosine over its vectors, mapped by `clamp((cos - cos_floor) / (cos_ceil - cos_floor), 0, 1)`.
  - `RankingService::rank` result with `engine='blend'` or `'lexical'`.
  - `php artisan search:tune` (tune split only; prints and writes the best values).
  - `config/search.php` `blend` keys: `lexical_weight`, `semantic_weight`, `semantic_weight_with_concept`, `cos_floor`, `cos_ceil`, `min_score`, `margin`, `search_min_score`, `new_topic_min_score`.

**Algorithm:** `final = lexical_weight·lexical + w·semantic`, where `w = semantic_weight_with_concept` if the query hit any concept, else `semantic_weight`. Concept-hit tuning range is lower than no-hit range, so the dictionary keeps priority. `status` uses the same logic as the lexical scorer with `blend.min_score` and `blend.margin`, and `missingTarget` still forces `none`. `parts` gains `semantic`. In lexical mode, all original thresholds apply.

- [ ] **Step 1: Write failing tests.**

```php
// RankingBlendTest with fixed vectors
query with no concept hit: semantic lifts the right article above lexical top
query with a strong concept: dictionary target stays first against a conflicting semantic score
engine==='blend'; results sorted desc; scores in [0,1]; chapter-filtered pool only returns that chapter

// EmbedderFallbackTest — every case gives engine 'lexical' and results identical to LexicalScorer, no exception
embedder timeout; 500; wrong dimension; no vectors stored; vectors only for a different model; vectors covering only some articles
GET /tra-cuu?q=… and POST /tu-van/hoi both return 200 with the embedder down

// SearchRegressionTest — on the blend, using recorded query vectors (see Step 3)
every question in array C and added of tests/scorer-test.js: top article == expected, status != none
'thời tiết hôm nay thế nào' → status none
10 follow-up and 2 conversation checks pass through LawAnswerer
```

- [ ] **Step 2: Run.** `php artisan test --filter 'RankingBlend|EmbedderFallback'`. Expected: FAIL.

- [ ] **Step 3: Add vector caching for tests.** Write `tests/fixtures/query-vectors.json` (question → vector from the real embedder, for regression and eval questions) via a `--dump` option on `search:eval`, so regression tests run without the embedder. Commit passage vectors for tests the same way, at `tests/fixtures/passage-vectors.json`.

- [ ] **Step 4: Implement** `SemanticScorer`, the blend, and `search:tune`: grid search over weights, `cos_floor/ceil`, `min_score`, `margin`, maximizing top-1 then top-3 on `split=tune` only, rejecting any setting that fails a regression question.

- [ ] **Step 5: Tune.** Run `php artisan search:tune`, commit the chosen values to `config/search.php`, then run `php artisan test --filter 'RankingBlend|EmbedderFallback|SearchRegression'`. Expected: PASS.

- [ ] **Step 6: Evaluate once on holdout.** `for e in lexical semantic blend; do php artisan search:eval --engine=$e --split=holdout; done`. Expected: blend top-1 and top-3 are each ≥ `tests/fixtures/search-baseline.json`. If not, stop and report the real numbers to the owner: holdout has now been seen, so retuning against it would make the result meaningless. Never edit eval labels or baseline to pass.

- [ ] **Step 7: Record.** Add a README "Search quality" table with the three engines' numbers. Add a test asserting holdout blend ≥ baseline using cached vectors (Step 6 must have passed).

- [ ] **Step 8: Commit.** `feat(search): blend semantic matching with lexical scorer`

### Task 14: Production stack, setup service, ARM proof

**Files:**
- Create: `docker/php/setup.sh`, `tests/docker/smoke.sh`
- Modify: `compose.yaml`, `compose.override.yaml`, `Dockerfile`, `docker/nginx/default.conf`, `bootstrap/app.php` (trusted proxies), `.env.example`

**Interfaces:**
- Produces: `setup` one-shot service (same image as `app`); `app` depends on `setup: service_completed_successfully`.
- `setup.sh` order: wait for MySQL → `migrate --force` → `db:seed --force` (idempotent) → `search:embed --wait=60`, which can't fail the boot.

- [ ] **Step 1: Write `tests/docker/smoke.sh`** (failing until the rest exists). It uses project name `hs-smoke`, port 18080, `compose.yaml` only.

```bash
cp .env.example to a temp env file; docker compose -p hs-smoke -f compose.yaml --env-file $E up -d --build --wait
assert GET /login == 200
assert POST-free search: log in as demo/12345 with curl cookie jar and CSRF; GET /tra-cuu?q=đánh+bạc contains 'Điều 321'
record APP_KEY hash and create a note as demo
docker compose ... down (no -v); up -d --wait
assert same APP_KEY hash; note still exists; demo still logs in; 18 articles; 1 demo user
docker compose ... stop embedder; GET /tra-cuu?q=đánh+bạc still contains 'Điều 321'
trap: down -v and remove temp file
```

- [ ] **Step 2: Run.** `bash tests/docker/smoke.sh`. Expected: FAIL.

- [ ] **Step 3: Implement** the `setup` service, healthchecks, `APP_ENV`, `APP_DEBUG=false`, config/route/view caching in the prod entrypoint, trusted proxies from `TRUSTED_PROXIES`, and gzip + long-cache for `/build/` in nginx. Dev stack gets `setup` too, so `docker compose up` alone is enough.

- [ ] **Step 4: Verify amd64.** `bash tests/docker/smoke.sh`. Expected: exits 0 with all assertions printed.

- [ ] **Step 5: ARM proof.** This step installs host-level binfmt handlers, which needs the owner's OK; ask first. Then:

```bash
docker run --privileged --rm tonistiigi/binfmt --install arm64
docker buildx create --use --name hs-multi
docker buildx build --platform linux/amd64,linux/arm64 --target prod .
docker buildx build --platform linux/amd64,linux/arm64 --target web .
docker buildx build --platform linux/amd64,linux/arm64 docker/embedder
DOCKER_DEFAULT_PLATFORM=linux/arm64 bash tests/docker/smoke.sh
```

Expected: all exit 0. Emulation is slow, so raise timeouts if needed. Record the outcome and duration in `docs/ARCHITECTURE.md`. If it can't complete on this VM, report exactly what passed and what didn't. Do not claim ARM works.

- [ ] **Step 6: Commit.** `feat(docker): add production stack, setup service and smoke test`

### Task 15: Remove old code and finish docs

**Files:**
- Delete: `html/`, `js/`, `css/`, root `index.html`, `backend/`, `tools/export_law.php`, `data/`, `database/schema.sql`, `database/seed*.sql`, `database/migrations/*.sql`, `tests/run-in-browser.html`, `tests/e2e.py`, `tests/db-test.js`, `tests/scorer-test.js`, `tests/fixtures/generate-scorer-golden.cjs`
- Rewrite: `README.md`, `docs/ARCHITECTURE.md`
- Modify: `docs/API_CONTRACT.md`, `docs/DATABASE.md`; add an "out of date since the Laravel rewrite" note at the top of `docs/TASK_BOARD.md`, `TIMELINE.md`, `FILE_OWNERSHIP.md`, `PROJECT_AUDIT.md`, `TESTING_CHECKLIST.md`
- Modify: `tests/Feature/Search/SearchRegressionTest.php`, moving its question lists into `tests/fixtures/search-regression.json` first

- [ ] **Step 1: Move remaining dependencies.** Copy regression questions into `tests/fixtures/search-regression.json`, then `grep -rnE "scorer-test|js/(scorer|law|nlu|data|chatbot|detail)|html/" app tests routes resources docker` must print nothing.

- [ ] **Step 2: Delete** the old files listed above with `git rm -r`.

- [ ] **Step 3: Write docs.**
  - `README.md`: what the app is; `cp .env.example .env && docker compose up`; SSH forwarding for headless hosts (`ssh -N -L 8080:localhost:80 -L 5173:localhost:5173 user@host`); demo account; production command (`docker compose -f compose.yaml up -d --build`, change `DB_*` first); multi-arch `docker buildx build --platform linux/amd64,linux/arm64` commands; resources (about 1 GB for embedder); all test commands; search quality table; known limitations (no password recovery, plain HTTP).
  - `ARCHITECTURE.md`: services, search flow, fallback.
  - `API_CONTRACT.md`, `DATABASE.md`: match actual routes and tables.
  - Keep them in Vietnamese, as existing docs are.

- [ ] **Step 4: Full verification.**

```bash
docker compose exec app php artisan test
docker compose exec vite sh -c 'pnpm exec tsc --noEmit && pnpm exec vp check && pnpm exec vp test run && pnpm build'
docker compose exec embedder pytest
bash tests/docker/smoke.sh
find resources/js -name '*.js' -o -name '*.jsx'
grep -rnE '@ts-ignore|as unknown as|: any\b' resources/js --exclude-dir=ui
```

Expected: all pass, and the last two print nothing.

- [ ] **Step 5: Commit.** `chore: remove old static site and update docs`
