<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class OwnerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'name' => 'Oseahumen Agboifoh John',
            'email' => 'oseahumenagboifoh@gmail.com',
            'password' => Hash::make('Prnzdi@m0nd'),
            'email_verified_at' => now(),
        ]);
    }
}