<?php

namespace App\Http\Controllers;

use App\Models\MailCampaign;
use App\Services\Mail\SendSampleMailCampaignService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MailCampaignSampleSendController extends Controller
{
    public function __construct(
        private readonly SendSampleMailCampaignService $sendSampleMailCampaignService,
    ) {
    }

    public function __invoke(Request $request, MailCampaign $mailCampaign): JsonResponse
    {
        $validated = $request->validate([
            'target_email' => ['required', 'email', 'max:255'],
        ], [
            'target_email.required' => 'Vui lòng nhập địa chỉ email nhận.',
            'target_email.email' => 'Địa chỉ email không hợp lệ.',
        ]);

        $results = $this->sendSampleMailCampaignService->send($mailCampaign, $validated['target_email']);

        $sentCount = count(array_filter($results, static fn ($r): bool => $r['status'] === 'sent'));
        $total = count($results);

        return response()->json([
            'status' => 'ok',
            'message' => sprintf('Đã gửi %d/%d mail mẫu đến %s.', $sentCount, $total, $validated['target_email']),
            'results' => $results,
        ]);
    }
}
