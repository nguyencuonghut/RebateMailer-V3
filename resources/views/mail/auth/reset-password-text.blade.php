YÊU CẦU ĐẶT LẠI MẬT KHẨU

Xin chào{{ filled($recipientName) ? ' '.$recipientName : '' }},

Chúng tôi đã nhận được yêu cầu đặt lại mật khẩu cho tài khoản đăng nhập vào hệ thống {{ $appName }}.

Truy cập liên kết sau để thiết lập mật khẩu mới. Liên kết này sẽ hết hạn sau {{ $expireMinutes }} phút:
{{ $resetUrl }}

Nếu bạn không thực hiện yêu cầu này, có thể bỏ qua email này. Mật khẩu hiện tại của bạn sẽ không bị thay đổi.

Hỗ trợ: {{ $supportEmail }}
