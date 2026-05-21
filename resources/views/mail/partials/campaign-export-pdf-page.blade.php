<div class="pdf-page">
    <div>
        {!! $page['bodyHtml'] !!}
    </div>

    @include('mail.partials.campaign-recipient-signature', ['signatureSnapshot' => $page['signatureSnapshot'] ?? null])
</div>
