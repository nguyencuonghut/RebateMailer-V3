<?php

namespace App\Http\Controllers;

use App\Models\MailTemplate;
use App\Services\Templates\ActivateMailTemplateCanvasService;
use Illuminate\Http\RedirectResponse;

class TemplateActivateController extends Controller
{
    public function __construct(
        private readonly ActivateMailTemplateCanvasService $activateMailTemplateCanvasService,
    ) {
    }

    public function update(MailTemplate $mailTemplate): RedirectResponse
    {
        $user = request()->user();

        abort_unless($user !== null, 403);

        $this->activateMailTemplateCanvasService->activate($mailTemplate, $user);

        return redirect()
            ->route('templates.index')
            ->with('success', 'Đã đặt template làm mẫu hoạt động.');
    }
}
