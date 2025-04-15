# Copilot Development Instructions for PKKI ITERA

This document outlines the programming and architectural rules that GitHub Copilot (or any developer) should follow while contributing to the PKKI ITERA platform.

## 🧠 Tech Stack
- **Frontend**: React.js + Inertia.js
- **Backend**: Laravel 10
- **Admin Panel**: Filament PHP
- **Database**: MySQL

---

#### Included Plugins

This project leverages several Filament plugins:

| **Plugin**                                                                                          | **Purpose**                                         |
| :-------------------------------------------------------------------------------------------------- | :-------------------------------------------------- |
| [Filament Spatie Media Library](https://github.com/filamentphp/spatie-laravel-media-library-plugin) | Media management                                    |
| [Filament Spatie Settings](https://github.com/filamentphp/spatie-laravel-settings-plugin)           | Site configuration                                  |
| [Shield](https://github.com/bezhanSalleh/filament-shield)                                           | Permissions management                              |
| [Breezy](https://github.com/jeffgreco13/filament-breezy)                                            | User profile management                             |
| [Logger](https://github.com/z3d0x/filament-logger)                                                  | System activity logging                             |
| [Filament Menu Builder](https://github.com/datlechin/filament-menu-builder)                         | Custom navigation menus                             |

## 📜 Code of Conduct
As a developer, you are expected to follow the best practices and principles outlined below. This will ensure that the codebase remains clean, maintainable, and secure.

## 🔒 Principles to Follow

### ✅ Best Practices (General)
- Use **clear and consistent naming conventions** (camelCase for JS, snake_case for PHP).
- Always **write modular and reusable code**.
- Add **comments** to clarify non-obvious logic.
- Break down large components or classes into smaller pieces.
- Validate and sanitize all input on both client and server side.
- Use **environment variables** and Laravel configuration files correctly.
- Avoid hardcoding values.

### 🧱 SOLID Principles
- **Single Responsibility**: Keep functions/components/classes focused on one task.
- **Open/Closed**: Extend features with new classes or methods, don't modify existing core logic.
- **Liskov Substitution**: Ensure child classes can stand in for parent classes without breaking.
- **Interface Segregation**: Break large interfaces into smaller, more specific ones.
- **Dependency Inversion**: Depend on abstractions. Use dependency injection (Laravel's Service Container).

---

## ⚛️ React + Inertia.js Instructions
- Use **functional components** and **hooks**.
- Use **axios** or Inertia's `$inertia` for AJAX requests.
- Structure components in a clear `pages`, `components`, `layouts` folder hierarchy.
- Always map backend Laravel routes to Inertia page components.
- Use **controlled components** for all forms.
- Validate form inputs client-side before sending.

---

## 🧩 Laravel Instructions
- Use **Eloquent relationships** for all database operations.
- Follow RESTful resource controller patterns.
- Use Laravel Form Requests for **validation**.
- Use **Resource Collections** and **API Resources** to format JSON responses.
- All business logic should be inside Services or Actions (not controllers).
- Log important actions using Laravel’s built-in logging system.

---

## 🎛️ Filament Admin Instructions
- Use Filament for managing users, roles, submission workflows, and certificate management.
- Use Filament’s `Resources` and `Tables` for fast CRUD generation.
- Extend Filament’s permission system using **Spatie Permission**.
- Create custom widgets for dashboards if needed.

---

## 🗃️ MySQL & Database Practices
- Always run **migrations** to modify schema.
- Use **foreign key constraints** to maintain data integrity.
- Add indexes on frequently queried columns.
- Name tables and columns clearly.

---

## 🛠️ Tools & Testing
- Use Laravel **Pest** or **PHPUnit** for backend tests.
- Use **Jest** or **React Testing Library** for frontend tests.
- Document API endpoints using **Laravel Swagger / Scribe**.
- Lint code using **Prettier** (JS) and **Laravel Pint** (PHP).

---

> Copilot must always generate clean, maintainable, secure code that respects the principles and frameworks stated above.