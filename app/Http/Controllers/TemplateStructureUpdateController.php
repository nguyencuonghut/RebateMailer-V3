<?php

namespace App\Http\Controllers;

use App\Http\Requests\Templates\UpdateMailTemplateStructureRequest;
use App\Models\MailTemplate;
use App\Services\Templates\UpdateMailTemplateStructureService;
use Illuminate\Http\RedirectResponse;

class TemplateStructureUpdateController extends Controller
{
    public function __construct(
        private readonly UpdateMailTemplateStructureService $updateMailTemplateStructureService,
    ) {
    }

    public function update(UpdateMailTemplateStructureRequest $request, MailTemplate $mailTemplate): RedirectResponse
    {
        $this->updateMailTemplateStructureService->update(
            $mailTemplate,
            $request->user(),
            $request->validated(),
        );

        return redirect()
            ->route('templates.index')
            ->with('success', 'Đã lưu cấu trúc template email.');
    }
}
