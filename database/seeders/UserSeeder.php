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
                'phone' => '01711111112',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'parent_id' => $root->id,
            ]
        );
        $root->left_child_id = $admin->id;
        $root->save();

        // Vendor Owner 1 (RIGHT)
        $owner1 = User::firstOrCreate(
            ['email' => 'owner1@gmail.com'],
            [
                'name' => 'Vendor Owner 1',
                'phone' => '01711111113',
                'password' => Hash::make('password'),
                'role' => 'customer',
                'parent_id' => $root->id,
            ]
        );
        $root->right_child_id = $owner1->id;
        $root->save();

        // Vendor Owner 2 under Admin (LEFT child)
        $owner2 = User::firstOrCreate(
            ['email' => 'owner2@gmail.com'],
            [
                'name' => 'Vendor Owner 2',
                'phone' => '01711111114',
                'password' => Hash::make('password'),
                'role' => 'customer',
                'parent_id' => $admin->id,
            ]
        );
        $admin->left_child_id = $owner2->id;
        $admin->save();

        // Staff under Owner1 (LEFT child)
        $staff1 = User::firstOrCreate(
            ['email' => 'staff1@gmail.com'],
            [
                'name' => 'Vendor Staff 1',
                'phone' => '01711111115',
                'password' => Hash::make('password'),
                'role' => 'customer',
                'parent_id' => $owner1->id,
            ]
        );
        $owner1->left_child_id = $staff1->id;
        $owner1->save();

        // Customers under Owner2 (LEFT & RIGHT child)
        $parent = $owner2;

        for ($i = 1; $i <= 4; $i++) {
            $customerEmail = "customer$i@gmail.com";

            $customer = User::firstOrCreate(
                ['email' => $customerEmail],
                [
                    'name' => "Customer $i",
                    'phone' => "0171111112$i",
                    'password' => Hash::make('password'),
                    'role' => 'customer',
                    'parent_id' => $parent->id,
                ]
            );

            // auto left/right assign
            if (!$parent->left_child_id) {
                $parent->left_child_id = $customer->id;
            } elseif (!$parent->right_child_id) {
                $parent->right_child_id = $customer->id;
            }
            $parent->save();
        }
    }
}
