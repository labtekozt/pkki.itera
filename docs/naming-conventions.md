# Code Naming Conventions

## 1. File Naming

- **PHP Classes**: One class per file, named exactly like the class - `PascalCase.php`
  ```
  UserController.php
  PaymentService.php
  ```

- **Blade Templates**: Use `kebab-case.blade.php`
  ```
  user-profile.blade.php
  payment-form.blade.php
  ```

- **Config/Language Files**: Use `snake_case.php`
  ```
  app_settings.php
  error_messages.php
  ```

- **JavaScript Files**: Use `kebab-case.js` for modules/components
  ```
  user-management.js
  data-table.js
  ```

- **CSS/SCSS Files**: Use `kebab-case.css/scss`
  ```
  main-layout.scss
  button-styles.scss
  ```

- **Migration Files**: Follow Laravel's timestamp prefix convention
  ```
  2023_01_15_154322_create_users_table.php
  ```

## 2. Class Naming

- Use `PascalCase` for all class names
- Make names descriptive and avoid abbreviations
- Use suffixes to indicate type:
  - Controllers: `UserController`
  - Models: `User`, `BrandDetail`
  - Middleware: `AuthenticateUser`
  - Services: `PaymentProcessingService`
  - Repositories: `UserRepository`
  - Events: `UserRegistered`
  - Listeners: `SendWelcomeEmail`
  - Jobs: `ProcessPayment`
  - Factories: `UserFactory`
  - Seeders: `UserSeeder`

## 3. Method/Function Naming

- Use `camelCase` for methods and functions
- Start with a verb that describes the action:
  - `getUser()` - retrieves something
  - `createUser()` - creates something
  - `updateProfile()` - updates something
  - `deleteAccount()` - deletes something
  - `isValid()` - boolean check
  - `hasPermission()` - boolean check
  
- Prefix boolean methods with `is`, `has`, `can`, or `should`
  ```php
  public function isActive(): bool
  public function hasSubscription(): bool
  public function canEditPost(): bool
  ```

- For private helper methods, be descriptive about their purpose
  ```php
  private function validateUserInput(): void
  private function normalizePhoneNumber(string $phone): string
  ```

## 4. Variable Naming

- Use `camelCase` for variables
- Make names descriptive and readable:
  ```php
  // Bad
  $u = User::find(1);
  $s = $this->getSettings();
  
  // Good
  $user = User::find(1);
  $applicationSettings = $this->getSettings();
  ```

- Use clear, descriptive names even if they're longer
  ```php
  // Not ideal
  $res = $this->getResult();
  
  // Better
  $searchResults = $this->getSearchResults();
  ```

- Prefix booleans with `is`, `has`, `can` or `should`:
  ```php
  $isActive = true;
  $hasSubscription = false;
  $canEdit = $user->hasRole('editor');
  ```

- Use plural form for arrays and collections:
  ```php
  $users = User::all();
  $activeUsers = $users->where('status', 'active');
  ```

## 5. Database Naming

- **Tables**: Use `snake_case` and plural form
  ```
  users
  product_categories
  order_items
  ```

- **Pivot Tables**: Combine the two related table names in singular form, alphabetically
  ```
  role_user
  category_product
  ```

- **Columns**: Use `snake_case` and singular form
  ```
  first_name
  email_address
  created_at
  ```

- **Primary Keys**: Use `id` for primary key
- **Foreign Keys**: Use singular table name followed by `_id`
  ```
  user_id
  product_id
  ```

- **Indexes**: Name format `{table}_{column(s)}_{type}`
  ```
  users_email_unique
  products_name_index
  ```

## 6. Constants

- Use uppercase `SNAKE_CASE` for constants and enum values
  ```php
  const API_VERSION = 'v1';
  const MAX_LOGIN_ATTEMPTS = 5;
  ```

- For class constants, use descriptive names:
  ```php
  class User
  {
      const STATUS_ACTIVE = 'active';
      const STATUS_INACTIVE = 'inactive';
      const ROLE_ADMIN = 'admin';
  }
  ```

## 7. Routes

- Use `kebab-case` for URL segments
  ```
  /user-profiles
  /product-categories
  ```

- Route names should use `dot.notation` (Laravel convention)
  ```php
  Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
  Route::get('/users/{id}/edit', [UserController::class, 'edit'])->name('users.edit');
  ```

## 8. General Principles

1. **Consistency**: Follow conventions consistently across the entire codebase
2. **Clarity**: Names should clearly express what they represent
3. **Brevity**: Use concise names but not at the expense of clarity
4. **Avoid**: Abbreviations, meaningless names (e.g., `$x, $y`), and overly generic names
5. **Comments**: Add comments for complex logic, not to explain poor naming choices

## 9. Laravel-Specific

- **FormRequests**: Suffix with `Request` (e.g., `CreateUserRequest`)
- **Resources**: Suffix with `Resource` (e.g., `UserResource`)
- **Policies**: Suffix with `Policy` (e.g., `PostPolicy`)
- **Observers**: Suffix with `Observer` (e.g., `UserObserver`)
- **Traits**: Use `PascalCase` with descriptive names (e.g., `Notifiable`, `HasApiTokens`)
