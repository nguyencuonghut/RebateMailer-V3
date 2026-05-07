<?php

namespace App\Http\Controllers;

use App\Http\Requests\Templates\StoreMailTemplateSectionRequest;
use App\Models\MailTemplate;
use App\Services\Templates\AppendMailTemplateSectionService;
use Illuminate\Http\RedirectResponse;

class TemplateSectionStoreController extends Controller
{
    public function __construct(
        private readonly AppendMailTemplateSectionService $appendMailTemplateSectionService,
    ) {
    }

    public function store(StoreMailTemplateSectionRequest $request, MailTemplate $mailTemplate): RedirectResponse
    {
        $this->appendMailTemplateSectionService->append(
            $mailTemplate,
            $request->user(),
            $request->validated('type'),
        );

        return redirect()
            ->route('templates.index')
            ->with('success', 'Đã thêm section vào template email.');
    }
}
