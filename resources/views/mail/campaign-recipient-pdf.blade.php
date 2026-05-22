<!DOCTYPE html>
<html lang="vi">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>{{ $subjectLine ?: 'PDF email chiến dịch' }}</title>
    </head>
    <body style="margin:0; padding:0; background:#ffffff; font-family: Arial, Helvetica, sans-serif; color:#0f172a; line-height:1.6;">
        <div style="padding:24px 24px 32px 24px;">
            <div style="font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:0.18em; color:#0284c7;">Rebate Mailer</div>
            <h1 style="margin:12px 0 0 0; font-size:28px; line-height:1.3; color:#0f172a;">
                {{ $subjectLine ?: 'Chưa có subject' }}
            </h1>

            <div style="margin-top:28px;">
                @include('mail.partials.campaign-recipient-content', ['bodyHtml' => $bodyHtml, 'preview' => $preview ?? null])
            </div>

            @include('mail.partials.campaign-recipient-signature', ['signature' => $signature])
        </div>
    </body>
</html>
