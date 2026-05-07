<?php

namespace App\Services\Templates;

class RenderTemplateTextPreviewService
{
    public function __construct(
        private readonly TemplateVariableCatalogService $templateVariableCatalogService,
    ) {
    }

    /**
     * @param  array<string, string>  $variables
     * @return array{renderedText: string, errors: array<int, string>}
     */
    public function render(string $templateText, array $variables): array
    {
        preg_match_all('/{{[^}]+}}/u', $templateText, $matches);

        $tokens = array_values(array_unique($matches[0] ?? []));
        $allowedTokens = $this->templateVariableCatalogService->allowedTokens();
        $errors = [];
        $renderedText = $templateText;

        foreach ($tokens as $token) {
            if (! in_array($token, $allowedTokens, true)) {
                $errors[] = sprintf('Biến %s không nằm trong contract template email.', $token);
                continue;
            }

            $value = $variables[$token] ?? '';

            if ($value === '') {
                $errors[] = sprintf('Biến %s chưa có dữ liệu thật để preview.', $token);
                continue;
            }

            $renderedText = str_replace($token, $value, $renderedText);
        }

        return [
            'renderedText' => $renderedText,
            'errors' => $errors,
        ];
    }
}
