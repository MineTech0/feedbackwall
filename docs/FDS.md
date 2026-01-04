# Functional Design Specification (FDS)
## Anonyymi tanssikurssipalautteen järjestelmä

## 1. Overview

### 1.1 Purpose

This system collects **anonymous feedback for dance courses**.  
Students can submit text feedback and see other students’ feedback for the same course, as well as upvote feedback they agree with.  
Teachers/admins can create and manage course-specific boards and moderate the content.

The user interface must support **bilingual localization (fi/en)** so that both Finnish and English speaking users can use the system. The active language can be chosen in the UI or derived from browser language, with a manual override.

### 1.2 Scope

In scope:

- Public, anonymous feedback boards per course
- Anonymous text-only feedback submission
- Upvoting of feedback entries
- Multiple boards (one per course or group)
- Public board list (optional hub)
- Admin interface for:
  - Board management
  - Feedback moderation (hide/show/delete)
  - Viewing pending/flagged feedback
- Basic content filtering for harmful or inappropriate messages
- **UI localization in Finnish and English (fi/en)**

Out of scope (for this version):

- User accounts for students
- Rich media feedback (images, files, audio, video)
- Comment threads, replies, or discussions
- Workflow states like “planned / in progress / done”
- Mobile apps (native) – mobile web is supported via responsive UI

### 1.3 Stakeholders

- **Dance students (anonymous users)** – submit and read feedback, upvote.
- **Teachers / course organizers (admins)** – create boards, review feedback, moderate.
- **System owner** – responsible for deployment, configuration, and maintenance.

Localization stakeholders:

- Content owners who maintain translations, labels and messages in fi/en.

---

## 2. User Roles and Permissions

### 2.1 Anonymous User (Student)

**Authentication:** None.

**Capabilities:**

- View a public list of active boards (if enabled).
- Open a board via URL (link or QR code).
- Read published feedback items for that board.
- Submit new feedback (single text field).
- Upvote/unvote feedback items (limited to 1 vote per item per browser/fingerprint).
- Switch UI language between fi/en (e.g., toggle or dropdown).

**Restrictions:**

- Cannot access admin routes or admin UI.
- Cannot see hidden or pending feedback items.
- Cannot manage boards.

### 2.2 Admin (Teacher / Organizer)

**Authentication:** Google OAuth via Laravel Socialite.  
Only emails configured as admins may access admin panel.

**Capabilities:**

- Access admin dashboard (localized fi/en).
- Create new boards (courses) and edit existing boards.
- Archive boards (disable new feedback, keep data visible or hidden as configured).
- View all feedback for a board (published, pending, hidden).
- Change moderation state for a feedback item:
  - Publish (visible to students)
  - Hide (not visible to students)
  - Delete (remove from system or soft-delete)
- View list of feedback pending review (flagged by content filter).
- View a basic moderation log/history.

**Restrictions:**

- Must be authenticated and authorized (admin flag).
- Admin rights are not granted to arbitrary Google users.

---

## 3. High-Level User Journeys

### 3.1 Student submits anonymous feedback

1. Student opens a board URL (e.g. `/b/salsa-kevat-2025`) or via QR code.
2. Student sees:
   - Board title and description.
   - List of existing feedback items with vote counts.
   - UI texts in their chosen language (fi/en).
3. Student writes feedback into a textarea and clicks **"Send feedback"** (or localized equivalent).
4. System validates input (non-empty, length constraints).
5. System runs content moderation:
   - If safe → feedback is **published** immediately.
   - If suspicious → feedback is stored as **pending** for admin review.
   - If clearly harmful → feedback is rejected; student gets a generic error message (localized).
6. Student sees success or error message in the selected language.

### 3.2 Student upvotes feedback

1. Student scrolls feedback list.
2. Student clicks upvote icon/button on a feedback item.
3. System checks if this browser/fingerprint has already voted for this item.
   - If not voted → add vote and increment visible vote count.
   - If already voted and toggle is allowed → remove vote and decrement vote count.
4. UI updates vote count and visual state (highlighted/not highlighted), using localized labels/tooltips.

### 3.3 Admin creates a new board

1. Admin logs in using **"Login with Google"**.
2. System verifies email against allowed admin emails and sets `is_admin = true` for authorized users.
3. Admin navigates to **Admin → Boards**.
4. Admin clicks **"Create Board"** and fills:
   - Name
   - Description
   - Slug (auto-generated but editable)
   - Visibility: Public / Private (public list vs only direct link)
5. Admin saves board; board is now accessible at `/b/{slug}`.
6. The admin UI labels, buttons and messages are shown in the admin’s selected language (fi/en).

### 3.4 Admin moderates feedback

1. Admin opens **Admin → Boards → [Board] → Feedback**.
2. Admin sees all feedback items:
   - Content preview
   - Votes
   - Created time
   - Moderation state (Published / Hidden / Pending)
3. Admin actions:
   - **Publish** a pending feedback (makes it visible).
   - **Hide** a published feedback (removes it from public view).
   - **Delete** a feedback (for severe or irrelevant content).
4. Each action is logged in the moderation log.
5. All admin messages and statuses are shown in chosen language fi/en.

---

## 4. Functional Requirements

### 4.1 Boards

- FR-BOARD-1: The system must support multiple boards.
- FR-BOARD-2: Each board must have a unique slug to be used in URLs.
- FR-BOARD-3: Boards can be marked as **public** (visible in board list) or **private** (accessible only via direct URL).
- FR-BOARD-4: Admin must be able to create, edit, and archive boards.
- FR-BOARD-5: When a board is archived:
  - New feedback submissions must be disabled.
  - Existing feedback may remain visible or be hidden based on config.

### 4.2 Feedback

- FR-FEEDBACK-1: Students must be able to submit anonymous feedback via a single text field.
- FR-FEEDBACK-2: Feedback must be associated with exactly one board.
- FR-FEEDBACK-3: Feedback must have a creation timestamp.
- FR-FEEDBACK-4: Feedback must have a moderation state:
  - `published` – visible to students.
  - `pending_review` – visible only in admin panel.
  - `hidden` – not visible to students.
- FR-FEEDBACK-5: Students can only see feedback with state `published`.
- FR-FEEDBACK-6: System must apply content filtering before deciding the initial moderation state (published/pending/rejected).

### 4.3 Voting

- FR-VOTE-1: Students must be able to upvote published feedback items.
- FR-VOTE-2: A single browser/fingerprint may only have one active vote per feedback item.
- FR-VOTE-3: Vote toggling must be supported (upvote/unvote).
- FR-VOTE-4: Vote counts must be cached/denormalized on the feedback item for fast listing.
- FR-VOTE-5: Feedback lists must support sorting by:
  - most upvotes
  - newest first

### 4.4 Admin Panel

- FR-ADMIN-1: Admin login must use Google OAuth.
- FR-ADMIN-2: Only configured emails may access admin routes.
- FR-ADMIN-3: Admin must see:
  - Board list with counts (feedback, votes).
  - Per-board feedback list with moderation controls.
- FR-ADMIN-4: Admin must be able to:
  - Publish pending feedback.
  - Hide or unhide feedback.
  - Delete feedback.
- FR-ADMIN-5: Admin must see a list of feedback in `pending_review` state.
- FR-ADMIN-6: Admin actions on feedback must be recorded in a moderation log.

### 4.5 Content Filtering & Rate Limiting

- FR-FILTER-1: System must analyze feedback text using a rule-based content filter.
- FR-FILTER-2: The filter must be able to return three outcomes:
  - OK → auto-publish.
  - Needs Review → set to `pending_review`.
  - Reject → do not store; return error.
- FR-FILTER-3: System must apply reasonable rate limiting on feedback submissions per IP/fingerprint.
- FR-FILTER-4: System must apply rate limiting on voting actions.

### 4.6 UI/UX Requirements

- FR-UI-1: Frontend must be built with **Inertia.js + Svelte 5 + Tailwind CSS**.
- FR-UI-2: Board pages and admin pages must be responsive and usable on mobile devices.
- FR-UI-3: Feedback submission must give clear success or error messages.
- FR-UI-4: Upvote interactions must update visually without full page reloads.
- FR-UI-5: All labels, buttons, validation errors, and system messages must be localizable in **fi/en**.
- FR-UI-6: The user must be able to switch language (fi/en) from the UI, and the system should also support defaulting to browser language.

---

## 5. Non-Functional Requirements (High Level)

- NFR-SEC-1: All admin actions must require authentication and authorization.
- NFR-SEC-2: No personally identifiable student data is stored (anonymous usage).
- NFR-PERF-1: Board pages must load and render in under ~1s on typical connections for moderate data volumes.
- NFR-RELIAB-1: System must handle cases where external services (Google OAuth) are temporarily unavailable with friendly messages.
- NFR-MAINT-1: System must be easily configurable (e.g., admin email list, filtering thresholds, localization files) via environment/config files.
- NFR-I18N-1: Adding a new language besides fi/en should require only translation changes (no code changes to logic).
