<!DOCTYPE html>
<html lang="vi">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>{{ data_get($preview, 'subject.renderedText', 'Export PDF mail') }}</title>
    </head>
    <body style="margin:0; padding:0; background:#ffffff; font-family:'DejaVu Sans', sans-serif; color:#0f172a; line-height:1.6;">
        <div style="padding:24px;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                <tr>
                    <td align="center">
                        <table role="presentation" width="860" cellpadding="0" cellspacing="0" border="0" style="width:100%; max-width:860px;">
                            <tr>
                                <td style="padding:0;">
                                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#ffffff; border:1px solid #dbe4ef; border-radius:20px;">
                                        <tr>
                                            <td style="padding:32px 32px 16px 32px; background:linear-gradient(135deg, #f8fafc 0%, #eef6ff 100%);">
                                                <div style="font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:0.18em; color:#0284c7;">Rebate Mailer</div>
                                                <h1 style="margin:12px 0 0 0; font-size:28px; line-height:1.3; color:#0f172a;">
                                                    {{ data_get($preview, 'subject.renderedText', 'Chưa có subject') }}
                                                </h1>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="padding:28px 32px 36px 32px; background:#ffffff;">
                                                @include('mail.partials.campaign-recipient-content', [
                                                    'preview' => $preview,
                                                    'signatureSnapshot' => $signatureSnapshot ?? null,
                                                ])
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </div>
    </body>
</html>
