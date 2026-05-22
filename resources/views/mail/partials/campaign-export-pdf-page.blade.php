<section @if (! empty($pageBreakBefore ?? false)) style="page-break-before: always;" @endif>
    <div style="font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:0.18em; color:#0284c7;">Rebate Mailer</div>
    <h1 style="margin:12px 0 0 0; font-size:28px; line-height:1.3; color:#0f172a;">
        {{ $subjectLine ?: 'Chưa có subject' }}
    </h1>

    <div style="margin-top:28px;">
        @include('mail.partials.campaign-recipient-content', ['bodyHtml' => $bodyHtml ?? null, 'preview' => $preview ?? null])
    </div>

    @include('mail.partials.campaign-recipient-signature', ['signature' => $signature ?? null])
</section>
