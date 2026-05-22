<?php

namespace App\Services\Mail;

use DOMDocument;
use DOMElement;
use DOMXPath;

class ExtractMailCampaignRecipientBodyHtmlService
{
    public function extract(string $fullHtml): string
    {
        $html = trim($fullHtml);

        if ($html === '') {
            return '';
        }

        $document = new DOMDocument();

        libxml_use_internal_errors(true);
        $document->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $xpath = new DOMXPath($document);
        $nodes = $xpath->query('//td[contains(@style, "padding:28px 32px 36px 32px")]');

        if ($nodes !== false && $nodes->length > 0 && $nodes->item(0) instanceof DOMElement) {
            return $this->innerHtml($nodes->item(0));
        }

        $bodyNodes = $xpath->query('//body');

        if ($bodyNodes !== false && $bodyNodes->length > 0 && $bodyNodes->item(0) instanceof DOMElement) {
            return $this->innerHtml($bodyNodes->item(0));
        }

        return $html;
    }

    private function innerHtml(DOMElement $element): string
    {
        $html = '';

        foreach ($element->childNodes as $childNode) {
            $html .= $element->ownerDocument?->saveHTML($childNode) ?? '';
        }

        return $html;
    }
}
