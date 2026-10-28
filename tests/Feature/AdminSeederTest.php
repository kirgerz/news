<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\AdminSeeder;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_creates_admin_who_can_login_and_open_admin_section(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::where('login', 'admin')->firstOrFail();

        $this->assertSame('Администратор', $admin->role->name);
        $this->assertTrue(Hash::check('admin123', $admin->password));
        $this->assertNotSame('admin123', $admin->password);
        $this->post('/login', ['login' => 'admin', 'password' => 'admin123'])->assertRedirect('/');
        $this->get('/admin')->assertOk();
    }

    public function test_repeated_seeding_does_not_duplicate_admin_or_reset_existing_password(): void
    {
        $otherUser = User::factory()->create();
        $this->seed(AdminSeeder::class);
        $admin = User::where('login', 'admin')->firstOrFail();
        $admin->password = 'changed123';
        $admin->save();

        $this->seed(AdminSeeder::class);
        $this->assertDatabaseCount('users', 2);
        $this->assertDatabaseHas('users', ['id' => $otherUser->id, 'login' => $otherUser->login]);
        $this->assertTrue(Hash::check('changed123', $admin->fresh()->password));
    }
}
