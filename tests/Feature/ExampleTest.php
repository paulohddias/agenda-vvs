<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_shows_the_booking_page_to_guests(): void
    {
        $this->get('/')->assertOk();
    }

    public function test_home_sends_admins_to_the_panel(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)->get('/')->assertRedirect(route('admin.dashboard'));
    }
}
