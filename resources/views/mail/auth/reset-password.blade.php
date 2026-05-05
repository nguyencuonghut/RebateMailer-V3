<!DOCTYPE html>
<html lang="vi" xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="x-apple-disable-message-reformatting">
        <title>Đặt lại mật khẩu</title>
    </head>
    <body style="margin:0; padding:0; width:100%; background-color:#eef4f7; font-family:'Be Vietnam Pro', Arial, sans-serif; color:#0f172a;">
        <div style="display:none; max-height:0; overflow:hidden; opacity:0;">
            Yêu cầu đặt lại mật khẩu cho tài khoản {{ $appName }}.
        </div>

        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="width:100%; border-collapse:collapse; background-color:#eef4f7; margin:0; padding:24px 0;">
            <tr>
                <td align="center" style="padding:24px 16px;">
                    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="width:100%; max-width:640px; border-collapse:collapse;">
                        <tr>
                            <td style="padding-bottom:16px; text-align:left; font-size:12px; letter-spacing:0.28em; font-weight:700; color:#0f766e;">
                                REBATEMAILERV3
                            </td>
                        </tr>
                        <tr>
                            <td style="background-color:#ffffff; border:1px solid #dbe6ec; border-radius:20px; padding:40px 32px; box-shadow:0 10px 30px rgba(15, 23, 42, 0.08);">
                                <p style="margin:0 0 12px; font-size:14px; line-height:22px; color:#475569;">
                                    Xin chào{{ filled($recipientName) ? ' '.$recipientName : '' }},
                                </p>

                                <h1 style="margin:0 0 16px; font-size:28px; line-height:36px; font-weight:800; color:#0f172a;">
                                    Yêu cầu đặt lại mật khẩu
                                </h1>

                                <p style="margin:0 0 16px; font-size:16px; line-height:28px; color:#334155;">
                                    Chúng tôi đã nhận được yêu cầu đặt lại mật khẩu cho tài khoản đăng nhập vào hệ thống <strong>{{ $appName }}</strong>.
                                </p>

                                <p style="margin:0 0 24px; font-size:16px; line-height:28px; color:#334155;">
                                    Nhấn vào nút bên dưới để thiết lập mật khẩu mới. Liên kết này sẽ hết hạn sau <strong>{{ $expireMinutes }} phút</strong>.
                                </p>

                                <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse; margin:0 0 24px;">
                                    <tr>
                                        <td align="center" bgcolor="#0f766e" style="border-radius:14px;">
                                            <a
                                                href="{{ $resetUrl }}"
                                                style="display:inline-block; padding:14px 24px; font-size:15px; line-height:22px; font-weight:700; color:#ffffff; text-decoration:none; border-radius:14px;"
                                            >
                                                Đặt lại mật khẩu
                                            </a>
                                        </td>
                                    </tr>
                                </table>

                                <p style="margin:0 0 16px; font-size:14px; line-height:24px; color:#64748b;">
                                    Nếu bạn không thực hiện yêu cầu này, có thể bỏ qua email. Mật khẩu hiện tại của bạn sẽ không bị thay đổi.
                                </p>

                                <p style="margin:0 0 8px; font-size:14px; line-height:24px; color:#64748b;">
                                    Nếu nút không hoạt động, hãy sao chép và mở liên kết sau trong trình duyệt:
                                </p>
                                <p style="margin:0 0 24px; font-size:13px; line-height:22px; color:#0f766e; word-break:break-all;">
                                    <a href="{{ $resetUrl }}" style="color:#0f766e; text-decoration:underline;">{{ $resetUrl }}</a>
                                </p>

                                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="width:100%; border-collapse:collapse; border-top:1px solid #e2e8f0; margin-top:24px;">
                                    <tr>
                                        <td style="padding-top:20px; font-size:13px; line-height:22px; color:#64748b;">
                                            Bộ phận hỗ trợ: <a href="mailto:{{ $supportEmail }}" style="color:#0f766e; text-decoration:none;">{{ $supportEmail }}</a><br>
                                            Email này được gửi tự động từ hệ thống {{ $appName }}.
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding:16px 8px 0; font-size:12px; line-height:20px; text-align:center; color:#64748b;">
                                Vui lòng không trả lời trực tiếp email này nếu hộp thư không được giám sát.
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </body>
</html>
