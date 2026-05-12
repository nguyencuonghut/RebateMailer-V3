<script setup lang="ts">
import AppLayout from '@/layout/AppLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import type { PageProps } from '@/types';

const page = usePage<PageProps>();
const printPage = (): void => { window.print(); };
</script>

<template>
    <AppLayout :app-name="page.props.appName">
        <Head title="Hướng dẫn sử dụng" />

        <div class="ug-page">

            <!-- ── Page header ──────────────────────────────────────────── -->
            <div class="ug-card ug-page-header no-print">
                <div>
                    <h1 class="ug-page-title">Hướng dẫn sử dụng</h1>
                    <p class="ug-page-sub">RebateMailer v3 · Hệ thống điều phối gửi mail chiết khấu</p>
                </div>
                <button class="ug-print-btn" @click="printPage">
                    🖨️ In / Lưu PDF
                </button>
            </div>

            <!-- ── TOC ──────────────────────────────────────────────────── -->
            <div class="ug-card">
                <div class="ug-toc-title">📋 Mục lục</div>
                <ol class="ug-toc">
                    <li><a href="#s1">Tổng quan & Phân quyền</a>
                        <ul class="sub"><li><a href="#s1-intro">Giới thiệu</a></li><li><a href="#s1-nav">Điều hướng</a></li><li><a href="#s1-roles">Phân quyền</a></li><li><a href="#s1-flow">Quy trình tổng quát</a></li></ul>
                    </li>
                    <li><a href="#s2">Đăng nhập & Hồ sơ cá nhân</a></li>
                    <li><a href="#s3">Bảng điều khiển vận hành</a></li>
                    <li><a href="#s4">Import dữ liệu</a>
                        <ul class="sub"><li><a href="#s4-prepare">Chuẩn bị file Excel</a></li><li><a href="#s4-upload">Tải file lên</a></li><li><a href="#s4-result">Kết quả import</a></li><li><a href="#s4-history">Lịch sử nhập dữ liệu</a></li></ul>
                    </li>
                    <li><a href="#s5">Thiết kế mẫu email</a>
                        <ul class="sub"><li><a href="#s5-list">Danh sách template</a></li><li><a href="#s5-canvas">Thiết kế canvas</a></li><li><a href="#s5-activate">Đặt làm template hoạt động</a></li></ul>
                    </li>
                    <li><a href="#s6">Điều phối gửi mail</a>
                        <ul class="sub"><li><a href="#s6-create">Tạo chiến dịch</a></li><li><a href="#s6-sample">Gửi mail mẫu</a></li><li><a href="#s6-schedule">Lên lịch gửi</a></li><li><a href="#s6-dispatch">Gửi ngay</a></li><li><a href="#s6-monitor">Theo dõi kết quả</a></li><li><a href="#s6-retry">Thử lại</a></li><li><a href="#s6-resend">Gửi lại</a></li><li><a href="#s6-export">Export lỗi</a></li></ul>
                    </li>
                    <li><a href="#s7">Người dùng <span class="badge b-admin">Chỉ Admin</span></a></li>
                    <li><a href="#s8">Câu hỏi thường gặp</a></li>
                </ol>
            </div>

            <!-- ═══════════════════════════ SECTION 1 — TỔNG QUAN -->
            <div id="s1" class="ug-card">
                <div class="sec-head">
                    <div class="sec-num">1</div>
                    <div><h2 class="sec-title">Tổng quan & Phân quyền</h2><p class="sec-sub">Giới thiệu, cấu trúc giao diện và vai trò người dùng</p></div>
                </div>

                <h3 id="s1-intro">Giới thiệu</h3>
                <p><strong>RebateMailer</strong> là hệ thống điều phối gửi mail chiết khấu tự động hàng tháng cho các đại lý / nhà phân phối:</p>
                <ul class="bl">
                    <li>Import dữ liệu chiết khấu từ file Excel (4 sheet) lên hệ thống.</li>
                    <li>Thiết kế template email với bảng chiết khấu động, tự động điền số liệu riêng của từng khách hàng.</li>
                    <li>Tạo và điều phối chiến dịch gửi hàng loạt email cá nhân hoá.</li>
                    <li>Theo dõi trạng thái gửi theo thời gian thực (tự động làm mới mỗi 5 giây), thử lại mail thất bại, gửi lại mail thành công, xuất báo cáo lỗi.</li>
                </ul>

                <h3 id="s1-nav">Điều hướng hệ thống</h3>
                <p>Thanh menu bên trái chia thành các nhóm:</p>
                <div class="sidebar-mock">
                    <div class="sg">Trang chính</div>
                    <div class="si active"><span>🏠</span> Tổng quan</div>
                    <div class="sg">Nền tảng</div>
                    <div class="si"><span>📥</span> Import dữ liệu</div>
                    <div class="si"><span>✉️</span> Thiết kế mẫu email</div>
                    <div class="si"><span>📤</span> Điều phối gửi mail</div>
                    <div class="sg">Quản trị</div>
                    <div class="si"><span>👥</span> Người dùng</div>
                    <div class="sg">Trợ giúp</div>
                    <div class="si"><span>📖</span> Hướng dẫn sử dụng</div>
                </div>
                <p>Góc trên phải: nhấn avatar / tên tài khoản → <strong>Hồ sơ cá nhân</strong> hoặc <strong>Đăng xuất</strong>.</p>

                <h3 id="s1-roles">Phân quyền người dùng</h3>
                <table class="dtable">
                    <thead><tr><th>Tính năng</th><th style="text-align:center">Admin</th><th style="text-align:center">Người dùng</th><th style="text-align:center">Khách</th></tr></thead>
                    <tbody>
                        <tr><td>Xem Bảng điều khiển vận hành</td><td style="text-align:center" class="check">✓</td><td style="text-align:center" class="check">✓</td><td style="text-align:center" class="check">✓</td></tr>
                        <tr><td>Import dữ liệu Excel</td><td style="text-align:center" class="check">✓</td><td style="text-align:center" class="check">✓</td><td style="text-align:center" class="cross">—</td></tr>
                        <tr><td>Thiết kế mẫu email</td><td style="text-align:center" class="check">✓</td><td style="text-align:center" class="check">✓</td><td style="text-align:center" class="cross">—</td></tr>
                        <tr><td>Tạo & điều phối chiến dịch</td><td style="text-align:center" class="check">✓</td><td style="text-align:center" class="check">✓</td><td style="text-align:center" class="cross">—</td></tr>
                        <tr><td>Quản lý Người dùng</td><td style="text-align:center" class="check">✓</td><td style="text-align:center" class="cross">—</td><td style="text-align:center" class="cross">—</td></tr>
                    </tbody>
                </table>
                <div class="callout warn">
                    <span>⚠️</span>
                    <div>Tài khoản <strong>Khách</strong> chỉ xem Dashboard, không thao tác được. Giao diện hiển thị <span class="badge b-muted">Chỉ xem</span>; tài khoản có quyền hiển thị <span class="badge b-ok">Có quyền điều phối</span>.</div>
                </div>

                <h3 id="s1-flow">Quy trình tổng quát mỗi tháng</h3>
                <div class="flow">
                    <div class="flow-step">① Import dữ liệu</div><div class="flow-arrow">→</div>
                    <div class="flow-step">② Kiểm tra kết quả</div><div class="flow-arrow">→</div>
                    <div class="flow-step">③ Tạo chiến dịch</div><div class="flow-arrow">→</div>
                    <div class="flow-step">④ Gửi mail mẫu</div><div class="flow-arrow">→</div>
                    <div class="flow-step">⑤ Gửi ngay / Lên lịch</div><div class="flow-arrow">→</div>
                    <div class="flow-step">⑥ Theo dõi & Xử lý lỗi</div>
                </div>
            </div>

            <!-- ═══════════════════════════ SECTION 2 — ĐĂNG NHẬP -->
            <div id="s2" class="ug-card">
                <div class="sec-head">
                    <div class="sec-num">2</div>
                    <div><h2 class="sec-title">Đăng nhập & Hồ sơ cá nhân</h2><p class="sec-sub">Truy cập hệ thống và quản lý tài khoản</p></div>
                </div>

                <h3>Đăng nhập</h3>
                <ol class="steps">
                    <li><div class="st"><span class="st-t">Mở trình duyệt</span>Nhập địa chỉ hệ thống (ví dụ: <code>http://172.16.2.100</code>). Trang đăng nhập hiển thị.</div></li>
                    <li><div class="st"><span class="st-t">Nhập thông tin</span>Điền <strong>Email</strong> và <strong>Mật khẩu</strong> đã được cấp.</div></li>
                    <li><div class="st"><span class="st-t">Nhấn Đăng nhập</span>Hệ thống chuyển về trang <strong>Bảng điều khiển vận hành</strong>.</div></li>
                </ol>
                <div class="callout info"><span>🔑</span><div>Nếu quên mật khẩu, liên hệ Admin để được đặt lại.</div></div>

                <h3 id="s2-profile">Cập nhật thông tin & Đổi mật khẩu</h3>
                <ol class="steps">
                    <li><div class="st"><span class="st-t">Vào Hồ sơ cá nhân</span>Nhấn avatar góc trên phải → <strong>"Hồ sơ cá nhân"</strong>.</div></li>
                    <li><div class="st"><span class="st-t">Cập nhật thông tin</span>Chỉnh sửa Tên, Email. Nhấn <span class="btn btn-primary">Lưu thay đổi</span>.</div></li>
                    <li><div class="st"><span class="st-t">Đổi mật khẩu – "Cập nhật mật khẩu"</span>Cuộn xuống mục <strong>"Cập nhật mật khẩu"</strong>. Điền mật khẩu hiện tại → mật khẩu mới. Nhấn <span class="btn btn-primary">Lưu mật khẩu mới</span>.</div></li>
                </ol>
            </div>

            <!-- ═══════════════════════════ SECTION 3 — DASHBOARD -->
            <div id="s3" class="ug-card">
                <div class="sec-head">
                    <div class="sec-num">3</div>
                    <div><h2 class="sec-title">Bảng điều khiển vận hành</h2><p class="sec-sub">Menu: Tổng quan</p></div>
                </div>

                <h3>Sức khỏe gửi mail</h3>
                <ul class="bl">
                    <li><strong>Đã gửi thành công</strong> – Tổng số mail gửi thành công.</li>
                    <li><strong>Đang queued</strong> – Số mail đang chờ trong hàng đợi.</li>
                    <li><strong>Lỗi gửi</strong> – Số mail thất bại.</li>
                    <li><strong>Tỷ lệ lỗi</strong> – Phần trăm mail lỗi so với tổng đã xử lý.</li>
                </ul>
                <div class="callout info"><span>💡</span><div>Dữ liệu có thể xem <strong>"Tĩnh"</strong> hoặc <strong>"Tự động mỗi N giây"</strong> tùy cài đặt.</div></div>

                <h3>Điều hướng nhanh</h3>
                <ul class="bl">
                    <li><span class="btn btn-primary">Mở import dữ liệu</span> – Chuyển đến trang Import dữ liệu.</li>
                    <li><span class="btn btn-primary">Mở điều phối gửi mail</span> – Chuyển đến trang Điều phối gửi mail.</li>
                </ul>

                <h3>Batch import gần đây & Chiến dịch gần đây</h3>
                <p>Hiển thị 5 batch / 5 chiến dịch mới nhất. Nhấn <span class="btn btn-outline">Mở batch</span> hoặc <span class="btn btn-outline">Mở chiến dịch</span> để xem chi tiết.</p>
            </div>

            <!-- ═══════════════════════════ SECTION 4 — IMPORT -->
            <div id="s4" class="ug-card">
                <div class="sec-head">
                    <div class="sec-num">4</div>
                    <div><h2 class="sec-title">Import dữ liệu</h2><p class="sec-sub">Menu: Import dữ liệu</p></div>
                </div>

                <p>Trang gồm 5 card: <strong>Tải file dữ liệu · Lịch sử nhập dữ liệu · Thông tin file đã nhận · Cấu trúc tệp Excel · Kết quả import</strong>.</p>

                <h3 id="s4-prepare">Chuẩn bị file Excel</h3>
                <p>File phải là <code>.xlsx</code> với đúng <strong>4 sheet</strong>:</p>
                <div class="tabs-mock">
                    <div class="tab act">Tổng hợp</div><div class="tab">Khoán NPP</div><div class="tab">Cám cá</div><div class="tab">Key Account</div>
                </div>
                <div class="tab-body">
                    <strong>Tổng hợp</strong> – Chiết khấu khách thường. Cột bắt buộc: STT, Tháng, Mã số, Mã &amp; tên khách hàng, Email, Địa chỉ, Thức ăn chăn nuôi, Tổng sản lượng, Doanh thu, Tổng cộng, Bằng chữ.<br/><br/>
                    <strong>Khoán NPP</strong> – Khách tham gia khoán (có thể trống).<br/>
                    <strong>Cám cá</strong> – Khách bán cám cá (có thể trống).<br/>
                    <strong>Key Account</strong> – Khách Key Account, tách biệt hoàn toàn khách thường.
                </div>
                <div class="callout warn"><span>⚠️</span><div>Dữ liệu bắt đầu từ <strong>dòng 1</strong>. Cột <strong>Email</strong> phải hợp lệ. Một khách Key Account không được xuất hiện ở sheet khách thường.</div></div>

                <h3 id="s4-upload">Tải file lên – Card "Tải file dữ liệu"</h3>
                <ol class="steps">
                    <li><div class="st"><span class="st-t">Nhập tên batch</span>Điền ô <strong>"Tên batch"</strong>. Ví dụ: <code>Dữ liệu chiết khấu tháng 03-2026</code></div></li>
                    <li><div class="st"><span class="st-t">Chọn file</span>Nhấn <span class="btn btn-outline">Chọn file Excel</span>, duyệt đến file <code>.xlsx</code>.</div></li>
                    <li><div class="st"><span class="st-t">Tải lên</span>Nhấn <span class="btn btn-primary">Tải file lên</span>. Hệ thống tự động upload, phân tích và aggregate dữ liệu.</div></li>
                    <li><div class="st"><span class="st-t">Hoàn tất</span>Card <strong>"Thông tin file đã nhận"</strong> và <strong>"Kết quả import"</strong> tự động hiển thị.</div></li>
                </ol>

                <h3 id="s4-result">Kết quả import – Tab "Dữ liệu hợp nhất"</h3>
                <div class="tabs-mock">
                    <div class="tab act">Dữ liệu hợp nhất</div><div class="tab">Tổng hợp</div><div class="tab">Khoán NPP</div><div class="tab">Cám cá</div><div class="tab">Key Account</div>
                </div>
                <div class="tab-body">Tab <strong>"Dữ liệu hợp nhất"</strong> hiển thị dữ liệu gom theo Mã số khách hàng — đây là dữ liệu thực tế dùng để tạo nội dung email. Kiểm tra số lượng khách và số liệu khớp file gốc trước khi tạo chiến dịch.</div>
                <div class="callout success"><span>✅</span><div>Sau khi xác nhận dữ liệu đúng, có thể tạo chiến dịch từ batch này.</div></div>

                <h3 id="s4-history">Lịch sử nhập dữ liệu – "Danh sách batch đã lưu"</h3>
                <table class="dtable">
                    <thead><tr><th>Mã batch</th><th>Tên batch</th><th>File nguồn</th><th>Trạng thái</th><th>Người import</th><th>Thời điểm</th><th>Mở lại</th></tr></thead>
                    <tbody><tr><td colspan="6" style="color:var(--ug-muted);font-size:.83rem">← Các batch đã import trước đây →</td><td><span class="btn btn-outline">Mở batch</span></td></tr></tbody>
                </table>
                <p style="margin-top:8px">Nhấn <span class="btn btn-outline">Mở batch</span> để tải lại kết quả import cũ. Nhấn <span class="btn btn-outline">Mở template</span> để chuyển sang thiết kế template với batch đó làm dữ liệu preview.</p>
            </div>

            <!-- ═══════════════════════════ SECTION 5 — TEMPLATES -->
            <div id="s5" class="ug-card">
                <div class="sec-head">
                    <div class="sec-num">5</div>
                    <div><h2 class="sec-title">Thiết kế mẫu email</h2><p class="sec-sub">Menu: Thiết kế mẫu email</p></div>
                </div>

                <p>Chỉ <strong>một template đang hoạt động</strong> tại mỗi thời điểm. Hệ thống dùng template đó để render email khi tạo chiến dịch.</p>

                <h3 id="s5-list">Danh sách template</h3>
                <table class="dtable">
                    <thead><tr><th>Tên template</th><th>Trạng thái</th><th>Số phần</th><th>Tạo bởi</th><th>Thao tác</th></tr></thead>
                    <tbody>
                        <tr><td>Template tháng 2026</td><td><span class="badge b-ok">Đang hoạt động</span></td><td>6</td><td>Admin</td><td><span class="btn btn-secondary">Xem</span></td></tr>
                        <tr><td>Template cũ 2025</td><td><span class="badge b-muted">Ngừng hoạt động</span></td><td>6</td><td>Admin</td><td><span class="btn btn-outline">Đặt làm template hoạt động</span></td></tr>
                    </tbody>
                </table>

                <h3 id="s5-canvas">Các tab trong trình thiết kế canvas</h3>
                <div class="tabs-mock">
                    <div class="tab act">Subject</div><div class="tab">Lời chào</div><div class="tab">Bảng chế độ tháng</div><div class="tab">Bảng chương trình khoán đặc biệt</div><div class="tab">Bảng chiết khấu cám cá</div><div class="tab">Bảng chiết khấu Key Account</div>
                </div>
                <div class="tab-body">
                    <strong>Subject</strong> – Tiêu đề email. Dùng biến <code>&#123;&#123;tháng&#125;&#125;</code>, <code>&#123;&#123;mã &amp; tên khách hàng&#125;&#125;</code>…<br/><br/>
                    <strong>Lời chào</strong> – Nội dung phần mở đầu email.<br/>
                    <strong>Bảng chế độ tháng</strong> – Dữ liệu từ sheet Tổng hợp (khách thường).<br/>
                    <strong>Bảng chương trình khoán đặc biệt</strong> – Dữ liệu từ sheet Khoán NPP.<br/>
                    <strong>Bảng chiết khấu cám cá</strong> – Dữ liệu từ sheet Cám cá.<br/>
                    <strong>Bảng chiết khấu Key Account</strong> – Dữ liệu riêng cho khách Key Account.
                </div>
                <ul class="bl" style="margin-top:10px">
                    <li><strong>Kéo thả</strong> để sắp xếp thứ tự dòng. Số thứ tự tự động nhảy (I, II… / 1, 2…).</li>
                    <li>Chọn <strong>"Dữ liệu preview theo batch import"</strong> và <strong>"Khách hàng preview"</strong> để xem email mẫu trực tiếp trên canvas.</li>
                </ul>

                <h3 id="s5-activate">Đặt làm template hoạt động</h3>
                <ol class="steps">
                    <li><div class="st"><span class="st-t">Trong danh sách</span>Tìm template muốn dùng (trạng thái <span class="badge b-muted">Ngừng hoạt động</span>).</div></li>
                    <li><div class="st"><span class="st-t">Nhấn "Đặt làm template hoạt động"</span>Nhấn nút <span class="btn btn-outline">Đặt làm template hoạt động</span>. Nút đổi thành <span class="btn btn-secondary">Đang kích hoạt...</span> trong lúc xử lý.</div></li>
                    <li><div class="st"><span class="st-t">Hoàn tất</span>Template chuyển sang <span class="badge b-ok">Đang hoạt động</span>. Template cũ tự động ngừng.</div></li>
                </ol>
            </div>

            <!-- ═══════════════════════════ SECTION 6 — MAIL -->
            <div id="s6" class="ug-card">
                <div class="sec-head">
                    <div class="sec-num">6</div>
                    <div><h2 class="sec-title">Điều phối gửi mail</h2><p class="sec-sub">Menu: Điều phối gửi mail · Tiêu đề trang: Gửi mail chiết khấu</p></div>
                </div>

                <p>Trang chia 2 khu vực: <strong>"Chiến dịch đang xem"</strong> (trái) và <strong>"Danh sách người nhận đã aggregate"</strong> (phải).</p>

                <h3>Trạng thái chiến dịch</h3>
                <div class="status-grid">
                    <div class="status-card sc-muted"><div class="sn"><span class="badge b-muted">Nháp</span></div><div class="sd">Mới tạo, chưa gửi.</div></div>
                    <div class="status-card sc-warn"><div class="sn"><span class="badge b-warn">Đã lên lịch</span></div><div class="sd">Đặt lịch gửi tự động.</div></div>
                    <div class="status-card sc-brand"><div class="sn"><span class="badge b-user">Đang gửi</span></div><div class="sd">Đang dispatch.</div></div>
                    <div class="status-card sc-ok"><div class="sn"><span class="badge b-ok">Hoàn thành</span></div><div class="sd">Tất cả gửi thành công.</div></div>
                    <div class="status-card sc-danger"><div class="sn"><span class="badge b-danger">Hoàn thành có lỗi</span></div><div class="sd">Xong nhưng có mail thất bại.</div></div>
                    <div class="status-card sc-muted"><div class="sn"><span class="badge b-muted">Đã hủy</span></div><div class="sd">Chiến dịch bị hủy.</div></div>
                </div>

                <h3 id="s6-create">Tạo chiến dịch gửi mail</h3>
                <div class="callout info"><span>📋</span><div>Trước khi tạo: đã import Excel thành công và có template <span class="badge b-ok">Đang hoạt động</span>.</div></div>
                <ol class="steps">
                    <li><div class="st"><span class="st-t">Nhấn "Tạo chiến dịch"</span>Nút <span class="btn btn-primary">Tạo chiến dịch</span>. Dialog <strong>"Tạo chiến dịch gửi mail"</strong> mở ra.</div></li>
                    <li><div class="st"><span class="st-t">Điền thông tin</span><strong>Tên chiến dịch</strong> · <strong>Batch nhập liệu</strong> (chọn batch đã aggregate) · <strong>Template email</strong> · <strong>Ghi chú</strong> (tuỳ chọn).</div></li>
                    <li><div class="st"><span class="st-t">Xác nhận</span>Nhấn <span class="btn btn-primary">Tạo chiến dịch</span>. Chiến dịch mới xuất hiện trạng thái <span class="badge b-muted">Nháp</span>.</div></li>
                </ol>

                <h3 id="s6-sample">Gửi mail mẫu</h3>
                <p>Kiểm tra nội dung trước khi gửi đại trà.</p>
                <ol class="steps">
                    <li><div class="st"><span class="st-t">Chọn chiến dịch Nháp</span>Nhấn vào chiến dịch trạng thái <span class="badge b-muted">Nháp</span>.</div></li>
                    <li><div class="st"><span class="st-t">Nhấn "Gửi mẫu"</span>Nút <span class="btn btn-outline">Gửi mẫu</span>. Dialog <strong>"Gửi mail mẫu"</strong> mở ra.</div></li>
                    <li><div class="st"><span class="st-t">Hệ thống chọn đại diện</span>Tối đa <strong>3 khách hàng đại diện</strong> (cả khách thường và Key Account nếu có). Mail gửi đến email thực của họ.</div></li>
                    <li><div class="st"><span class="st-t">Kiểm tra hòm thư</span>Kiểm tra tiêu đề, tên khách hàng, số tiền, định dạng bảng.</div></li>
                </ol>

                <h3 id="s6-schedule">Lên lịch gửi</h3>
                <ol class="steps">
                    <li><div class="st"><span class="st-t">Chọn chiến dịch Nháp</span>Nhấn vào chiến dịch trạng thái <span class="badge b-muted">Nháp</span>.</div></li>
                    <li><div class="st"><span class="st-t">Nhấn "Lên lịch gửi"</span>Nút <span class="btn btn-warn">Lên lịch gửi</span>. Chọn ngày và giờ. Xác nhận.</div></li>
                    <li><div class="st"><span class="st-t">Trạng thái chuyển sang "Đã lên lịch"</span>Hệ thống tự động gửi đúng thời điểm. Múi giờ: <strong>Asia/Ho_Chi_Minh (GMT+7)</strong>.</div></li>
                </ol>
                <div class="callout info"><span>💡</span><div>Để huỷ lịch: mở chi tiết chiến dịch <span class="badge b-warn">Đã lên lịch</span> và chọn huỷ lịch. Chiến dịch trở về <span class="badge b-muted">Nháp</span>.</div></div>

                <h3 id="s6-dispatch">Gửi ngay</h3>
                <ol class="steps">
                    <li><div class="st"><span class="st-t">Nhấn "Gửi ngay"</span>Nút <span class="btn btn-success">Gửi ngay</span>. Hộp thoại xác nhận hiển thị số lượng người nhận.</div></li>
                    <li><div class="st"><span class="st-t">Xác nhận</span>Chiến dịch chuyển sang <span class="badge b-user">Đang gửi</span>. Có thể đóng tab, hệ thống tiếp tục xử lý nền.</div></li>
                </ol>

                <h3 id="s6-monitor">Theo dõi kết quả</h3>
                <h4>Thẻ tóm tắt tiến độ</h4>
                <div class="card-row">
                    <div class="card-mini"><div class="cm-l">Tổng người nhận</div><div class="cm-v">500</div></div>
                    <div class="card-mini"><div class="cm-l">Chưa gửi</div><div class="cm-v" style="color:#64748b">12</div></div>
                    <div class="card-mini"><div class="cm-l">Đã vào hàng đợi</div><div class="cm-v" style="color:#2563eb">38</div></div>
                    <div class="card-mini"><div class="cm-l">Đã gửi</div><div class="cm-v" style="color:#059669">445</div></div>
                    <div class="card-mini"><div class="cm-l">Lỗi gửi</div><div class="cm-v" style="color:#dc2626">5</div></div>
                </div>
                <p>Thanh tiến độ: <strong>Hoàn thành tổng · Đã vào hàng đợi · Đã gửi thành công</strong> cập nhật realtime. Khi ổn định hiển thị <span class="badge b-ok">Dữ liệu đã ổn định</span>.</p>

                <h4>Bảng người nhận</h4>
                <table class="dtable">
                    <thead><tr><th>Mã số</th><th>Mã &amp; tên KH</th><th>Email</th><th>Nguồn dữ liệu</th><th>Trạng thái gửi</th><th>Lỗi gần nhất</th><th>Thao tác</th></tr></thead>
                    <tbody>
                        <tr><td>15001</td><td>15001 - Nguyễn A</td><td>a@mail.com</td><td>Tổng hợp</td><td><span class="badge b-ok">Đã gửi</span></td><td>—</td><td><span class="btn btn-outline">Xem trước email</span> <span class="btn btn-secondary">Gửi lại</span></td></tr>
                        <tr><td>15002</td><td>15002 - Trần B</td><td>b@mail.com</td><td>Key Account</td><td><span class="badge b-danger">Lỗi gửi</span></td><td>Mailbox full</td><td><span class="btn btn-warn">Chi tiết lỗi</span> <span class="btn btn-warn">Thử lại</span></td></tr>
                    </tbody>
                </table>
                <p style="margin-top:8px">Nút <span class="btn btn-danger">Chỉ xem lỗi (N)</span> lọc nhanh mail lỗi. Khi đang lọc đổi thành <span class="btn btn-secondary">Xem tất cả</span>. Lọc thêm theo dropdown <strong>Trạng thái gửi</strong>.</p>

                <h3 id="s6-retry">Thử lại – Mail lỗi</h3>
                <ol class="steps">
                    <li><div class="st"><span class="st-t">Lọc mail lỗi</span>Nhấn <span class="btn btn-danger">Chỉ xem lỗi (N)</span>.</div></li>
                    <li><div class="st"><span class="st-t">Nhấn "Thử lại"</span>Nhấn <span class="btn btn-warn">Thử lại</span> ở cột Thao tác. Hệ thống đưa mail vào hàng đợi ngay, không hỏi xác nhận.</div></li>
                    <li><div class="st"><span class="st-t">Theo dõi kết quả</span>Trạng thái cập nhật tự động mỗi 5 giây. Lặp lại nếu cần.</div></li>
                </ol>
                <div class="callout danger"><span>❗</span><div><strong>Nguyên nhân phổ biến thất bại:</strong> email sai / không tồn tại · hòm thư đầy · SMTP gián đoạn · bị chặn bởi spam filter.</div></div>

                <h3 id="s6-resend">Gửi lại – Mail đã gửi thành công</h3>
                <p>Dùng khi khách hàng đã nhận mail nhưng cần nhận lại (ví dụ: khách báo không tìm thấy).</p>
                <ol class="steps">
                    <li><div class="st"><span class="st-t">Tìm khách hàng</span>Chỉ mail trạng thái <span class="badge b-ok">Đã gửi</span> mới có nút <span class="btn btn-secondary">Gửi lại</span>.</div></li>
                    <li><div class="st"><span class="st-t">Nhấn "Gửi lại"</span>Dialog <strong>"Xác nhận gửi lại"</strong> hiển thị tên &amp; email khách kèm cảnh báo trùng mail.</div></li>
                    <li><div class="st"><span class="st-t">Xác nhận</span>Nhấn <span class="btn btn-secondary">Gửi lại</span> trong dialog. Nhấn <span class="btn btn-secondary">Hủy</span> để bỏ qua.</div></li>
                </ol>
                <div class="callout warn"><span>⚠️</span><div>Khách hàng sẽ nhận <strong>email trùng</strong>. Chỉ dùng khi thực sự cần thiết.</div></div>

                <h3 id="s6-export">Export lỗi</h3>
                <ol class="steps">
                    <li><div class="st"><span class="st-t">Mở chiến dịch có lỗi</span>Chiến dịch trạng thái <span class="badge b-danger">Hoàn thành có lỗi</span>.</div></li>
                    <li><div class="st"><span class="st-t">Nhấn "Export lỗi"</span>Nút <span class="btn btn-danger">Export lỗi</span> trong toolbar card người nhận.</div></li>
                    <li><div class="st"><span class="st-t">Tải file Excel</span>Trình duyệt tự tải về <code>.xlsx</code> chứa danh sách lỗi chi tiết.</div></li>
                </ol>
            </div>

            <!-- ═══════════════════════════ SECTION 7 — USERS -->
            <div id="s7" class="ug-card">
                <div class="sec-head">
                    <div class="sec-num">7</div>
                    <div><h2 class="sec-title">Người dùng <span class="badge b-admin">Chỉ Admin</span></h2><p class="sec-sub">Menu: Người dùng · Tiêu đề trang: Quản lý người dùng</p></div>
                </div>

                <div class="callout warn"><span>🔒</span><div>Mục <strong>"Người dùng"</strong> trên menu chỉ hiển thị với tài khoản <strong>Admin</strong>.</div></div>

                <h3>Danh sách tài khoản</h3>
                <p>4 thẻ thống kê: <strong>Tổng tài khoản · Admin · Người dùng · Khách</strong>.</p>
                <table class="dtable">
                    <thead><tr><th>Họ tên</th><th>Email</th><th>Vai trò</th><th>Trạng thái</th><th>Thao tác</th></tr></thead>
                    <tbody>
                        <tr><td>Nguyễn Văn A</td><td>a@ct.com</td><td><span class="badge b-admin">Admin</span></td><td><span class="badge b-ok">Đã xác thực</span></td><td><span class="btn btn-outline">Sửa</span></td></tr>
                        <tr><td>Trần Thị B</td><td>b@ct.com</td><td><span class="badge b-user">Người dùng</span></td><td><span class="badge b-muted">Chưa xác thực</span></td><td><span class="btn btn-outline">Sửa</span> <span class="btn btn-danger">Xóa</span></td></tr>
                    </tbody>
                </table>

                <h3>Tạo người dùng mới</h3>
                <ol class="steps">
                    <li><div class="st"><span class="st-t">Nhấn "Tạo người dùng"</span>Nút <span class="btn btn-primary">Tạo người dùng</span>.</div></li>
                    <li><div class="st"><span class="st-t">Điền thông tin</span><strong>Họ tên · Email · Vai trò</strong> (Admin / Người dùng / Khách) <strong>· Mật khẩu · Xác nhận mật khẩu</strong>.</div></li>
                    <li><div class="st"><span class="st-t">Lưu</span>Nhấn <span class="btn btn-primary">Tạo người dùng</span>. Tài khoản có thể đăng nhập ngay.</div></li>
                </ol>

                <h3>Sửa & Xóa tài khoản</h3>
                <ol class="steps">
                    <li><div class="st"><span class="st-t">Nhấn "Sửa"</span>Nút <span class="btn btn-outline">Sửa</span>. Dialog <strong>"Cập nhật người dùng"</strong> mở ra.</div></li>
                    <li><div class="st"><span class="st-t">Cập nhật</span>Chỉnh Họ tên, Email, Vai trò. Để đặt lại mật khẩu: điền <strong>Mật khẩu mới</strong> &amp; <strong>Xác nhận mật khẩu mới</strong> (để trống nếu không đổi). Nhấn <span class="btn btn-primary">Lưu thay đổi</span>.</div></li>
                    <li><div class="st"><span class="st-t">Xóa</span>Nhấn <span class="btn btn-danger">Xóa</span> → xác nhận <em>"Xóa tài khoản {email}?"</em>. Nhấn <span class="btn btn-secondary">Hủy</span> để bỏ qua.</div></li>
                </ol>
                <div class="callout danger"><span>❗</span><div>Không thể xóa tài khoản Admin cuối cùng.</div></div>
            </div>

            <!-- ═══════════════════════════ SECTION 8 — FAQ -->
            <div id="s8" class="ug-card">
                <div class="sec-head">
                    <div class="sec-num">8</div>
                    <div><h2 class="sec-title">Câu hỏi thường gặp</h2><p class="sec-sub">Xử lý các tình huống phổ biến</p></div>
                </div>

                <h3>❓ Import file thất bại?</h3>
                <ul class="bl">
                    <li>Đúng <strong>4 sheet</strong>: <em>Tổng hợp, Khoán NPP, Cám cá, Key Account</em>.</li>
                    <li>Dữ liệu từ <strong>dòng 1</strong>. File phải là <code>.xlsx</code>.</li>
                    <li>Kiểm tra card <strong>"Cấu trúc tệp Excel"</strong> nếu hệ thống báo lỗi sheet.</li>
                </ul>

                <h3>❓ Mail lỗi liên tục dù đã Thử lại nhiều lần?</h3>
                <ul class="bl">
                    <li>Nhấn <span class="btn btn-warn">Chi tiết lỗi</span> → <strong>"Xem chi tiết kỹ thuật"</strong> để đọc lỗi gốc.</li>
                    <li><em>"invalid email"</em>: kiểm tra cột Email trong file Excel.</li>
                    <li><em>"Mailbox full"</em>: yêu cầu khách dọn hòm thư rồi nhấn <span class="btn btn-warn">Thử lại</span>.</li>
                    <li>Dùng <span class="btn btn-danger">Export lỗi</span> để chuyển danh sách cho bộ phận liên quan.</li>
                </ul>

                <h3>❓ Chiến dịch "Đã lên lịch" không tự gửi?</h3>
                <ul class="bl">
                    <li>Kiểm tra múi giờ – hệ thống dùng <strong>Asia/Ho_Chi_Minh (GMT+7)</strong>.</li>
                    <li>Liên hệ quản trị hệ thống kiểm tra dịch vụ Scheduler.</li>
                </ul>

                <h3>❓ Nội dung email sai số liệu?</h3>
                <ul class="bl">
                    <li>Dùng <span class="btn btn-outline">Xem trước email</span> kiểm tra trước khi gửi đại trà.</li>
                    <li>Nếu sai: import lại file Excel đúng → tạo chiến dịch mới từ batch mới.</li>
                </ul>

                <h3>❓ Quên mật khẩu?</h3>
                <ul class="bl">
                    <li>Liên hệ Admin: <strong>Người dùng</strong> → <span class="btn btn-outline">Sửa</span> → điền mật khẩu mới → <span class="btn btn-primary">Lưu thay đổi</span>.</li>
                </ul>

                <h3>❓ Mail hay vào spam?</h3>
                <ul class="bl">
                    <li>Dùng <span class="btn btn-outline">Gửi mẫu</span> kiểm tra hòm thư spam trước khi gửi đại trà.</li>
                    <li>Liên hệ quản trị hệ thống để cấu hình SPF/DKIM/DMARC.</li>
                </ul>
            </div>

            <!-- Footer -->
            <div class="ug-footer no-print">
                RebateMailer v3 &nbsp;·&nbsp; Hướng dẫn sử dụng &nbsp;·&nbsp; Tháng 5/2026
            </div>

        </div><!-- /ug-page -->
    </AppLayout>
</template>

<style scoped>
/* ── CSS variables ─────────────────────────────────────────────────── */
.ug-page {
    --ug-brand:        #0d9488;
    --ug-brand-dark:   #0f766e;
    --ug-brand-light:  #ccfbf1;
    --ug-accent:       #059669;
    --ug-accent-light: #d1fae5;
    --ug-warn:         #d97706;
    --ug-warn-light:   #fef3c7;
    --ug-danger:       #dc2626;
    --ug-danger-light: #fee2e2;
    --ug-purple:       #7c3aed;
    --ug-purple-light: #ede9fe;
    --ug-border:       #e2e8f0;
    --ug-bg:           #f8fafc;
    --ug-muted:        #64748b;
    --ug-text:         #1e293b;
    --ug-surface:      #ffffff;
    --ug-neutral-bg:   #f1f5f9;
    --ug-neutral-text: #475569;
    --ug-radius:       12px;
    --ug-radius-sm:    6px;
    --ug-shadow:       0 4px 14px rgba(0,0,0,.08);
    padding: 0 0 48px;
    background: transparent;
}

:global(.app-dark .ug-page) {
    --ug-brand:        #2dd4bf;
    --ug-brand-dark:   #99f6e4;
    --ug-brand-light:  rgba(20, 184, 166, 0.18);
    --ug-accent:       #10b981;
    --ug-accent-light: rgba(16, 185, 129, 0.18);
    --ug-warn:         #f59e0b;
    --ug-warn-light:   rgba(245, 158, 11, 0.18);
    --ug-danger:       #ef4444;
    --ug-danger-light: rgba(239, 68, 68, 0.18);
    --ug-purple:       #a78bfa;
    --ug-purple-light: rgba(167, 139, 250, 0.18);
    --ug-border:       rgba(71, 85, 105, 0.42);
    --ug-bg:           rgba(15, 23, 42, 0.5);
    --ug-muted:        #94a3b8;
    --ug-text:         #e2e8f0;
    --ug-surface:      rgba(15, 23, 42, 0.86);
    --ug-neutral-bg:   rgba(51, 65, 85, 0.45);
    --ug-neutral-text: #94a3b8;
    --ug-shadow:       0 4px 20px rgba(0,0,0,.45);
}

@media print {
    .ug-page { padding: 0; }
    .ug-card { box-shadow: none !important; border: 1px solid #ddd; page-break-inside: avoid; }
    .no-print { display: none !important; }
    .ug-page-header { display: none; }
    @page { margin: 18mm 16mm; size: A4; }
}

/* ── Page header ────────────────────────────────────────────────────── */
.ug-page-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
    padding-bottom: 0;
}
.ug-page-title { font-size: 1.5rem; font-weight: 800; color: var(--ug-brand-dark); }
.ug-page-sub   { font-size: .85rem; color: var(--ug-muted); margin-top: 2px; }
.ug-print-btn {
    display: inline-flex; align-items: center; gap: 7px;
    background: var(--ug-brand); color: white; border: none;
    border-radius: 8px; padding: 9px 18px; font-size: .85rem;
    font-weight: 600; cursor: pointer; transition: background .15s;
}
.ug-print-btn:hover { background: var(--ug-brand-dark); }

/* ── Card ───────────────────────────────────────────────────────────── */
.ug-card {
    background: var(--ug-surface);
    border: 1px solid var(--ug-border);
    border-radius: var(--ug-radius);
    box-shadow: var(--ug-shadow);
    padding: 32px 36px;
    margin-bottom: 24px;
}

/* ── TOC ────────────────────────────────────────────────────────────── */
.ug-toc-title { font-size: 1.1rem; font-weight: 700; color: var(--ug-brand-dark); margin-bottom: 18px; padding-bottom: 12px; border-bottom: 2px solid var(--ug-brand-light); }
.ug-toc { list-style: none; counter-reset: toc; padding: 0; margin: 0; }
.ug-toc > li { counter-increment: toc; margin-bottom: 5px; }
.ug-toc > li > a { display: flex; align-items: center; gap: 9px; color: var(--ug-text); text-decoration: none; font-weight: 600; padding: 6px 0; border-bottom: 1px solid var(--ug-border); font-size: .93rem; }
.ug-toc > li > a:hover { color: var(--ug-brand); }
.ug-toc > li > a::before { content: counter(toc, decimal-leading-zero); min-width: 28px; height: 24px; line-height: 24px; text-align: center; background: var(--ug-brand-light); color: var(--ug-brand-dark); border-radius: 5px; font-size: .75rem; font-weight: 700; }
.ug-toc .sub { list-style: none; margin: 4px 0 2px 37px; padding: 0; }
.ug-toc .sub li { margin: 2px 0; }
.ug-toc .sub a { color: var(--ug-muted); text-decoration: none; font-size: .85rem; }
.ug-toc .sub a:hover { color: var(--ug-brand); }
.ug-toc .sub a::before { content: "→ "; color: var(--ug-brand); }

/* ── Section header ─────────────────────────────────────────────────── */
.sec-head { display: flex; align-items: center; gap: 13px; margin-bottom: 24px; padding-bottom: 14px; border-bottom: 2px solid var(--ug-brand-light); }
.sec-num  { display: flex; align-items: center; justify-content: center; width: 44px; height: 44px; min-width: 44px; background: var(--ug-brand); color: white; border-radius: 11px; font-size: 1.2rem; font-weight: 800; }
.sec-title { font-size: 1.25rem; font-weight: 700; color: var(--ug-brand-dark); }
.sec-sub   { font-size: .8rem; color: var(--ug-muted); margin-top: 2px; }

/* ── Typography ─────────────────────────────────────────────────────── */
h3 { font-size: 1rem; font-weight: 700; color: var(--ug-text); margin: 22px 0 9px; display: flex; align-items: center; gap: 7px; }
h3::before { content: ""; display: inline-block; width: 4px; height: 17px; background: var(--ug-brand); border-radius: 2px; }
h4 { font-size: .9rem; font-weight: 600; color: var(--ug-text); margin: 14px 0 6px; }
p  { margin-bottom: 9px; font-size: .9rem; line-height: 1.7; }
.bl { list-style: none; margin: 7px 0 12px; padding: 0; }
.bl li { padding: 3px 0 3px 15px; position: relative; font-size: .9rem; line-height: 1.65; }
.bl li::before { content: "•"; position: absolute; left: 0; color: var(--ug-brand); font-weight: 700; }

/* ── Steps ──────────────────────────────────────────────────────────── */
ol.steps { list-style: none; counter-reset: step; margin: 10px 0 18px; padding: 0; }
ol.steps > li { counter-increment: step; display: flex; gap: 12px; margin-bottom: 12px; align-items: flex-start; }
ol.steps > li::before { content: counter(step); display: flex; align-items: center; justify-content: center; min-width: 26px; height: 26px; background: var(--ug-brand); color: white; border-radius: 50%; font-size: .78rem; font-weight: 700; margin-top: 2px; flex-shrink: 0; }
.st { flex: 1; font-size: .9rem; line-height: 1.65; }
.st-t { display: block; font-weight: 600; color: var(--ug-brand-dark); margin-bottom: 1px; }

/* ── Callout ────────────────────────────────────────────────────────── */
.callout { border-radius: var(--ug-radius-sm); padding: 11px 14px; margin: 12px 0; display: flex; gap: 10px; align-items: flex-start; font-size: .88rem; line-height: 1.6; }
.callout span:first-child { flex-shrink: 0; margin-top: 1px; }
.info    { background: var(--ug-brand-light);  border-left: 4px solid var(--ug-brand); }
.success { background: var(--ug-accent-light); border-left: 4px solid var(--ug-accent); }
.warn    { background: var(--ug-warn-light);   border-left: 4px solid var(--ug-warn); }
.danger  { background: var(--ug-danger-light); border-left: 4px solid var(--ug-danger); }

/* ── Badges ─────────────────────────────────────────────────────────── */
.badge { display: inline-block; padding: 2px 8px; border-radius: 100px; font-size: .72rem; font-weight: 700; vertical-align: middle; white-space: nowrap; }
.b-admin  { background: var(--ug-purple-light); color: var(--ug-purple); }
.b-user   { background: var(--ug-brand-light);  color: var(--ug-brand-dark); }
.b-guest  { background: var(--ug-neutral-bg); color: var(--ug-neutral-text); }
.b-ok     { background: var(--ug-accent-light); color: var(--ug-accent); }
.b-warn   { background: var(--ug-warn-light);   color: var(--ug-warn); }
.b-danger { background: var(--ug-danger-light); color: var(--ug-danger); }
.b-muted  { background: var(--ug-neutral-bg); color: var(--ug-neutral-text); }

/* ── Buttons (mock) ─────────────────────────────────────────────────── */
.btn { display: inline-flex; align-items: center; gap: 3px; padding: 2px 9px; border-radius: 5px; font-size: .77rem; font-weight: 600; vertical-align: middle; white-space: nowrap; line-height: 1.7; }
.btn-primary   { background: var(--ug-brand);   color: white; }
.btn-success   { background: var(--ug-accent);  color: white; }
.btn-danger    { background: var(--ug-danger);  color: white; }
.btn-warn      { background: var(--ug-warn);    color: white; }
.btn-outline   { background: var(--ug-surface); color: var(--ug-brand);  border: 1px solid var(--ug-brand); }
.btn-secondary { background: var(--ug-neutral-bg); color: var(--ug-neutral-text); border: 1px solid var(--ug-border); }

/* ── Tables ─────────────────────────────────────────────────────────── */
.dtable { width: 100%; border-collapse: collapse; font-size: .83rem; margin: 10px 0; }
.dtable th { background: var(--ug-brand); color: white; text-align: left; padding: 8px 11px; font-weight: 600; white-space: nowrap; }
.dtable th:first-child { border-radius: var(--ug-radius-sm) 0 0 0; }
.dtable th:last-child  { border-radius: 0 var(--ug-radius-sm) 0 0; }
.dtable td { padding: 7px 11px; border-bottom: 1px solid var(--ug-border); vertical-align: middle; }
.dtable tr:nth-child(even) td { background: var(--ug-bg); }
.dtable tr:last-child td { border-bottom: none; }
.check { color: var(--ug-accent); font-size: 1rem; }
.cross { color: #cbd5e1; }

/* ── Sidebar mock ───────────────────────────────────────────────────── */
.sidebar-mock { border: 1px solid var(--ug-border); border-radius: var(--ug-radius-sm); max-width: 230px; overflow: hidden; margin: 12px 0; font-size: .88rem; }
.sg  { padding: 6px 12px 3px; font-size: .7rem; font-weight: 700; color: var(--ug-muted); text-transform: uppercase; letter-spacing: .8px; }
.si  { padding: 7px 14px; display: flex; align-items: center; gap: 9px; color: var(--ug-text); border-left: 3px solid transparent; }
.si.active { background: var(--ug-brand-light); color: var(--ug-brand-dark); font-weight: 600; border-left-color: var(--ug-brand); }

/* ── Tabs mock ──────────────────────────────────────────────────────── */
.tabs-mock { display: flex; flex-wrap: wrap; gap: 0; margin: 10px 0 0; }
.tab { padding: 6px 13px; font-size: .8rem; font-weight: 600; border: 1px solid var(--ug-border); border-bottom: none; border-radius: var(--ug-radius-sm) var(--ug-radius-sm) 0 0; background: var(--ug-bg); color: var(--ug-muted); margin-right: 3px; }
.tab.act { background: var(--ug-surface); color: var(--ug-brand-dark); border-top: 3px solid var(--ug-brand); }
.tab-body { border: 1px solid var(--ug-border); border-radius: 0 var(--ug-radius-sm) var(--ug-radius-sm) var(--ug-radius-sm); padding: 13px 15px; background: var(--ug-surface); margin-bottom: 10px; font-size: .87rem; line-height: 1.65; }

/* ── Flow ───────────────────────────────────────────────────────────── */
.flow { display: flex; flex-wrap: wrap; gap: 5px; align-items: center; margin: 12px 0; }
.flow-step  { background: var(--ug-brand); color: white; border-radius: var(--ug-radius-sm); padding: 6px 13px; font-size: .79rem; font-weight: 600; white-space: nowrap; }
.flow-arrow { color: var(--ug-brand); font-weight: 700; }

/* ── Status grid ────────────────────────────────────────────────────── */
.status-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(170px, 1fr)); gap: 8px; margin: 10px 0; }
.status-card { border-radius: var(--ug-radius-sm); padding: 11px 13px; border: 1px solid var(--ug-border); }
.sc-muted  { border-top: 3px solid var(--ug-muted); }
.sc-warn   { border-top: 3px solid var(--ug-warn); }
.sc-brand  { border-top: 3px solid var(--ug-brand); }
.sc-ok     { border-top: 3px solid var(--ug-accent); }
.sc-danger { border-top: 3px solid var(--ug-danger); }
.sn { font-weight: 700; font-size: .82rem; margin-bottom: 3px; }
.sd { font-size: .78rem; color: var(--ug-muted); line-height: 1.5; }

/* ── Summary cards ──────────────────────────────────────────────────── */
.card-row  { display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 8px; margin: 10px 0; }
.card-mini { border: 1px solid var(--ug-border); border-radius: var(--ug-radius-sm); padding: 10px 12px; background: var(--ug-surface); }
.cm-l { font-size: .75rem; color: var(--ug-muted); }
.cm-v { font-size: 1.2rem; font-weight: 800; color: var(--ug-brand-dark); }

/* ── Inline code ────────────────────────────────────────────────────── */
code { background: var(--ug-neutral-bg); color: var(--ug-brand-dark); padding: 1px 5px; border-radius: 4px; font-family: 'Consolas','Courier New',monospace; font-size: .83em; }

/* ── Footer ─────────────────────────────────────────────────────────── */
.ug-footer { text-align: center; font-size: .8rem; color: var(--ug-muted); padding-top: 16px; border-top: 1px solid var(--ug-border); }
</style>
