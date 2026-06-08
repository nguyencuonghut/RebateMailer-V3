<!DOCTYPE html>
<html lang="vi">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>{{ $subjectLine ?: 'PDF email chiến dịch' }}</title>
    </head>
    <body style="margin:0; padding:0; background:#ffffff; font-family: DejaVu Sans, Arial, Helvetica, sans-serif; font-size:11px; color:#0f172a; line-height:1.3;">
        <div style="padding:8px 10px 10px 10px;">
            @include('mail.partials.campaign-recipient-content', ['bodyHtml' => $bodyHtml, 'preview' => $preview ?? null, 'forPdf' => true])

            @include('mail.partials.campaign-recipient-signature', ['signature' => $signature, 'forPdf' => true])
        </div>
    </body>
</html>
