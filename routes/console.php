<?php

use App\Mail\MailpitProbeMail;
use App\Models\MailCampaign;
use App\Services\Mail\StartMailCampaignDispatchService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;

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

Artisan::command('mail:dispatch-scheduled-campaigns', function (StartMailCampaignDispatchService $startMailCampaignDispatchService) {
    $campaigns = MailCampaign::query()
        ->where('status', 'scheduled')
        ->whereNotNull('scheduled_at')
        ->where('scheduled_at', '<=', now())
        ->orderBy('scheduled_at')
        ->get();

    foreach ($campaigns as $campaign) {
        $startMailCampaignDispatchService->start($campaign);
    }

    $this->info(sprintf('Đã mở dispatch cho %d campaign đến giờ gửi.', $campaigns->count()));
})->purpose('Mở dispatch cho các campaign đã đến giờ schedule');
