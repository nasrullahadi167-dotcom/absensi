<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('ORGA-ATTEND');
        $response->assertSee('Username');
    }

    public function test_admin_user_can_authenticate_and_redirects_to_admin_dashboard(): void
    {
        $admin = User::create([
            'username' => 'admin_test',
            'name' => 'Admin Test',
            'email' => 'admin@test.com',
            'password' => Hash::make('password123'),
            'role' => 'ADMIN',
            'is_active' => true,
        ]);

        $response = $this->post('/login', [
            'username' => 'admin_test',
            'password' => 'password123',
        ]);

        $this->assertAuthenticatedAs($admin);
        $response->assertRedirect(route('admin.dashboard'));
    }

    public function test_member_user_can_authenticate_and_redirects_to_member_dashboard(): void
    {
        $member = User::create([
            'username' => 'member_test',
            'name' => 'Member Test',
            'email' => 'member@test.com',
            'password' => Hash::make('password123'),
            'role' => 'MEMBER',
            'is_active' => true,
        ]);

        $response = $this->post('/login', [
            'username' => 'member_test',
            'password' => 'password123',
        ]);

        $this->assertAuthenticatedAs($member);
        $response->assertRedirect(route('member.dashboard'));
    }

    public function test_users_cannot_authenticate_with_invalid_password(): void
    {
        User::create([
            'username' => 'member_test',
            'name' => 'Member Test',
            'password' => Hash::make('password123'),
            'role' => 'MEMBER',
            'is_active' => true,
        ]);

        $response = $this->post('/login', [
            'username' => 'member_test',
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('username');
    }

    public function test_inactive_users_cannot_authenticate(): void
    {
        User::create([
            'username' => 'inactive_user',
            'name' => 'Inactive User',
            'password' => Hash::make('password123'),
            'role' => 'MEMBER',
            'is_active' => false,
        ]);

        $response = $this->post('/login', [
            'username' => 'inactive_user',
            'password' => 'password123',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('username');
    }

    public function test_authenticated_user_can_logout(): void
    {
        $user = User::create([
            'username' => 'active_user',
            'name' => 'Active User',
            'password' => Hash::make('password123'),
            'role' => 'MEMBER',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/login');
    }

    public function test_user_can_change_password(): void
    {
        $user = User::create([
            'username' => 'active_user',
            'name' => 'Active User',
            'password' => Hash::make('old_password123'),
            'role' => 'MEMBER',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->put('/change-password', [
            'current_password' => 'old_password123',
            'password' => 'new_password123',
            'password_confirmation' => 'new_password123',
        ]);

        $response->assertSessionHasNoErrors();
        $user->refresh();
        $this->assertTrue(Hash::check('new_password123', $user->password));
    }
}
