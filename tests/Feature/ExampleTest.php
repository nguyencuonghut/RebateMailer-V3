<?php

namespace Tests\Feature;

use App\Mail\MailpitProbeMail;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_redirects_guests_to_login(): void
    {
        $response = $this->get('/');

        $response->assertRedirect(route('login', absolute: false));
    }

    public function test_the_dashboard_shell_renders_through_inertia_for_authenticated_users(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);

        $user = User::query()->where('email', 'user@rebatemailer.test')->firstOrFail();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
            );
    }

    public function test_the_mailpit_probe_command_sends_a_test_email(): void
    {
        Mail::fake();

        Artisan::call('mailpit:probe', [
            'recipient' => 'dev@example.test',
        ]);

        Mail::assertSent(MailpitProbeMail::class, function (MailpitProbeMail $mail) {
            return $mail->hasTo('dev@example.test');
        });
    }
}
