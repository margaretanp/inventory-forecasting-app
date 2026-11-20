<?php

namespace Database\Seeders;

use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class InitialSetupSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Akun default untuk login SweetBrew
        User::updateOrCreate(
            ['email' => 'admin@sweetbrew.id'],
            [
                'name' => 'Admin SweetBrew',
                'password' => Hash::make('password123'),
            ]
        );

        // 2. Data awal Master Supplier (tidak ada di CSV)
        $suppliers = [
            [
                'name' => 'Roastery Senja Nusantara',
                'whatsapp' => '+62 812-8840-1920',
                'address' => 'Jl. Soekarno Hatta No. 45, Malang',
                'notes' => 'Roasting setiap Senin & Kamis (H+2 kirim)',
            ],
            [
                'name' => 'CV Prima Dairy Coldchain',
                'whatsapp' => '+62 811-2091-4430',
                'address' => 'Jl. Raya Tlogomas No. 12, Malang',
                'notes' => 'Pengiriman chiller pagi 06.00 - 09.00 WIB',
            ],
            [
                'name' => 'Botanika Flavor & Pack Supply',
                'whatsapp' => '+62 813-7745-8821',
                'address' => 'Jl. Blimbing Indah No. 8, Malang',
                'notes' => 'Distributor resmi sirup, matcha & kemasan cup',
            ],
        ];

        foreach ($suppliers as $supplier) {
            Supplier::updateOrCreate(['name' => $supplier['name']], $supplier);
        }
    }
}
