# PKKI ITERA Web App - Extended Project Requirements

## Overview
The PKKI ITERA platform is designed for the digital management and submission of Intellectual Property (HKI) by civitas and non-civitas users. The system facilitates submission tracking, document verification, and certification workflows.

**Technology Stack:**

- **React** for frontend user interfaces
- **Inertia.js** for seamless frontend-backend routing
- **Laravel** as the primary backend framework
- **Filament** for admin dashboards and content management
- **MySQL** as the relational database

---

## Functional Requirements

### 1. User Management

| ID    | Feature                     | Description                                                                                  |
| ----- | --------------------------- | -------------------------------------------------------------------------------------------- |
| FR001 | Role-Based Access Control   | Four user types: Super Admin, Admin, Sivitas, Non-Civitas User. Permissions differ per role. |
| FR002 | Member Registration & Login | Supports email/username login and registration. Password must be hashed.                     |
| FR003 | Profile Management          | Each user can update their own profile data. Roles can only be updated by Super Admin.       |

### 2. Submission Management

| ID    | Feature                | Description                                                                                    |
| ----- | ---------------------- | ---------------------------------------------------------------------------------------------- |
| FR004 | Create Submission      | Users can create new HKI submission drafts (Paten, Hak Cipta, Merek Dagang, Desain Industri).  |
| FR005 | Draft Management       | Users can save drafts, which are editable. Drafts cannot be submitted unless complete.         |
| FR006 | Submit Submission      | Submissions are validated and moved into review flow. Trigger notification to reviewer.        |
| FR007 | Document Upload        | Users must upload required documents per submission type. Uploads are versioned and validated. |
| FR008 | Submission List        | Users can view all their submissions; Admins can view all submissions.                         |
| FR009 | Submission Detail View | Shows document list, history, reviewer notes, and current status.                              |

### 3. Review Workflow (Focus: Filament Admin Panel)

| ID    | Feature               | Description                                                                                   |
| ----- | --------------------- | --------------------------------------------------------------------------------------------- |
| FR010 | Workflow Configuration | Admin can configure review stages per submission type via Filament (e.g., Faculty → LPPM → Legal). |
| FR011 | Reviewer Assignment    | Admin assigns reviewer(s) at each stage via Filament dashboard. Can view history of assignments. |
| FR012 | Review Interface       | Filament shows submission with attachments, comments panel, approve/revise/reject buttons.     |
| FR013 | Review Actions         | Reviewer submits their action with comments. System logs action and advances workflow or returns for revision. |
| FR014 | Review Notifications   | Reviewers and submitters get notified on each change (status update, comments, revision needed). |
| FR015 | Auto Lock              | System locks submission after 3 revisions; Admin can unlock via Filament override.             |
| FR016 | Review History         | Filament displays full chronological log of actions, reviewer decisions, and feedback.         |

### 4. Certification (Focus: Admin Certification Flow)

| ID    | Feature              | Description                                                                                   |
| ----- | -------------------- | --------------------------------------------------------------------------------------------- |
| FR017 | Certification Trigger | After final review approval, admin marks submission as "Approved for Certification" via Filament. |
| FR018 | Certificate Generator | System generates a certificate (PDF) with dynamic fields (title, name, date, unique ID).       |
| FR019 | Certificate Upload    | Admin can upload or regenerate certificates in Filament panel. Stored in secure location.       |
| FR020 | Certificate Access    | Only submission creators can view/download the certificate from their dashboard.               |

### 5. Notifications

| ID    | Feature              | Description                                                                  |
| ----- | -------------------- | ---------------------------------------------------------------------------- |
| FR021 | In-App Notifications | Users receive real-time updates about submission status and review outcomes. |
| FR022 | Email Notifications  | Key actions (submission, revision, approval) trigger email alerts.           |

### 6. Guide & FAQ Management

| ID    | Feature        | Description                                                            |
| ----- | -------------- | ---------------------------------------------------------------------- |
| FR023 | CRUD Guides    | Admins can manage guide content per category (e.g., Paten, Hak Cipta). |
| FR024 | FAQ Management | Admins can create/update FAQs. Users can read guides and FAQs.         |

### 7. Submission Tracking & Visualization

| ID    | Feature                  | Description                                                                            |
| ----- | ------------------------ | -------------------------------------------------------------------------------------- |
| FR025 | Real-Time Tracking       | Submission status is visible only to the original submitter via a tracking page.       |
| FR026 | Dashboard Visualizations | Admin dashboard shows statistics (submission counts, status breakdowns, by year/type). |

---

## Non-Functional Requirements

### 1. 📈 Performance

- The system must support at least **500 concurrent users** without significant delay (< 2 seconds response time).
- Submission forms must save drafts within **1 second**.
- Dashboard visualizations must load under **3 seconds** even with 1000+ records.

### 2. 🔐 Security

- All user input must be validated and sanitized.
- Use HTTPS for all data transmission.
- Authentication must use Laravel's secure hashing system.
- Roles (Admin, Sivitas, Pengguna, Super Admin) must be protected with access control logic.
- Submissions and certificates must only be visible to their respective owners unless assigned otherwise.
- Track login history and alert suspicious activities.

### 3. ⚙️ Reliability

- System must achieve **99.9% uptime** per month.
- Implement auto-retry on background jobs (e.g., email notifications, status updates).
- Auto-backup MySQL database daily at midnight.

### 4. 🎯 Usability

- The interface should follow a **clean, modern UI/UX** using Filament on admin side and React for public-facing.
- Users should receive clear prompts on status changes and required actions.
- All input forms must have placeholders, labels, and client-side validation.
- Use progress indicators for multi-stage submissions.

### 5. 🌍 Accessibility

- System must follow basic WCAG 2.1 AA compliance:
  - Support keyboard navigation.
  - Use ARIA labels for dynamic components.
  - Ensure sufficient color contrast.

### 6. 🌐 Compatibility

- Must work on latest versions of Chrome, Firefox, Edge, and Safari.
- Mobile-friendly: fully responsive from 360px width upward.

### 7. 🛡️ Maintainability

- Code should be structured according to Laravel and React conventions.
- Modular services and reusable components must be prioritized.
- Admin configurations should not require code changes (dynamic setup via Filament).

### 8. 🧪 Testability

- All critical user paths (submission, review, login, download) must have automated tests.
- Support unit, feature, and integration tests across frontend and backend.

### 9. 🗃️ Scalability

- System should support growth to 10,000+ users without significant changes.
- Use pagination and query optimization for large datasets.
- Use caching (Laravel Cache, Redis) for heavy pages like dashboards.

### 10. 📚 Documentation

- Developer documentation must be maintained in markdown.
- Provide user guide and FAQ editable through the system.
- API endpoints must be documented using Scribe or Swagger.

---

These requirements ensure that PKKI ITERA is scalable, manageable, and user-centric, with robust workflows through the Filament admin panel and a secure certification process.

