<?php

namespace App\Http\Controllers;

use App\Http\Requests\Mail\StoreMailCampaignRequest;
use App\Services\Mail\CreateMailCampaignService;
use Illuminate\Http\RedirectResponse;

class MailCampaignStoreController extends Controller
{
    public function __construct(
        private readonly CreateMailCampaignService $createMailCampaignService,
    ) {
    }

    public function store(StoreMailCampaignRequest $request): RedirectResponse
    {
        $campaign = $this->createMailCampaignService->create(
            $request->user(),
            $request->validated(),
        );

        return redirect()
            ->route('mail.index', ['campaign' => $campaign->id])
            ->with('success', 'Chiến dịch gửi mail đã được tạo.');
    }
}
