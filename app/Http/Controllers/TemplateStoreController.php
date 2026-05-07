<?php

namespace App\Http\Controllers;

use App\Http\Requests\Templates\StoreMailTemplateRequest;
use App\Services\Templates\CreateMailTemplateService;
use Illuminate\Http\RedirectResponse;

class TemplateStoreController extends Controller
{
    public function __construct(
        private readonly CreateMailTemplateService $createMailTemplateService,
    ) {
    }

    public function store(StoreMailTemplateRequest $request): RedirectResponse
    {
        $this->createMailTemplateService->create(
            $request->user(),
            $request->validated(),
        );

        return redirect()
            ->route('templates.index')
            ->with('success', 'Template email đã được tạo.');
    }
}
