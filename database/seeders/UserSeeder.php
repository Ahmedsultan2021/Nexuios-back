<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::create([
            'name' =>"Hadia",
            'email' => "Hadia@icons.com",
            "role"=> "superAdmin",
            'password' => Hash::make("123456789"),
        ]);
    }
}
