<?php

namespace Tests\Feature;

use App\Mail\MailpitProbeMail;
use Inertia\Testing\AssertableInertia as Assert;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_dashboard_shell_renders_through_inertia(): void
    {
        $response = $this->get('/');

        $response
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('appName', 'RebateMailerV3')
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
