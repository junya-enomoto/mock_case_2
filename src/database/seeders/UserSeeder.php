<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'name' => '西怜奈',
            'email' => 'reina.n@coatctech.com',
            'password' => Hash::make('password'),
        ]);
        User::create([
            'name' => '山田太郎',
            'email' => 'taro.y@coatctech.com',
            'password' => Hash::make('password'),
        ]);
        User::create([
            'name' => '増田一世',
            'email' => 'issei.m@coatctech.com',
            'password' => Hash::make('password'),
        ]);
        User::create([
            'name' => '山本敬吉',
            'email' => 'keikichi.y@coatctech.com',
            'password' => Hash::make('password'),
        ]);
        User::create([
            'name' => '秋田朋美',
            'email' => 'tomomi.a@coatctech.com',
            'password' => Hash::make('password'),
        ]);
        User::create([
            'name' => '中西教夫',
            'email' => 'norio.n@coatctech.com',
            'password' => Hash::make('password'),
        ]);
    }
}
