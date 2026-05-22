<?php

namespace App\Http\Controllers;

use App\Models\MailCampaign;
use App\Services\Mail\RequestMailCampaignPdfExportService;
use Illuminate\Http\RedirectResponse;
use RuntimeException;

class MailCampaignPdfExportStoreController extends Controller
{
    public function __construct(
        private readonly RequestMailCampaignPdfExportService $requestMailCampaignPdfExportService,
    ) {
    }

    public function __invoke(MailCampaign $mailCampaign): RedirectResponse
    {
        try {
            $this->requestMailCampaignPdfExportService->request(
                request()->user(),
                $mailCampaign,
            );

            return redirect()
                ->route('mail.index', ['campaign' => $mailCampaign->id])
                ->with('success', 'Đã ghi nhận yêu cầu export PDF cho chiến dịch.');
        } catch (RuntimeException $runtimeException) {
            return redirect()
                ->route('mail.index', ['campaign' => $mailCampaign->id])
                ->with('error', $runtimeException->getMessage());
        }
    }
}
