<?php

namespace App\Http\Controllers;

use App\Http\Requests\Templates\UpdateMailTemplateCanvasPartBindingRequest;
use App\Models\MailTemplate;
use App\Services\Templates\UpdateMailTemplateCanvasPartBindingService;
use Illuminate\Http\RedirectResponse;

class TemplateCanvasPartBindingUpdateController extends Controller
{
    public function __construct(
        private readonly UpdateMailTemplateCanvasPartBindingService $updateMailTemplateCanvasPartBindingService,
    ) {
    }

    public function update(UpdateMailTemplateCanvasPartBindingRequest $request, MailTemplate $mailTemplate): RedirectResponse
    {
        $validated = $request->validated();

        $this->updateMailTemplateCanvasPartBindingService->update(
            $mailTemplate,
            $validated['partType'],
            (int) $validated['templatePartVersionId'],
        );

        return redirect()
            ->route('templates.index')
            ->with('success', 'Đã ghép version vào canvas hiện tại.');
    }
}
