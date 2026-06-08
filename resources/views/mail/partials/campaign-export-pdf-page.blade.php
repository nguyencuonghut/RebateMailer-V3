<section @if (! empty($pageBreakBefore ?? false)) style="page-break-before: always;" @endif>
    @include('mail.partials.campaign-recipient-content', ['bodyHtml' => $bodyHtml ?? null, 'preview' => $preview ?? null])

    @include('mail.partials.campaign-recipient-signature', ['signature' => $signature ?? null])
</section>
