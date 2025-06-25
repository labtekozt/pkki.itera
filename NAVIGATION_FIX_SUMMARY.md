# Fix for Submission Navigation Visibility Issue

## Problem Description
Civitas users with partial submission permissions were unable to see the submission navigation icon in the Filament admin dashboard. The original `SubmissionPolicy` required users to have review permissions (`submission::review` suffix) which some civitas users didn't possess.

## Root Cause
The `SubmissionPolicy.php` was checking for permissions with the `::review` suffix:
```php
return $user->can('view_any_submission::review');
```

However, some civitas users only had regular submission permissions without the review suffix:
- `view_submission` (instead of `view_submission::review`)
- `create_submission` (instead of `create_submission::review`)

## Solution Implementation

### 1. Created New SubmissionResourcePolicy
Created `/app/Policies/SubmissionResourcePolicy.php` with OR logic that checks for:
- Regular permissions (`view_any_submission`, `create_submission`, etc.)
- Review permissions (`view_any_submission::review`, `create_submission::review`, etc.)
- Role-based fallbacks (`civitas`, `non-civitas`, `admin`, `super_admin`)

### 2. Updated SubmissionResource
Modified `/app/Filament/Resources/SubmissionResource.php` to:
- Use the new `SubmissionResourcePolicy` instead of default `SubmissionPolicy`
- Added query scoping to ensure users only see their own submissions

### 3. Maintained SubmissionReviewResource
Updated `/app/Filament/Resources/SubmissionReviewResource.php` to:
- Explicitly use the original `SubmissionPolicy` for review functionality
- Preserves admin review workflow permissions

### 4. Updated AuthServiceProvider
Added documentation comments in `/app/Providers/AuthServiceProvider.php` explaining the dual policy approach.

## Key Changes

### SubmissionResourcePolicy.php (New File)
```php
public function viewAny(User $user): bool
{
    // Check regular permissions OR review permissions OR role-based access
    return $user->can('view_any_submission') || 
           $user->can('view_any_submission::review') ||
           $user->can('view_submission') || 
           $user->can('view_submission::review') ||
           $user->hasRole(['civitas', 'non-civitas', 'admin', 'super_admin']);
}
```

### SubmissionResource.php
```php
protected static ?string $modelPolicy = SubmissionResourcePolicy::class;

public static function getEloquentQuery(): Builder
{
    $query = parent::getEloquentQuery();
    
    // Non-admin users only see their own submissions
    if (!auth()->user()->hasRole(['admin', 'super_admin'])) {
        $query->where('user_id', auth()->id());
    }
    
    return $query;
}
```

## Permission Structure

### Civitas Role Permissions (30 permissions)
- Regular submission permissions: `view_submission`, `create_submission`, `update_submission`, etc.
- Review submission permissions: `view_submission::review`, `create_submission::review`, etc.
- User permissions: `view_user`, `update_user`
- Page permissions: `page_MyProfilePage`
- Widget permissions: `widget_ApplicationInfo`

### Non-Civitas Role Permissions (14 permissions)
- Basic submission permissions: `view_submission`, `create_submission`, `update_submission`, etc.
- Limited review permissions: `view_submission::review`, `create_submission::review`, etc.
- User permissions: `view_user`, `update_user`

## Testing the Fix

### Manual Testing
1. Clear Laravel caches:
```bash
php artisan cache:clear && php artisan config:clear && php artisan route:clear && php artisan view:clear
```

2. Test with civitas user:
```bash
php artisan tinker --execute="
\$user = App\Models\User::whereHas('roles', function(\$q) { 
    \$q->where('name', 'civitas'); 
})->first();
\$policy = new App\Policies\SubmissionResourcePolicy();
echo 'Navigation visible: ' . (\$policy->viewAny(\$user) ? 'YES' : 'NO');
"
```

### Automated Testing
Run the test suite:
```bash
php artisan test --filter=SubmissionNavigationTest
```

## Expected Results

### Before Fix
- Some civitas users couldn't see submission navigation despite having submission permissions
- Navigation required ALL permissions instead of ANY permission

### After Fix
- All civitas and non-civitas users can see submission navigation if they have ANY submission-related permission
- Role-based fallback ensures users with appropriate roles always have access
- Admin functionality preserved through explicit policy assignment

## Verification Checklist

- [x] Civitas users can see submission navigation
- [x] Non-civitas users can see submission navigation  
- [x] Users with partial permissions can access navigation
- [x] Admin users retain full access
- [x] Query scoping ensures data security
- [x] Review functionality preserved
- [x] No breaking changes to existing functionality

## Files Modified

1. **New**: `/app/Policies/SubmissionResourcePolicy.php`
2. **Modified**: `/app/Filament/Resources/SubmissionResource.php`
3. **Modified**: `/app/Filament/Resources/SubmissionReviewResource.php`
4. **Modified**: `/app/Providers/AuthServiceProvider.php`
5. **New**: `/tests/Feature/SubmissionNavigationTest.php`

The fix ensures that navigation visibility is based on ANY relevant permission rather than requiring ALL permissions, while maintaining security through proper query scoping and preserving administrative review functionality.
