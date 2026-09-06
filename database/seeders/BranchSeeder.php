<?php

namespace Database\Seeders;

use App\Models\Outlet;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    public function run(): void
    {
        Outlet::create([
            'name' => 'Outlet Jakarta Pusat',
            'code' => 'JK01',
            'address' => 'Jl. Sudirman No. 1, Jakarta Selatan',
        ]);
        Outlet::create([
            'name' => 'Outlet Bandung',
            'code' => 'BDG01',
            'address' => 'Jl. Asia Afrika No. 1, Bandung',
        ]);
        Outlet::create([
            'name' => 'Outlet Surabaya',
            'code' => 'SBY01',
            'address' => 'Jl. Rungkut Asri No. 1, Surabaya',
        ]);
    }
}
