<!DOCTYPE html>
<html lang="vi">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>{{ $subjectLine ?: 'PDF email chiến dịch' }}</title>
    </head>
    <body style="margin:0; padding:0; background:#ffffff; font-family: DejaVu Sans, Arial, Helvetica, sans-serif; color:#0f172a; line-height:1.6;">
        <div style="padding:24px 24px 32px 24px;">
            @include('mail.partials.campaign-recipient-content', ['bodyHtml' => $bodyHtml, 'preview' => $preview ?? null])

            @include('mail.partials.campaign-recipient-signature', ['signature' => $signature])
        </div>
    </body>
</html>
