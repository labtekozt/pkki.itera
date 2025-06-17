<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Role;

class UsersTableSeeder extends Seeder
{
    public function run()
    {
        // Check if superadmin user already exists
        $existingUser = User::where('email', 'superadmin@hki.itera.ac.id')->first();
        
        if (!$existingUser) {
            // Create superadmin user using User model
            $user = User::create([
                'fullname' => 'Super Admin PKKI',
                'email' => 'superadmin@hki.itera.ac.id',
                'email_verified_at' => now(),
                'password' => Hash::make('superadmin'),
            ]);

            // Assign super_admin role if it exists
            $superAdminRole = Role::where('name', 'super_admin')->first();
            if ($superAdminRole) {
                $user->assignRole($superAdminRole);
                echo "✅ Superadmin user created and assigned super_admin role.\n";
            } else {
                echo "⚠️ Superadmin user created but super_admin role not found.\n";
            }

            // Try to bind user to FilamentShield
            try {
                Artisan::call('shield:super-admin', ['--user' => $user->id]);
                echo "✅ FilamentShield super-admin setup completed.\n";
            } catch (\Exception $e) {
                echo "⚠️ Shield super-admin command failed (user might already be super admin): " . $e->getMessage() . "\n";
            }
        } else {
            echo "ℹ️ Superadmin user already exists, skipping creation.\n";
            
            // Ensure existing user has super_admin role
            $superAdminRole = Role::where('name', 'super_admin')->first();
            if ($superAdminRole && !$existingUser->hasRole('super_admin')) {
                $existingUser->assignRole($superAdminRole);
                echo "✅ Assigned super_admin role to existing user.\n";
            }
        }
    }
}
