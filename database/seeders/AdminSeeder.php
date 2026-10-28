<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    /** создаёт учебный аккаунт администратора, если его ещё нет */
    public function run(): void
    {
        User::firstOrCreate(['login' => 'admin'], [
            'password' => 'admin123',
            'role_id' => Role::where('name', 'Администратор')->firstOrFail()->id,
        ]);
    }
}
