<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();

        // Root User
        $root = User::firstOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name' => 'Super Admin',
                'phone' => '01711111111',
                'password' => Hash::make('password'),
                'role' => 'super_admin',
            ]
        );

        // Admin under root (LEFT)
        $admin = User::firstOrCreate(
            ['email' => 'adminuser@gmail.com'],
            [
                'name' => 'Admin User',
                'user_id' => 'DBMBL001',
                'phone' => '01711111112',
                'password' => Hash::make('password'),
                'role' => 'admin',

                // FIX: dynamic refer_id
                'refer_id' => $root->id,

                // parent assign
                'parent_id' => $root->id,
            ]
        );
        if (!$root->left_child_id) {
            $root->left_child_id = $admin->id;
            $root->save();
        }

        if ($admin->parent_id !== $root->id) {
            $admin->parent_id = $root->id;
            $admin->save();
        }

    }
}
