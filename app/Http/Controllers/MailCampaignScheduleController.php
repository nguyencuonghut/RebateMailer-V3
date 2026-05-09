<?php

namespace App\Http\Controllers;

use App\Http\Requests\Mail\ScheduleMailCampaignRequest;
use App\Models\MailCampaign;
use App\Services\Mail\ScheduleMailCampaignService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;

class MailCampaignScheduleController extends Controller
{
    public function __construct(
        private readonly ScheduleMailCampaignService $scheduleMailCampaignService,
    ) {
    }

    public function __invoke(ScheduleMailCampaignRequest $request, MailCampaign $mailCampaign): RedirectResponse
    {
        $scheduledAt = CarbonImmutable::parse((string) $request->string('scheduled_at'));
        $this->scheduleMailCampaignService->schedule($mailCampaign, $scheduledAt, $request->user());

        return redirect()->route('mail.index', ['campaign' => $mailCampaign->id]);
    }
}
