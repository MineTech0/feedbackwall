# spec.md
## Anonyymi tanssikurssipalautteen järjestelmä – Technical Specification

## 1. Technology Stack & Architecture

### 1.1 Stack

- **Backend Framework:** Laravel
- **Frontend:** Inertia.js + Svelte 5
- **Styling:** Tailwind CSS
- **Authentication (Admin):** Google OAuth via Laravel Socialite (or equivalent)
- **Database:** Relational (e.g., MySQL / PostgreSQL)
- **Session & CSRF:** Laravel default session + CSRF protection
- **Localization:** Laravel localization (lang files) + Svelte-level i18n helpers for UI text in **fi/en**
- **Deployment:** Standard PHP hosting or containerized environment

### 1.2 High-Level Architecture

- **Laravel backend**:
  - Provides routes, controllers, Eloquent models, validation, authorization, content filter service, localization backend, and persistence.
  - Serves Inertia responses for both public and admin UIs.
- **Inertia + Svelte 5 frontend**:
  - Svelte components are used as pages and partials rendered via Inertia.
  - UI text is localized via props injected from Laravel (per language) and simple Svelte i18n helpers.
- **Tailwind CSS**:
  - Used for layout, spacing, typography, and components.
  - Configured via `tailwind.config.cjs` with Laravel Mix/Vite build pipeline.

---

## 2. Data Model

### 2.1 Boards

**Table:** `boards`

| Column        | Type         | Notes                                         |
|---------------|--------------|-----------------------------------------------|
| id            | BIGINT PK    | Auto-increment                               |
| name          | VARCHAR(255) | Course/board name                             |
| slug          | VARCHAR(255) | Unique; used in URL `/b/{slug}`              |
| description   | TEXT         | Short description for students               |
| is_public     | BOOLEAN      | Show in board list or not                    |
| archived_at   | DATETIME     | Nullable; when set, board is archived        |
| created_at    | DATETIME     | Laravel timestamps                           |
| updated_at    | DATETIME     | Laravel timestamps                           |

**Laravel Model:** `Board`  
**Relationships:**

- `Board` hasMany `Feedback`.

### 2.2 Feedback

**Table:** `feedback`

| Column             | Type         | Notes                                            |
|--------------------|--------------|--------------------------------------------------|
| id                 | BIGINT PK    |                                                  |
| board_id           | BIGINT FK    | References `boards.id`                          |
| content            | TEXT         | Feedback text                                    |
| moderation_state   | ENUM         | `published`, `pending_review`, `hidden`         |
| votes_count        | INT          | Denormalized count, default 0                   |
| creator_fingerprint| VARCHAR(255) | Nullable; hash for spam/rate-limiting           |
| created_at         | DATETIME     |                                                  |
| updated_at         | DATETIME     |                                                  |
| deleted_at         | DATETIME     | Nullable; soft delete using `SoftDeletes`       |

**Laravel Model:** `Feedback` (+ `SoftDeletes`)  
**Relationships:**

- `Feedback` belongsTo `Board`.
- `Feedback` hasMany `FeedbackVote`.

### 2.3 Feedback Votes

**Table:** `feedback_votes`

| Column           | Type         | Notes                                     |
|------------------|--------------|-------------------------------------------|
| id               | BIGINT PK    |                                           |
| feedback_id      | BIGINT FK    | References `feedback.id`                 |
| voter_fingerprint| VARCHAR(255) | Hashed identifier per browser/client      |
| created_at       | DATETIME     |                                           |

**Constraints:**

- Unique composite index on `(feedback_id, voter_fingerprint)` to enforce one vote per fingerprint.

**Laravel Model:** `FeedbackVote`  
**Relationships:**

- `FeedbackVote` belongsTo `Feedback`.

### 2.4 Users (Admins)

**Table:** `users`

| Column     | Type         | Notes                                           |
|------------|--------------|-------------------------------------------------|
| id         | BIGINT PK    |                                                 |
| name       | VARCHAR(255) | From Google profile                             |
| email      | VARCHAR(255) | Unique                                          |
| google_id  | VARCHAR(255) | Unique; from Google OAuth                       |
| is_admin   | BOOLEAN      | True if the user is an admin                    |
| created_at | DATETIME     |                                                 |
| updated_at | DATETIME     |                                                 |

**Laravel Model:** `User`  
**Usage:**

- Only admins are relevant in this version.
- Non-admin Google accounts can be rejected or treated as non-privileged users.

### 2.5 Moderation Logs

**Table:** `moderation_logs`

| Column        | Type         | Notes                                                |
|---------------|--------------|------------------------------------------------------|
| id            | BIGINT PK    |                                                      |
| admin_user_id | BIGINT FK    | References `users.id`                                |
| feedback_id   | BIGINT FK    | References `feedback.id`                             |
| action        | VARCHAR(50)  | `publish`, `hide`, `delete`, `approve_pending`, etc. |
| reason        | TEXT         | Nullable; optional comment from admin                |
| created_at    | DATETIME     |                                                      |

**Laravel Model:** `ModerationLog`

---

## 3. Localization (fi/en)

### 3.1 Language Handling

- Supported locales: `fi` (default) and `en`.
- Laravel’s `config/app.php`:
  - `locale` set to `fi`.
  - `fallback_locale` set to `en`.
- Middleware to set locale order:
  1. Query string parameter `?lang=fi` or `?lang=en` (highest priority).
  2. Authenticated user preference (for admins).
  3. Browser `Accept-Language` header.
  4. Default app locale.

### 3.2 Translation Files

- Directory structure:

  - `lang/fi/`  
    - `auth.php`  
    - `pagination.php`  
    - `validation.php`  
    - `messages.php` (custom UI texts: buttons, labels, headings, toasts)  
  - `lang/en/`  
    - same files/mirrored keys as `fi`

- All UI text keys used in Blade / controllers and passed to Svelte via Inertia are defined in these files.

### 3.3 Passing Translations to Svelte

- Use a shared Inertia prop (via `HandleInertiaRequests` middleware) like:
  - `props['locale']` – current locale (`fi` or `en`).
  - `props['translations']` – subset of keys needed for given page, e.g.:

```php
// Example in a controller or a shared props middleware
't' => [
    'feedback' => [
        'title' => __('messages.feedback.title'),
        'submit' => __('messages.feedback.submit'),
        'placeholder' => __('messages.feedback.placeholder'),
        'success' => __('messages.feedback.success'),
        'error' => __('messages.feedback.error'),
    ],
]
```

- In Svelte:

```svelte
<script>
  export let t; // translations prop

  const labelSubmit = t.feedback.submit;
</script>

<button class="btn-primary">{labelSubmit}</button>
```

### 3.4 Language Switcher

- Global component (e.g. in layout):

  - Drop-down or simple toggle `FI | EN`.
  - On change, navigate to same route with `?lang=<code>` parameter.
  - Setting is stored:
    - In session (server-side) or short-lived cookie.
    - For admins, optional DB field `preferred_locale` in `users` table (not mandatory).

---

## 4. Routing & Controllers

### 4.1 Public Routes (web.php)

```php
// Board list (optional hub)
Route::get('/', [BoardController::class, 'index']);

// Single board view
Route::get('/b/{slug}', [BoardController::class, 'show']);

// Submit feedback
Route::post('/b/{slug}/feedback', [FeedbackController::class, 'store'])
    ->middleware('throttle:feedback-submissions');

// Toggle vote for a feedback item
Route::post('/feedback/{feedback}/vote', [FeedbackVoteController::class, 'toggle'])
    ->middleware('throttle:feedback-votes');
```

### 4.2 Admin Routes

```php
// Google OAuth
Route::get('/auth/google/redirect', [AuthController::class, 'redirectToGoogle']);
Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback']);

Route::middleware(['auth', 'can:access-admin'])->prefix('admin')->group(function () {
    Route::get('/', [Admin\DashboardController::class, 'index']);

    // Board management
    Route::get('/boards', [Admin\BoardController::class, 'index']);
    Route::get('/boards/create', [Admin\BoardController::class, 'create']);
    Route::post('/boards', [Admin\BoardController::class, 'store']);
    Route::get('/boards/{board}/edit', [Admin\BoardController::class, 'edit']);
    Route::put('/boards/{board}', [Admin\BoardController::class, 'update']);
    Route::delete('/boards/{board}', [Admin\BoardController::class, 'archive']);

    // Feedback management per board
    Route::get('/boards/{board}/feedback', [Admin\FeedbackController::class, 'index']);
    Route::post('/feedback/{feedback}/moderate', [Admin\FeedbackController::class, 'moderate']);

    // Moderation log
    Route::get('/moderation-logs', [Admin\ModerationLogController::class, 'index']);
});
```

### 4.3 Controller Responsibilities (recap)

- `BoardController@index`  
  - Fetch public boards (`is_public = true`, not archived) and return localized Inertia view.
- `BoardController@show`  
  - Resolve board by slug, localized title/description come from DB (board name/desc not translated per locale in this version).
  - Fetch `published` feedback, sort options: top/newest, with pagination.
- `FeedbackController@store`  
  - Validate `content` (required, min/max length, localized error messages).
  - Generate `creator_fingerprint` from IP + UA + secret.
  - Run `ContentModerationService` → determine `moderation_state`.
  - Save feedback, return localized success message or error.
- `FeedbackVoteController@toggle`  
  - Generate `voter_fingerprint`.
  - Add/remove vote, update `votes_count`.
  - Return JSON with new `votes_count`.
- `Admin` controllers as described in FDS.

---

## 5. Frontend Structure (Inertia + Svelte + Tailwind)

### 5.1 Pages

- `resources/js/Pages/Board/Index.svelte`
  - Board list page (public boards).
  - Uses Tailwind for responsive grid of boards.
  - Uses `t.board.*` translations for headings.
- `resources/js/Pages/Board/Show.svelte`
  - Displays a single board with:
    - Board title and description (from DB).
    - Sort controls (Top / Newest), labels from `t.feedback.sort.*`.
    - Feedback submission form (textarea + button, labels from `t.feedback.*`).
    - Feedback list with vote buttons (tooltips/text from `t.feedback.vote.*`).
- `resources/js/Pages/Admin/*` (Dashboard, Boards, Feedback, Logs)
  - All labels, headings, buttons are localized using provided `t.*` props.
  - Tailwind for tables, forms, responsive layout.

### 5.2 Tailwind Usage

- Tailwind configured and imported in main Svelte entry.
- Use utility classes for:
  - Layout (`flex`, `grid`, `p-4`, `gap-4`, `max-w-4xl`, `mx-auto`).
  - Typography (`text-lg`, `font-semibold`, `text-gray-700`).
  - Feedback cards:
    - `rounded-lg`, `shadow`, `bg-white`, `p-4`, `mb-4`.
  - Buttons:
    - `bg-blue-600`, `text-white`, `rounded`, `px-4`, `py-2`, `hover:bg-blue-700`.
- Ensure adequate contrast and spacing for usability.

---

## 6. Content Moderation & Data Handling

### 6.1 Fingerprints & Privacy

- `creator_fingerprint` and `voter_fingerprint` are hashes of:
  - IP address + user agent + secret salt.
- Implemented with a helper:

```php
class Fingerprint
{
    public static function fromRequest(Request $request): string
    {
        $ip = $request->ip() ?? '0.0.0.0';
        $ua = $request->userAgent() ?? 'unknown';
        $secret = config('app.key');

        return hash('sha256', $ip . '|' . $ua . '|' . $secret);
    }
}
```

- Raw IP is not stored in DB; only in server logs.

### 6.2 ContentModerationService

**Interface:**

```php
class ContentModerationService
{
    public function analyze(string $text): ModerationResult
    {
        // Rule-based checks against keyword lists
    }
}

class ModerationResult
{
    public function __construct(
        public string $status, // 'ok', 'needs_review', 'reject'
        public ?string $reason = null
    ) {}
}
```

**Config file:** `config/content_moderation.php`

```php
return [
    'enabled' => true,
    'keywords' => [
        'reject' => [
            // offensive words etc.
        ],
        'needs_review' => [
            // borderline phrases
        ],
    ],
];
```

---

## 7. Error Handling Strategy

### 7.1 User-Facing Errors

- Use Laravel validation with localization:
  - `resources/lang/fi/validation.php`
  - `resources/lang/en/validation.php`
- Display validation errors near the feedback textarea using Inertia form helpers.
- On moderation rejection (`reject`):
  - Return a translated error message from `messages.php` (fi/en).

### 7.2 Backend Error Handling

- Centralized exception handling in `Handler.php`:
  - Log errors to default log channel.
  - Return generic translated error messages for 500 errors if needed.
- Ensure admin routes are protected (`auth` + `can:access-admin`).

### 7.3 Rate Limiting

- Define custom rate limiters in `RouteServiceProvider` or `AppServiceProvider`:

```php
RateLimiter::for('feedback-submissions', function (Request $request) {
    return Limit::perMinutes(10, 10)->by($request->ip());
});

RateLimiter::for('feedback-votes', function (Request $request) {
    return Limit::perMinutes(10, 30)->by($request->ip());
});
```

- On 429, use localized message from `messages.php`.

---

## 8. Testing Plan

### 8.1 Unit Tests

- `ContentModerationServiceTest`
  - Text with no keywords → `ok`.
  - Text with review keywords → `needs_review`.
  - Text with reject keywords → `reject`.
- `FingerprintTest`
  - Same request data → same hash.
  - Different IPs/UA → different hashes.

### 8.2 Feature Tests (Laravel)

- **Localization**
  - `test_default_locale_is_fi`
  - `test_locale_can_be_switched_to_en_via_query_param`
  - `test_validation_errors_are_localized_according_to_locale`

- **Board & Feedback**
  - `test_public_board_list_shows_only_public_non_archived_boards`
  - `test_board_show_displays_published_feedback_only`
  - `test_anonymous_can_submit_feedback_ok_state_publishes_immediately`
  - `test_anonymous_feedback_with_bad_content_is_rejected`
  - `test_anonymous_feedback_needs_review_saved_as_pending_and_not_visible_publicly`

- **Voting**
  - `test_anonymous_can_upvote_feedback_once`
  - `test_voting_twice_toggles_vote_if_enabled`
  - `test_vote_count_is_updated_correctly`

- **Admin**
  - `test_admin_can_create_edit_archive_boards`
  - `test_non_admin_cannot_access_admin_routes`
  - `test_admin_can_publish_pending_feedback`
  - `test_admin_can_hide_and_delete_feedback`
  - `test_moderation_actions_are_logged`

- **Auth**
  - `test_google_oauth_callback_creates_admin_user_for_configured_email`
  - `test_non_admin_email_is_denied_admin_access`

### 8.3 Browser / UI Tests (Optional but Recommended)

- Using Laravel Dusk or Cypress:
  - Submit feedback as anonymous user, see it appear.
  - Change language to `en` and verify labels change.
  - Upvote and see real-time update.
  - Admin login → board creation → moderation workflow.

---

## 9. Configuration & Environment

- `.env` settings:

  - `APP_LOCALE=fi`
  - `APP_FALLBACK_LOCALE=en`
  - `ADMIN_EMAILS=admin@example.com,teacher@example.com`
  - Google OAuth:
    - `GOOGLE_CLIENT_ID`
    - `GOOGLE_CLIENT_SECRET`
    - `GOOGLE_REDIRECT_URI`
  - Content moderation:
    - `CONTENT_MODERATION_ENABLED=true`

- Tailwind config:

  - `content` paths include:
    - `resources/views/**/*.blade.php`
    - `resources/js/**/*.svelte`
    - `resources/js/**/*.vue` (if any), etc.

---

## 10. Security & Privacy Notes

- No student identities or emails are collected in the feedback flow.
- Only hashed fingerprints (and IP in server logs) are used for spam control.
- HTTPS must be enforced in production.
- Admin access is strictly limited to whitelisted Google accounts.
- CSRF tokens are applied to all POST/PUT/DELETE routes.

