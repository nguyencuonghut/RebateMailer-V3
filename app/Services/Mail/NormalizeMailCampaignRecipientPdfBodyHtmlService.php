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
            'padding:12px 14px;' => 'padding:5px 7px;',
            'padding:28px 32px 36px 32px;' => 'padding:0;',
            'margin-top:28px;' => 'margin-top:12px;',
            'margin-top:32px;' => 'margin-top:14px;',
            'margin-top:24px;' => 'margin-top:10px;',
            'margin-bottom:16px;' => 'margin-bottom:8px;',
            'line-height:1.6;' => 'line-height:1.3;',
            'border-radius:20px;' => 'border-radius:10px;',
            'border-radius:16px;' => 'border-radius:8px;',
            '__PDF_FONT_20__' => 'font-size:14px;',
            '__PDF_FONT_15__' => 'font-size:12px;',
            '__PDF_FONT_14__' => 'font-size:10px;',
        ];

        foreach ($replacements as $search => $replace) {
            $normalized = str_replace($search, $replace, $normalized);
        }

        return $normalized;
    }
}
