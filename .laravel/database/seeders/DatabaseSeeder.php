<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use UniGaleModules\AccountsManager\Models\Account;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        Account::query()->create([
            'name'     => 'Test User',
            'email'    => 'test@example.com',
            'password' => 'password',
        ]);
    }
}
