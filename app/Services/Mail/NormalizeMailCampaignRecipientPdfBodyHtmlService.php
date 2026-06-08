<?php

namespace App\Services\Mail;

class NormalizeMailCampaignRecipientPdfBodyHtmlService
{
    public function normalize(string $html): string
    {
        $normalized = $html;

        $replacements = [
            'font-size:20px;' => '__PDF_FONT_20__',
            'font-size:15px;' => '__PDF_FONT_15__',
            'font-size:14px;' => '__PDF_FONT_14__',
            'padding:12px 14px;' => 'padding:8px 10px;',
            'padding:28px 32px 36px 32px;' => 'padding:0;',
            'margin-top:28px;' => 'margin-top:18px;',
            'margin-top:32px;' => 'margin-top:20px;',
            'margin-top:24px;' => 'margin-top:16px;',
            'margin-bottom:16px;' => 'margin-bottom:12px;',
            'line-height:1.6;' => 'line-height:1.45;',
            'border-radius:20px;' => 'border-radius:14px;',
            'border-radius:16px;' => 'border-radius:12px;',
            '__PDF_FONT_20__' => 'font-size:17px;',
            '__PDF_FONT_15__' => 'font-size:14px;',
            '__PDF_FONT_14__' => 'font-size:12px;',
        ];

        foreach ($replacements as $search => $replace) {
            $normalized = str_replace($search, $replace, $normalized);
        }

        return $normalized;
    }
}
