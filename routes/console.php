<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use App\Mail\MailpitProbeMail;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('mailpit:probe {recipient?}', function (?string $recipient = null) {
    $recipient ??= config('mailpit.probe_recipient', env('MAILPIT_PROBE_RECIPIENT', config('mail.from.address')));

    Mail::to($recipient)->send(new MailpitProbeMail(
        appName: config('app.name'),
        sentAt: now()->toDateTimeString(),
    ));

    $this->info("Đã gửi email kiểm thử Mailpit tới: {$recipient}");
})->purpose('Gửi email kiểm thử vào Mailpit để xác minh luồng mail cục bộ');
