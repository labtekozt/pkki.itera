<?php

namespace Tests\Feature;

use App\Models\User;
use App\Policies\SubmissionResourcePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SubmissionNavigationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that civitas users can see submission navigation.
     */
    public function test_civitas_users_can_see_submission_navigation(): void
    {
        // Create civitas role and user
        $civitasRole = Role::create(['name' => 'civitas', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole($civitasRole);
        
        // Give basic submission permissions
        $user->givePermissionTo([
            'view_submission',
            'create_submission'
        ]);

        $policy = new SubmissionResourcePolicy();
        
        $this->assertTrue($policy->viewAny($user), 'Civitas user should be able to view submissions navigation');
        $this->assertTrue($policy->create($user), 'Civitas user should be able to create submissions');
    }

    /**
     * Test that non-civitas users can see submission navigation.
     */
    public function test_non_civitas_users_can_see_submission_navigation(): void
    {
        // Create non-civitas role and user
        $nonCivitasRole = Role::create(['name' => 'non-civitas', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole($nonCivitasRole);
        
        // Give basic submission permissions
        $user->givePermissionTo([
            'view_submission',
            'create_submission'
        ]);

        $policy = new SubmissionResourcePolicy();
        
        $this->assertTrue($policy->viewAny($user), 'Non-civitas user should be able to view submissions navigation');
        $this->assertTrue($policy->create($user), 'Non-civitas user should be able to create submissions');
    }

    /**
     * Test that users with only partial permissions can still see navigation.
     */
    public function test_users_with_partial_permissions_can_see_navigation(): void
    {
        $civitasRole = Role::create(['name' => 'civitas', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole($civitasRole);
        
        // Give only view permission (not view_any)
        $user->givePermissionTo('view_submission');

        $policy = new SubmissionResourcePolicy();
        
        $this->assertTrue($policy->viewAny($user), 'User with partial permissions should still see navigation');
    }

    /**
     * Test that the role-based fallback works correctly.
     */
    public function test_role_based_fallback_allows_access(): void
    {
        $civitasRole = Role::create(['name' => 'civitas', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole($civitasRole);
        
        // No specific permissions, but should pass due to role
        $policy = new SubmissionResourcePolicy();
        
        $this->assertTrue($policy->viewAny($user), 'Civitas role should allow access even without specific permissions');
    }

    /**
     * Test that admin users always have access.
     */
    public function test_admin_users_always_have_access(): void
    {
        $adminRole = Role::create(['name' => 'admin', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole($adminRole);

        $policy = new SubmissionResourcePolicy();
        
        $this->assertTrue($policy->viewAny($user), 'Admin users should always have access');
        $this->assertTrue($policy->create($user), 'Admin users should always be able to create');
    }
}
