<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_is_accessible(): void
    {
        $this->get('/login')->assertOk();
    }

    public function test_dashboard_redirects_guest_to_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_guest_landing_redirects_guest_to_login(): void
    {
        $this->get('/guest')->assertRedirect('/login');
    }

    public function test_authenticated_guest_can_access_guest_landing(): void
    {
        $user = User::factory()->create(['role' => 'guest']);

        $this->actingAs($user)->get('/guest')->assertOk();
    }

    public function test_admin_is_redirected_from_guest_landing_to_dashboard(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get('/guest')->assertRedirect('/dashboard');
    }

    public function test_guest_cannot_access_admin_pages(): void
    {
        $user = User::factory()->create(['role' => 'guest']);

        $this->actingAs($user)->get('/user')->assertForbidden();
    }

    public function test_admin_can_access_admin_pages(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get('/user')->assertOk();
    }
}
