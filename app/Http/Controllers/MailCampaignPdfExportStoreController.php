<?php

namespace App\Http\Controllers;

use App\Models\MailCampaign;
use App\Services\Mail\RequestMailCampaignPdfExportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

class MailCampaignPdfExportStoreController extends Controller
{
    public function __construct(
        private readonly RequestMailCampaignPdfExportService $requestMailCampaignPdfExportService,
    ) {
    }

    public function __invoke(MailCampaign $mailCampaign): RedirectResponse
    {
        try {
            $this->requestMailCampaignPdfExportService->request($mailCampaign, request()->user());
        } catch (ValidationException $exception) {
            return redirect()
                ->route('mail.index', ['campaign' => $mailCampaign->id])
                ->withErrors($exception->errors());
        }

        return redirect()
            ->route('mail.index', ['campaign' => $mailCampaign->id])
            ->with('success', 'Đã tạo yêu cầu export PDF mail đã gửi. Hệ thống sẽ xử lý trong hàng đợi.');
    }
}
