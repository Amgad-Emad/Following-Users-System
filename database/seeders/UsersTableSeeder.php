<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User; // Make sure to import the User model

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = factory(User::class, 10)->make()->toArray();

        foreach ($users as $user) {
            User::create($user);
        }
    }
}