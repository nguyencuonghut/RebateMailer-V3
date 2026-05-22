<div class="pdf-page">
    <table role="presentation" class="pdf-page-layout" cellpadding="0" cellspacing="0" border="0">
        <tr>
            <td class="pdf-page-content">
                {!! $page['bodyHtml'] !!}
            </td>
        </tr>
        <tr>
            <td class="pdf-page-signature-cell">
                @include('mail.partials.campaign-recipient-signature', ['signatureSnapshot' => $page['signatureSnapshot'] ?? null])
            </td>
        </tr>
    </table>
</div>
