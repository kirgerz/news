<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_needs_only_login_and_matching_passwords(): void
    {
        $this->post('/register', [
            'login' => 'student',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ])->assertRedirect('/');

        $user = User::where('login', 'student')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertTrue(Hash::check('secret123', $user->password));
        $this->assertNotSame('secret123', $user->password);
        $this->assertNull($user->email);
        $this->assertSame('Читатель', $user->role->name);
        $this->get('/admin')->assertForbidden();
    }

    public function test_later_registration_gets_reader_role_even_if_admin_role_is_submitted(): void
    {
        $admin = User::factory()->admin()->create();

        $this->post('/register', [
            'login' => 'next_student',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'role_id' => $admin->role_id,
        ])->assertRedirect('/');

        $user = User::where('login', 'next_student')->firstOrFail();
        $this->assertSame('Читатель', $user->role->name);
        $this->get('/admin')->assertForbidden();
    }

    public function test_registration_does_not_grant_admin_when_only_journalists_exist(): void
    {
        User::factory()->journalist()->create();

        $this->post('/register', [
            'login' => 'first_student',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ])->assertRedirect('/');

        $this->assertSame('Читатель', User::where('login', 'first_student')->firstOrFail()->role->name);
        $this->get('/admin')->assertForbidden();
    }

    public function test_duplicate_logins_are_rejected(): void
    {
        User::factory()->create(['login' => 'student']);

        $this->post('/register', [
            'login' => 'student',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ])->assertSessionHasErrors('login');

        $this->assertDatabaseCount('users', 1);
        $this->assertGuest();
    }

    public function test_registration_rejects_password_mismatch(): void
    {
        $this->post('/register', [
            'login' => 'student',
            'password' => 'secret123',
            'password_confirmation' => 'different',
        ])->assertSessionHasErrors('password');

        $this->assertDatabaseCount('users', 0);
    }

    public function test_user_can_login_with_login_and_password(): void
    {
        $user = User::factory()->create(['login' => 'student']);

        $this->post('/login', ['login' => 'student', 'password' => 'password'])->assertRedirect('/');

        $this->assertAuthenticatedAs($user);
    }

    public function test_wrong_password_does_not_log_user_in(): void
    {
        User::factory()->create(['login' => 'student']);

        $this->post('/login', ['login' => 'student', 'password' => 'wrong'])->assertSessionHasErrors('login');

        $this->assertGuest();
    }

    public function test_user_can_logout(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post('/logout')->assertRedirect('/');
        $this->assertGuest();
    }
}
