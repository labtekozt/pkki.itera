# Copilot Instructions for PKKI ITERA Laravel Project

## Project Overview
This is a Laravel application for PKKI ITERA (Pusat Kerjasama dan Komersialisasi Inovasi Institut Teknologi Sumatera) built with Filament admin panel, focusing on intellectual property management, submissions, and user management with role-based access control.

## Technology Stack
- **Framework**: Laravel 10.x
- **Admin Panel**: Filament 3.x
- **Database**: MySQL
- **Authentication**: Laravel Sanctum + Filament Auth
- **Authorization**: Spatie Permission + Filament Shield
- **Frontend**: Livewire + Alpine.js for filament , inertia + react for frontend 
- **Build Tool**: Vite
- **CSS Framework**: Tailwind CSS
- **File Management**: Spatie Media Library

## Architecture Principles

### 1. Domain-Driven Design (DDD) Patterns
```php
// Organize code by business domains
app/
├── Models/          // Eloquent models (Entities)
├── Services/        // Business logic layer
├── Repositories/    // Data access layer
├── Policies/        // Authorization logic
├── Observers/       // Model event handlers
├── Events/          // Domain events
├── Listeners/       // Event handlers
└── Enums/          // Value objects
```

### 2. SOLID Principles Implementation
- **Single Responsibility**: Each class should have one reason to change
- **Open/Closed**: Open for extension, closed for modification
- **Liskov Substitution**: Derived classes must be substitutable for base classes
- **Interface Segregation**: Clients shouldn't depend on unused interfaces
- **Dependency Inversion**: Depend on abstractions, not concretions

## Code Standards & Best Practices

### Laravel Conventions
```php
// Model naming: Singular, PascalCase
class User extends Model {}
class SubmissionType extends Model {}

// Controller naming: PascalCase + Controller suffix
class UserController extends Controller {}
class SubmissionController extends Controller {}

// Migration naming: snake_case with descriptive action
2024_01_01_000000_create_users_table.php
2024_01_01_000001_add_email_verification_to_users_table.php

// Route naming: snake_case with dot notation
Route::name('admin.')->group(function () {
    Route::resource('submissions', SubmissionController::class);
});
```

### Database Design Principles
```php
// Use UUIDs for primary keys (already implemented)
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class User extends Model
{
    use HasUuids;
    
    // Explicit fillable for mass assignment protection
    protected $fillable = [
        'fullname',
        'email',
        'password',
    ];
    
    // Define relationships with proper naming
    public function detail(): HasOne
    {
        return $this->hasOne(UserDetail::class);
    }
    
    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }
}
```

### Service Layer Pattern
```php
// Create services for complex business logic
namespace App\Services;

class SubmissionService
{
    public function __construct(
        private SubmissionRepository $repository,
        private NotificationService $notificationService
    ) {}
    
    public function createSubmission(array $data, User $user): Submission
    {
        DB::beginTransaction();
        
        try {
            $submission = $this->repository->create($data, $user);
            $this->notificationService->sendSubmissionCreatedNotification($submission);
            
            DB::commit();
            return $submission;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
```

### Repository Pattern
```php
// Use repositories for data access abstraction
namespace App\Repositories;

interface SubmissionRepositoryInterface
{
    public function findByUser(User $user): Collection;
    public function findByStatus(string $status): Collection;
}

class SubmissionRepository implements SubmissionRepositoryInterface
{
    public function findByUser(User $user): Collection
    {
        return Submission::where('user_id', $user->id)
            ->with(['submissionType', 'currentStage'])
            ->get();
    }
}
```

## Filament Best Practices

### Resource Organization
```php
// Use sections for better form organization
public static function form(Form $form): Form
{
    return $form->schema([
        Section::make('Basic Information')
            ->description('Enter the basic submission details')
            ->schema([
                TextInput::make('title')->required(),
                Select::make('submission_type_id')->required(),
            ]),
            
        Section::make('Documents')
            ->schema([
                SpatieMediaLibraryFileUpload::make('attachments')
                    ->collection('submissions')
                    ->multiple(),
            ]),
    ]);
}

// Use custom pages for complex workflows
class CreateSubmission extends CreateRecord
{
    use HasWizard;
    
    protected function getSteps(): array
    {
        return [
            Step::make('Basic Info'),
            Step::make('Details'),
            Step::make('Documents'),
        ];
    }
}
```

### Performance Optimization
```php
// Eager load relationships to prevent N+1 queries
public static function table(Table $table): Table
{
    return $table
        ->query(
            Submission::with(['user', 'submissionType', 'currentStage'])
        )
        ->columns([
            TextColumn::make('user.fullname'),
            TextColumn::make('submissionType.name'),
        ]);
}

// Use database transactions for data integrity
protected function afterCreate(): void
{
    DB::transaction(function () {
        $this->record->update(['status' => 'submitted']);
        $this->createInitialWorkflowStage();
    });
}
```

## Security Best Practices

### Authentication & Authorization
```php
// Use Spatie Permission for role-based access
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_user');
    }
    
    public function create(User $user): bool
    {
        return $user->can('create_user');
    }
}

// Implement proper validation
class CreateSubmissionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'submission_type_id' => 'required|exists:submission_types,id',
            'documents.*' => 'file|mimes:pdf,doc,docx|max:10240',
        ];
    }
}
```

### Data Protection
```php
// Use accessors/mutators for sensitive data
class User extends Model
{
    protected $hidden = ['password', 'remember_token'];
    
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];
    
    // Scope sensitive queries
    public function scopeVerified(Builder $query): Builder
    {
        return $query->whereNotNull('email_verified_at');
    }
}
```

## Error Handling & Logging

### Exception Handling
```php
// Create custom exceptions for domain-specific errors
class SubmissionException extends Exception
{
    public static function invalidStatus(string $status): self
    {
        return new self("Invalid submission status: {$status}");
    }
}

// Use structured logging
Log::channel('submissions')->info('Submission created', [
    'submission_id' => $submission->id,
    'user_id' => $user->id,
    'type' => $submission->submissionType->name,
]);
```

## Testing Standards

### Unit Testing
```php
class SubmissionServiceTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_can_create_submission(): void
    {
        $user = User::factory()->create();
        $data = [
            'title' => 'Test Submission',
            'submission_type_id' => SubmissionType::factory()->create()->id,
        ];
        
        $submission = app(SubmissionService::class)->createSubmission($data, $user);
        
        $this->assertInstanceOf(Submission::class, $submission);
        $this->assertEquals('Test Submission', $submission->title);
    }
}
```

### Feature Testing
```php
class SubmissionManagementTest extends TestCase
{
    public function test_user_can_create_submission(): void
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)
            ->post('/admin/submissions', [
                'title' => 'Patent Application',
                'submission_type_id' => SubmissionType::factory()->create()->id,
            ]);
            
        $response->assertRedirect();
        $this->assertDatabaseHas('submissions', ['title' => 'Patent Application']);
    }
}
```

## Performance Guidelines

### Database Optimization
```php
// Use indexes for frequently queried columns
Schema::table('submissions', function (Blueprint $table) {
    $table->index(['status', 'created_at']);
    $table->index(['user_id', 'submission_type_id']);
});

// Implement query scopes for complex queries
class Submission extends Model
{
    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }
    
    public function scopeRecent(Builder $query, int $days = 30): Builder
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}
```

### Caching Strategy
```php
// Cache expensive queries
class SubmissionService
{
    public function getSubmissionStats(): array
    {
        return Cache::remember('submission_stats', 3600, function () {
            return [
                'total' => Submission::count(),
                'pending' => Submission::byStatus('pending')->count(),
                'approved' => Submission::byStatus('approved')->count(),
            ];
        });
    }
}
```

## Code Review Checklist

### Before Committing
- [ ] Follow PSR-12 coding standards
- [ ] Add proper type hints and return types
- [ ] Include PHPDoc blocks for public methods
- [ ] Validate all user inputs
- [ ] Use database transactions for multi-step operations
- [ ] Add appropriate indexes for new queries
- [ ] Write tests for new functionality
- [ ] Update documentation if needed

### Filament Specific
- [ ] Use proper form validation
- [ ] Implement proper authorization checks
- [ ] Optimize table queries with eager loading
- [ ] Use appropriate field types for data
- [ ] Handle file uploads securely
- [ ] Implement proper error messages

## Deployment & Environment

### Production Checklist
```bash
# Optimize for production
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan icons:cache
php artisan shield:generate --all

# Database optimizations
php artisan migrate --force
php artisan db:seed --force
```

### Environment Variables
```env
# Security
APP_ENV=production
APP_DEBUG=false
APP_KEY=<strong-random-key>

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=pkki_itera_prod

# Mail
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@domain.com
MAIL_PASSWORD=your-app-password
```

## Common Patterns & Anti-Patterns

### ✅ Good Practices
```php
// Use dependency injection
class SubmissionController extends Controller
{
    public function __construct(
        private SubmissionService $submissionService
    ) {}
}

// Use resource collections for API responses
class SubmissionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'status' => $this->status,
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
```

### ❌ Anti-Patterns to Avoid
```php
// Don't put business logic in controllers
class SubmissionController extends Controller
{
    public function store(Request $request)
    {
        // ❌ Avoid this - business logic in controller
        $submission = new Submission();
        $submission->title = $request->title;
        $submission->save();
        
        // Send email here...
        // Update related records...
    }
}

// Don't use raw queries unless absolutely necessary
// ❌ Avoid this
DB::select('SELECT * FROM submissions WHERE status = ?', ['pending']);

// ✅ Use Eloquent instead
Submission::where('status', 'pending')->get();
```

## Documentation Standards

### Code Documentation
```php
/**
 * Create a new submission with proper workflow initialization.
 *
 * @param array $data The submission data
 * @param User $user The user creating the submission
 * @return Submission The created submission
 * @throws SubmissionException When submission data is invalid
 */
public function createSubmission(array $data, User $user): Submission
{
    // Implementation here...
}
```

### API Documentation
Use OpenAPI/Swagger standards for API documentation and maintain updated README.md files for major features.

---

## Contact & Resources

For questions about this codebase or implementation details:
- **Project**: PKKI ITERA
- **Documentation**: See `/docs` directory
- **Repository**: [PKKI ITERA GitHub](https://github.com/labtekozt/pkki.itera.git)

Remember: Code is read more often than it's written. Prioritize clarity and maintainability over cleverness.

## Database Schema & Relationships

### Entity Relationship Overview

The PKKI ITERA database is designed around intellectual property management with the following core entities:

```mermaid
erDiagram
    users ||--o{ submissions : "creates"
    users ||--o| user_detail : "has"
    users ||--o{ model_has_roles : "assigned"
    users ||--o{ tracking_histories : "processes"
    
    submissions ||--|| submission_types : "belongs_to"
    submissions ||--o| workflow_stages : "current_stage"
    submissions ||--o{ tracking_histories : "has"
    submissions ||--o| patent_details : "has"
    submissions ||--o| brand_details : "has"
    submissions ||--o| haki_details : "has"
    submissions ||--o| industrial_design_details : "has"
    submissions ||--o{ submission_documents : "has"
    
    submission_types ||--o{ workflow_stages : "defines"
    workflow_stages ||--o{ tracking_histories : "tracked_in"
    workflow_stages ||--o{ workflow_stage_requirements : "has"
    workflow_stages ||--o{ workflow_assignments : "assigned_to"
    
    documents ||--o{ submission_documents : "linked_via"
    documents ||--o{ tracking_histories : "referenced_in"
    
    roles ||--o{ model_has_roles : "assigned_via"
    roles ||--o{ role_has_permissions : "has"
    permissions ||--o{ role_has_permissions : "granted_via"
    permissions ||--o{ model_has_permissions : "directly_granted"
```

### Core Entities Description

#### 1. User Management System
```sql
-- Primary user entity with authentication
users (
    id: UUID PRIMARY KEY,
    fullname: VARCHAR(255) NOT NULL,
    email: VARCHAR(255) UNIQUE NOT NULL,
    email_verified_at: TIMESTAMP NULL,
    avatar: VARCHAR(255) NULL,
    password: VARCHAR(255) NULL,
    provider: VARCHAR(255) DEFAULT 'local', -- 'local', 'google', 'sso_itera'
    provider_id: VARCHAR(255) NULL,
    remember_token: VARCHAR(100) NULL,
    created_at: TIMESTAMP,
    updated_at: TIMESTAMP,
    deleted_at: TIMESTAMP NULL -- Soft deletes
)

-- Extended user information
user_detail (
    id: UUID PRIMARY KEY,
    user_id: UUID FOREIGN KEY REFERENCES users(id) CASCADE,
    alamat: VARCHAR(255) NULL,      -- Address
    phonenumber: VARCHAR(255) NULL, -- Phone number
    prodi: VARCHAR(255) NULL,       -- Study program
    jurusan: VARCHAR(255) NULL      -- Department
)
```

#### 2. Submission Management System
```sql
-- Main submission entity for IP applications
submissions (
    id: UUID PRIMARY KEY,
    submission_type_id: UUID FOREIGN KEY REFERENCES submission_types(id),
    current_stage_id: UUID FOREIGN KEY REFERENCES workflow_stages(id) NULL,
    title: VARCHAR(255) NOT NULL,
    status: ENUM('draft','submitted','in_review','revision_needed','approved','rejected','completed','cancelled') DEFAULT 'draft',
    certificate: VARCHAR(255) NULL,    -- Certificate file path when approved
    reviewer_notes: TEXT NULL,          -- Admin/reviewer comments
    user_id: UUID FOREIGN KEY REFERENCES users(id),
    created_at: TIMESTAMP,
    updated_at: TIMESTAMP,
    deleted_at: TIMESTAMP NULL
)

-- Types of intellectual property submissions
submission_types (
    id: UUID PRIMARY KEY,
    name: VARCHAR(255) NOT NULL,        -- e.g., "Patent", "Trademark", "Copyright"
    slug: VARCHAR(255) UNIQUE NOT NULL, -- e.g., "paten", "brand", "haki"
    description: TEXT NULL,
    created_at: TIMESTAMP,
    updated_at: TIMESTAMP,
    deleted_at: TIMESTAMP NULL
)
```

#### 3. Workflow Management System
```sql
-- Workflow stages for different submission types
workflow_stages (
    id: UUID PRIMARY KEY,
    submission_type_id: UUID FOREIGN KEY REFERENCES submission_types(id),
    code: VARCHAR(255) UNIQUE NOT NULL,     -- Stage identifier
    name: VARCHAR(255) NOT NULL,            -- Display name
    order: INT NOT NULL,                    -- Sequence order
    description: TEXT NULL,
    is_active: BOOLEAN DEFAULT TRUE,
    created_at: TIMESTAMP,
    updated_at: TIMESTAMP,
    deleted_at: TIMESTAMP NULL
)

-- Tracking history for submissions through workflow
tracking_histories (
    id: UUID PRIMARY KEY,
    submission_id: UUID FOREIGN KEY REFERENCES submissions(id),
    stage_id: UUID FOREIGN KEY REFERENCES workflow_stages(id),
    action: VARCHAR(255) DEFAULT 'state_change',
    metadata: JSON NULL,                     -- Additional data
    status: ENUM('started','in_progress','approved','rejected','revision_needed','objection','completed') DEFAULT 'started',
    comment: TEXT NULL,                      -- Stage-specific comments
    document_id: UUID FOREIGN KEY REFERENCES documents(id) NULL,
    processed_by: UUID FOREIGN KEY REFERENCES users(id) NULL, -- Who processed this stage
    previous_stage_id: UUID FOREIGN KEY REFERENCES workflow_stages(id) NULL,
    source_status: VARCHAR(255) NULL,
    target_status: VARCHAR(255) NULL,
    event_type: VARCHAR(255) DEFAULT 'state_change',
    resolved_at: TIMESTAMP NULL,
    event_timestamp: TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at: TIMESTAMP,
    updated_at: TIMESTAMP,
    deleted_at: TIMESTAMP NULL
)
```

#### 4. Intellectual Property Details

```sql
-- Patent-specific information
patent_details (
    id: UUID PRIMARY KEY,
    submission_id: UUID FOREIGN KEY REFERENCES submissions(id),
    application_type: VARCHAR(255) NOT NULL,
    patent_title: VARCHAR(255) NOT NULL,
    patent_description: TEXT NOT NULL,
    from_grant_research: BOOLEAN NOT NULL,   -- From research grant?
    self_funded: BOOLEAN NOT NULL,           -- Self-funded research?
    media_link: TEXT NULL,                   -- Media/demo links
    inventors_name: TEXT NULL,               -- List of inventors
    created_at: TIMESTAMP,
    updated_at: TIMESTAMP
)

-- Brand/Trademark-specific information
brand_details (
    id: UUID PRIMARY KEY,
    submission_id: UUID FOREIGN KEY REFERENCES submissions(id),
    application_type: VARCHAR(255) NOT NULL,
    application_date: DATE NOT NULL,
    application_origin: VARCHAR(255) NOT NULL,
    application_category: VARCHAR(255) NOT NULL,
    brand_name: VARCHAR(255) NOT NULL,
    brand_description: TEXT NOT NULL,
    inovators_name: VARCHAR(255) NOT NULL,   -- Innovator names
    brand_type: VARCHAR(255) NOT NULL,
    brand_label: VARCHAR(255) NOT NULL,
    brand_label_reference: VARCHAR(255) NULL,
    brand_label_description: TEXT NOT NULL,
    brand_color_elements: TEXT NULL,
    foreign_language_translation: TEXT NULL,
    disclaimer: TEXT NULL,
    priority_number: VARCHAR(255) NULL,
    nice_classes: VARCHAR(255) NOT NULL,     -- Nice Classification
    goods_services_search: TEXT NULL,
    created_at: TIMESTAMP,
    updated_at: TIMESTAMP
)

-- Copyright/HAKI-specific information
haki_details (
    id: UUID PRIMARY KEY,
    submission_id: UUID FOREIGN KEY REFERENCES submissions(id),
    -- Additional HAKI-specific fields would be defined here
    created_at: TIMESTAMP,
    updated_at: TIMESTAMP
)

-- Industrial Design-specific information
industrial_design_details (
    id: UUID PRIMARY KEY,
    submission_id: UUID FOREIGN KEY REFERENCES submissions(id),
    design_type: VARCHAR(255) NOT NULL,
    design_description: TEXT NOT NULL,
    novelty_statement: TEXT NOT NULL,
    designer_information: TEXT NOT NULL,
    locarno_class: VARCHAR(255) NULL,        -- Locarno Classification
    filing_date: DATE NULL,
    application_number: VARCHAR(255) NULL,
    registration_date: DATE NULL,
    registration_number: VARCHAR(255) NULL,
    expiration_date: DATE NULL,
    created_at: TIMESTAMP,
    updated_at: TIMESTAMP
)
```

#### 5. Document Management System
```sql
-- Document storage
documents (
    id: UUID PRIMARY KEY,
    uri: VARCHAR(255) NOT NULL,              -- File path/URL
    title: VARCHAR(255) NOT NULL,            -- Document title
    mimetype: VARCHAR(255) NULL,             -- File MIME type
    size: BIGINT NULL,                       -- File size in bytes
    created_at: TIMESTAMP,
    updated_at: TIMESTAMP,
    deleted_at: TIMESTAMP NULL
)

-- Link documents to submissions
submission_documents (
    id: UUID PRIMARY KEY,
    submission_id: UUID FOREIGN KEY REFERENCES submissions(id),
    document_id: UUID FOREIGN KEY REFERENCES documents(id),
    document_type: VARCHAR(255) NULL,        -- Type of document
    created_at: TIMESTAMP,
    updated_at: TIMESTAMP
)
```

#### 6. Permission & Role System (Spatie Permission)
```sql
-- Roles definition
roles (
    id: BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name: VARCHAR(255) NOT NULL,             -- 'super_admin', 'admin', 'civitas', 'non-civitas'
    guard_name: VARCHAR(255) NOT NULL,       -- Usually 'web'
    created_at: TIMESTAMP,
    updated_at: TIMESTAMP
)

-- Permissions definition
permissions (
    id: BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name: VARCHAR(255) NOT NULL,             -- 'view_submission', 'create_user', etc.
    guard_name: VARCHAR(255) NOT NULL,
    created_at: TIMESTAMP,
    updated_at: TIMESTAMP
)

-- User-Role assignments
model_has_roles (
    role_id: BIGINT UNSIGNED FOREIGN KEY REFERENCES roles(id),
    model_type: VARCHAR(255) NOT NULL,      -- Usually 'App\\Models\\User'
    model_id: UUID                           -- References users(id)
)

-- Role-Permission assignments
role_has_permissions (
    permission_id: BIGINT UNSIGNED FOREIGN KEY REFERENCES permissions(id),
    role_id: BIGINT UNSIGNED FOREIGN KEY REFERENCES roles(id)
)

-- Direct user permissions (optional)
model_has_permissions (
    permission_id: BIGINT UNSIGNED FOREIGN KEY REFERENCES permissions(id),
    model_type: VARCHAR(255) NOT NULL,
    model_id: UUID
)
```

### Key Relationships & Business Rules

#### Submission Workflow
1. **User** creates **Submission** of specific **SubmissionType**
2. **Submission** progresses through **WorkflowStages** based on type
3. Each stage transition creates **TrackingHistory** entry
4. **Documents** can be attached at various stages
5. **Administrators** process submissions through workflow stages

#### Role-Based Access Control
- **super_admin**: Full system access
- **admin**: Administrative functions
- **civitas**: ITERA community members (students, faculty, staff)
- **non-civitas**: External users

#### Data Integrity Rules
```php
// Always use UUID for primary keys
use Illuminate\Database\Eloquent\Concerns\HasUuids;

// Implement soft deletes where appropriate
use Illuminate\Database\Eloquent\SoftDeletes;

// Use proper foreign key constraints
Schema::table('submissions', function (Blueprint $table) {
    $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
    $table->foreignUuid('submission_type_id')->constrained();
    $table->foreignUuid('current_stage_id')->nullable()->constrained('workflow_stages');
});
```

### Database Indexing Strategy
```sql
-- Performance indexes for frequent queries
CREATE INDEX idx_submissions_status ON submissions(status);
CREATE INDEX idx_submissions_user_type ON submissions(user_id, submission_type_id);
CREATE INDEX idx_tracking_submission_stage ON tracking_histories(submission_id, stage_id);
CREATE INDEX idx_tracking_status_event ON tracking_histories(status, event_type);
CREATE INDEX idx_users_email_verified ON users(email, email_verified_at);
CREATE INDEX idx_workflow_stages_type_order ON workflow_stages(submission_type_id, order);
```
