<!DOCTYPE html>
<html lang="vi">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>{{ data_get($preview, 'subject.renderedText', 'Xem trước email') }}</title>
    </head>
    <body style="margin:0; padding:0; background:#eef2f7; font-family: Arial, Helvetica, sans-serif; color:#0f172a; line-height:1.6;">
        <div style="padding:32px 16px;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                <tr>
                    <td align="center">
                        <table role="presentation" width="860" cellpadding="0" cellspacing="0" border="0" style="width:100%; max-width:860px;">
                            <tr>
                                <td style="padding:0 0 16px 0;">
                                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#ffffff; border:1px solid #dbe4ef; border-radius:20px;">
                                        @if ($isPreview ?? true)
                                        <tr>
                                            <td style="padding:24px 28px; border-bottom:1px solid #e2e8f0; background:#f8fafc;">
                                                <div style="font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:0.18em; color:#0284c7;">Chiến dịch gửi mail</div>
                                                <div style="margin-top:10px; font-size:16px; color:#334155;">
                                                    <strong>Từ chiến dịch:</strong> {{ $campaignName ?: 'Chưa đặt tên' }}<br>
                                                    <strong>Đến:</strong> {{ data_get($preview, 'recipient.customerFullName', 'Chưa có khách hàng') }} &lt;{{ data_get($preview, 'recipient.recipientEmail', 'chưa-có-email') ?: 'chưa-có-email' }}&gt;<br>
                                                    <strong>Chủ đề:</strong> {{ data_get($preview, 'subject.renderedText', 'Chưa có subject') }}
                                                </div>
                                            </td>
                                        </tr>
                                        @endif
                                        <tr>
                                            <td style="padding:32px; background:#ffffff;">
                                                @include('mail.partials.campaign-recipient-content', ['preview' => $preview, 'bodyHtml' => null])
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
