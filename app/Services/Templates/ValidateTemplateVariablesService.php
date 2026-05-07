<?php

namespace App\Services\Templates;

class ValidateTemplateVariablesService
{
    public function __construct(
        private readonly TemplateVariableCatalogService $templateVariableCatalogService,
    ) {
    }

    public function passes(string $value): bool
    {
        preg_match_all('/{{[^}]+}}/u', $value, $matches);

        $tokens = $matches[0] ?? [];

        if ($tokens === []) {
            return true;
        }

        $allowedTokens = $this->templateVariableCatalogService->allowedTokens();

        foreach ($tokens as $token) {
            if (! in_array($token, $allowedTokens, true)) {
                return false;
            }
        }

        return true;
    }

    public function message(): string
    {
        return 'Chỉ được dùng các biến đã được xác nhận trong contract template email.';
    }
}
