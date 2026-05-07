<?php

namespace App\Http\Controllers;

use App\Http\Requests\Templates\UpdateMailTemplateCanvasCompositionRequest;
use App\Models\MailTemplate;
use App\Services\Templates\UpdateMailTemplateCanvasCompositionService;
use Illuminate\Http\RedirectResponse;

class TemplateCanvasCompositionUpdateController extends Controller
{
    public function __construct(
        private readonly UpdateMailTemplateCanvasCompositionService $updateMailTemplateCanvasCompositionService,
    ) {
    }

    public function update(UpdateMailTemplateCanvasCompositionRequest $request, MailTemplate $mailTemplate): RedirectResponse
    {
        $this->updateMailTemplateCanvasCompositionService->update(
            $mailTemplate,
            $request->validated('partTypes'),
        );

        return redirect()
            ->route('templates.index')
            ->with('success', 'Đã lưu canvas composition.');
    }
}
