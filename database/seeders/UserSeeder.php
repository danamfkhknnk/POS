<?php

namespace Database\Seeders;

use App\Models\Outlet;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::firstOrCreate(['name' => 'admin'], ['description' => 'Admin Pusat']);
        $staffRole = Role::firstOrCreate(['name' => 'staff'], ['description' => 'Staff Outlet']);

        $jakarta = Outlet::where('code', 'JK01')->first();
        $bandung = Outlet::where('code', 'BDG01')->first();

        $admin = User::create([
            'name' => 'Admin Pusat',
            'email' => 'admin@demo.com',
            'password' => Hash::make('password'),
            'outlet_id' => null,
        ]);
        $admin->roles()->attach($adminRole);

        $staffJakarta = User::create([
            'name' => 'Staff Jakarta',
            'email' => 'staff.jk@demo.com',
            'password' => Hash::make('password'),
            'outlet_id' => $jakarta->id,
        ]);
        $staffJakarta->roles()->attach($staffRole);

        $staffBandung = User::create([
            'name' => 'Staff Bandung',
            'email' => 'staff.bdg@demo.com',
            'password' => Hash::make('password'),
            'outlet_id' => $bandung->id,
        ]);
        $staffBandung->roles()->attach($staffRole);
    }
}
