<?php

namespace App\Http\Controllers;

use App\Http\Requests\Templates\UpdateMailTemplatePartRequest;
use App\Models\MailTemplate;
use App\Services\Templates\UpdateMailTemplatePartService;
use Illuminate\Http\RedirectResponse;

class TemplatePartUpdateController extends Controller
{
    public function __construct(
        private readonly UpdateMailTemplatePartService $updateMailTemplatePartService,
    ) {
    }

    public function update(UpdateMailTemplatePartRequest $request, MailTemplate $mailTemplate): RedirectResponse
    {
        $validated = $request->validated();

        $this->updateMailTemplatePartService->update(
            $mailTemplate,
            $request->user(),
            $validated['partType'],
            $validated,
        );

        return redirect()
            ->route('templates.index')
            ->with('success', 'Đã lưu phiên bản của phần template.');
    }
}
