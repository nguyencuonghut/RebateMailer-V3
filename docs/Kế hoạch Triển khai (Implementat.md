# Kế hoạch Triển khai (Implementation Plan)
**Dự án:** Hệ thống Gửi Mail Chiết Khấu Tự động
**Phương pháp:** Vertical Slice Architecture (Triển khai theo lát cắt tính năng)
**Tư duy phát triển:** Solo Dev + AI Agents (Kế thừa các kỹ thuật từ Matt Pocock's Skills)

---

## 1. Nguyên tắc triển khai (Core Principles)
* **Vertical Slices:** Thay vì làm toàn bộ Database -> Backend -> Frontend, mỗi bước (Step) sẽ hoàn thiện một tính năng cụ thể từ DB đến UI để có thể test được ngay [cite: 240].
* **Type-Safe Boundary:** Sử dụng TypeScript và Zod để định nghĩa các "Contracts" giữa Backend và Frontend, đảm bảo dữ liệu Excel được parse chính xác trước khi xử lý [cite: 237].
* **AI-First Workflow:** Cấu trúc mã nguồn mô-đun hóa cao để AI Agents có thể hỗ trợ viết Unit Test và Component Logic hiệu quả [cite: 239].

---

## 2. Lộ trình chi tiết (Roadmap)

### Giai đoạn 0: Khởi tạo Hạ tầng (Foundational Infrastructure)
* **Task 0.1:** Khởi tạo project Laravel 13, cấu hình PostgreSQL và Redis [cite: 237].
* **Task 0.2:** Thiết lập InertiaJS với Vue 3 (Composition API) và TypeScript [cite: 237].
* **Task 0.3:** Cài đặt PrimeVue v4 và Layout Sakai-Vue làm Dashboard cơ bản [cite: 237, 238].
* **Task 0.4:** Cấu hình Mailpit để kiểm thử luồng gửi mail cục bộ [cite: 237].

### Giai đoạn 1: Slice 1 - Ingestion & Data Aggregator
* **Mục tiêu:** Triển khai luồng import theo các lát cắt nhỏ có thể test ngay trên UI, bắt đầu từ upload + preview tối thiểu, sau đó mở rộng dần parser cho từng sheet, hợp nhất dữ liệu theo `Mã số`, và **lưu DB theo từng lớp xử lý** để phục vụ review, validation, gửi mail và audit [cite: 1, 2].
* **Quyết định kiến trúc:** Giai đoạn 1 **không dừng ở preview trong bộ nhớ**. Hệ thống phải lưu lại:
  * thông tin và trạng thái của từng lần import;
  * dữ liệu đã parse theo từng sheet;
  * dữ liệu đã aggregate theo `Mã số`.
* **Mô hình lưu trữ cấp cao đề xuất:**
  * `import_batches`: metadata của mỗi lần import, trạng thái xử lý, người upload, file nguồn.
  * `import_batch_sheet_records`: dữ liệu đã parse theo từng sheet, giữ raw/business shape của từng record.
  * `import_batch_aggregated_records`: dữ liệu đã aggregate theo `Mã số`, tách rõ `Khách thường` và `Key Account`.
* **Task 1.1 (UI Shell + Upload):** Tạo giao diện import tối thiểu gồm chọn file, upload, hiển thị trạng thái xử lý, và khung preview rỗng để có thể test end-to-end luồng upload ngay từ đầu.
* **Task 1.2 (Workbook Boundary + Batch Draft):** Đọc workbook, chỉ nhận đúng 4 sheet import (`Tổng hợp`, `Khoán NPP`, `Cám cá`, `Key Account`), và tạo nháp `import_batch` đầu tiên để theo dõi vòng đời import.
* **Task 1.3 (Parser - Sheet Tổng hợp + Persist Parsed Record):** Parse riêng sheet `Tổng hợp`, chuẩn hóa cột cố định + cột động theo tháng, hiển thị preview, đồng thời lưu parsed record vào DB theo batch.
* **Task 1.4 (Parser - Sheet Khoán NPP + Persist Parsed Record):** Parse riêng sheet `Khoán NPP`, normalize block `Nội dung CT n | SL | đ/kg | Thành tiền`, hiển thị preview, đồng thời lưu parsed record vào DB theo batch.
* **Task 1.5 (Parser - Sheet Cám cá + Persist Parsed Record):** Parse riêng sheet `Cám cá`, tách rõ cột rời rạc và cặp `CTn | Thành tiền`, hiển thị preview, đồng thời lưu parsed record vào DB theo batch.
* **Task 1.6 (Parser - Sheet Key Account + Persist Parsed Record):** Parse riêng sheet `Key Account`, chuẩn hóa nhóm cột rời rạc + block chương trình, hiển thị preview, đồng thời lưu parsed record vào DB theo batch.
* **Task 1.7 (Aggregator + Persist Aggregated Record):** Viết thuật toán gom dữ liệu từ 4 sheet theo `Mã số`, giữ đúng quy tắc phân loại `Khách thường` và `Key Account`, rồi lưu kết quả aggregate vào DB [cite: 6, 7, 13].
* **Task 1.8 (Validation):** Sử dụng Laravel Validation và Zod-style contracts để đánh dấu lỗi/cảnh báo như thiếu email, xung đột `Key Account`, header không hợp lệ, hoặc dữ liệu bất thường, và cập nhật trạng thái batch/record [cite: 5, 25].
* **Task 1.9 (Unified Preview + Batch Review):** Nâng cấp DataTable preview để hiển thị dữ liệu đã gộp hoàn chỉnh từ DB, filter theo loại khách, trạng thái hợp lệ, và chỉ cho phép đi tiếp khi không có lỗi chặn.

### Giai đoạn 2: Slice 2 - Visual Template Builder
* **Mục tiêu:** Cho phép người dùng thiết kế mẫu mail bằng kéo thả và phân cấp cha/con [cite: 4, 70].
* **Task 2.1 (Component):** Phát triển bộ Designer sử dụng `vuedraggable` [cite: 70].
* **Task 2.2 (Logic):** Xử lý logic thụt lề (Indentation) và tự động định dạng (Bold cho mục cha, Regular cho mục con) [cite: 71, 72, 73].
* **Task 2.3 (Parser):** Xây dựng Engine thay thế biến động `{{tháng}}`, `{{tên khách hàng}}` trong Subject và Body [cite: 75].
* **Task 2.4 (Persistence):** Lưu trữ cấu trúc Template dưới dạng JSON vào Postgres.

### Giai đoạn 3: Slice 3 - Queue Dispatcher & Tracking
* **Mục tiêu:** Biến dữ liệu đã aggregate và template đã thiết kế ở Giai đoạn 1-2 thành một **chiến dịch gửi mail vận hành được trong thực tế**, có preview đầy đủ, có schedule, có queue/throttle chống spam, có dashboard realtime theo từng batch import, và có khả năng retry khi từng mail gặp lỗi.
* **Phạm vi nghiệp vụ cốt lõi của Giai đoạn 3:**
  * Người dùng tạo **chiến dịch gửi mail** với các trường bắt buộc:
    * `Tên chiến dịch`
    * `Batch nhập liệu` (chính là một `import_batch` đã import và aggregate xong)
    * `Template email`
    * `Ghi chú` (optional)
  * Toàn bộ giao diện của Giai đoạn 3 phải là **tiếng Việt có dấu 100%**:
    * tiêu đề, label, placeholder, trạng thái, thông báo lỗi, tooltip, nút bấm, empty state, toast message;
    * không để sót wording tiếng Anh kiểu `preview`, `retry`, `schedule`, `failed`, `progress`, `dispatch`, trừ khi đó là dữ liệu kỹ thuật nội bộ không hiển thị cho người dùng cuối.
  * Sau khi tạo chiến dịch, hệ thống hiển thị **DataTable danh sách dữ liệu đã aggregated của batch đó**:
    * có global search;
    * có filter tối thiểu theo loại khách, trạng thái gửi, trạng thái lỗi;
    * là nơi người dùng duyệt nhanh tập người nhận trước khi gửi.
  * Mỗi dòng người nhận phải có nút **Preview full nội dung email**:
    * hiển thị đầy đủ `Subject`;
    * `Lời chào`;
    * 4 bảng dữ liệu sau khi render bằng dữ liệu aggregate thật của đúng khách trong đúng batch.
  * Người dùng có thể tạo **schedule gửi mail**:
    * gửi ngay;
    * hoặc hẹn giờ gửi.
  * Hệ thống gửi mail hàng loạt qua queue, có cấu hình để **giảm nguy cơ bị đánh spam**.
  * Có **dashboard theo dõi tiến độ gửi mail**, trong đó:
    * có progress bar theo từng `campaign`;
    * có progress bar theo từng `batch import`;
    * trạng thái được cập nhật realtime theo tiến độ xử lý thực tế từ backend/queue workers.
  * Hệ thống ghi nhận **lỗi chi tiết của từng mail**:
    * lỗi là gì;
    * xảy ra lúc nào;
    * đã thử mấy lần;
    * có nút `Retry` cho từng mail lỗi hoặc retry hàng loạt theo filter.

* **Mô hình lưu trữ cấp cao đề xuất cho Giai đoạn 3:**
  * `mail_campaigns`:
    * metadata của chiến dịch (`name`, `import_batch_id`, `mail_template_canvas_id`, `notes`, `schedule_at`, `status`, `created_by`);
    * snapshot tối thiểu của ngữ cảnh gửi để phục vụ audit.
  * `mail_campaign_recipients`:
    * một dòng cho mỗi aggregated record / người nhận;
    * chứa `campaign_id`, `import_batch_aggregated_record_id`, email đích, loại khách, trạng thái gửi, số lần thử, message lỗi gần nhất, timestamp gửi thành công/thất bại.
  * `mail_campaign_dispatch_logs` hoặc `mail_campaign_attempts`:
    * log chi tiết từng lần attempt gửi mail;
    * lưu response/exception message để phục vụ audit, retry và điều tra lỗi.
  * **Nguyên tắc quan trọng:**
    * `campaign` là lớp orchestration;
    * dữ liệu nguồn vẫn lấy từ `import_batch_aggregated_records`;
    * nội dung mail vẫn render từ `template canvas + selected part versions`;
    * không copy toàn bộ payload aggregate sang nơi khác nếu không có lý do audit rõ ràng.

* **Task 3.1 (Campaign Creation + Recipient Table):**
  * Tạo UI cho form tạo chiến dịch:
    * `Tên chiến dịch`
    * `Batch nhập liệu`
    * `Template email`
    * `Ghi chú`
  * Chỉ cho phép chọn:
    * batch đã aggregate thành công;
    * template/canvas đang hợp lệ để gửi.
  * Sau khi tạo thành công:
    * sinh `campaign`;
    * materialize danh sách `mail_campaign_recipients` từ `import_batch_aggregated_records` của batch đã chọn;
    * mở DataTable danh sách người nhận với global search.
  * DataTable người nhận cần hiển thị tối thiểu:
    * `Mã số`
    * `Mã & tên khách hàng`
    * `Email`
    * `Loại khách`
    * `Trạng thái gửi`
    * `Lỗi gần nhất`
    * `Thao tác`

* **Task 3.2 (Full Email Preview):**
  * Cho phép bấm `Preview full nội dung email` trên từng recipient.
  * Preview phải render từ dữ liệu thật:
    * đúng `campaign`;
    * đúng `template canvas`;
    * đúng `aggregated record` của khách đó.
  * UI preview cần cho thấy:
    * subject;
    * body HTML/text;
    * 4 bảng sau khi áp rule `ẩn khi = 0 hoặc rỗng`;
    * cảnh báo nếu part nào đó chưa render được do thiếu dữ liệu.
  * Đây là bề mặt QA cuối trước khi dispatch hàng loạt, nên preview không được dùng sample giả.
  * Nội dung HTML render ra phải được kiểm tra qua HTML check của Mailpit:
    * ưu tiên đạt điểm cao nhất có thể;
    * tránh markup dễ làm giảm deliverability hoặc giảm khả năng hiển thị trên mail client;
    * các regression test/email smoke test nên khóa lại những lỗi HTML nghiêm trọng trước khi cho phép dispatch.

* **Task 3.3 (Schedule + Dispatch Orchestrator):**
  * Thêm lựa chọn:
    * `Gửi ngay`
    * `Lên lịch gửi`
  * Khi đến giờ schedule:
    * hệ thống mở dispatch campaign;
    * đưa từng recipient vào queue bằng job riêng.
  * Cần có service orchestration kiểu:
    * `StartMailCampaignDispatchService`
    * `DispatchMailCampaignRecipientJob`
  * Trạng thái campaign đề xuất:
    * `draft`
    * `scheduled`
    * `dispatching`
    * `completed`
    * `completed_with_failures`
    * `paused`
    * `cancelled`

* **Task 3.4 (Performance + Anti-Spam Guardrails):**
  * Sử dụng queue + Redis để dispatch hàng loạt theo nền.
  * Cấu hình throttle/rate limit rõ ràng để tránh burst gửi quá nhanh:
    * giới hạn số mail / phút;
    * giới hạn concurrency worker cho mail dispatch;
    * thêm khoảng nghỉ ngắn giữa các chunk nếu cần.
  * Chia dispatch theo chunk/batch nhỏ thay vì bắn toàn bộ cùng lúc.
  * Thiết kế để dễ thay đổi chiến lược anti-spam theo SMTP provider:
    * warm-up domain/IP;
    * cap throughput theo giờ;
    * retry có backoff;
    * tách lỗi tạm thời và lỗi vĩnh viễn.
  * Đây là nơi phải chuẩn bị boundary để sau này nếu đổi SMTP provider hoặc dùng API provider thì flow vẫn giữ được.
  * Chất lượng HTML email là một phần của anti-spam/deliverability:
    * dùng cấu trúc email-friendly;
    * hạn chế CSS/markup dễ bị mail client cắt bỏ;
    * kiểm tra định kỳ bằng Mailpit HTML check trước khi campaign được đánh dấu sẵn sàng gửi.

* **Task 3.5 (Realtime Dashboard & Progress Tracking):**
  * Tạo dashboard theo dõi chiến dịch gửi mail:
    * tổng số mail;
    * số `pending`;
    * số `sending`;
    * số `sent`;
    * số `failed`;
    * tỷ lệ hoàn thành.
  * Có progress bar:
    * theo `campaign`;
    * theo `batch import` gắn với campaign.
  * Progress phải update realtime từ backend:
    * qua broadcast/websocket hoặc polling ngắn hạn nếu chưa có realtime stack hoàn chỉnh;
    * nguồn sự thật là trạng thái thực tế của `mail_campaign_recipients`.
  * Dashboard cũng cần hiển thị tốc độ gửi thực tế:
    * số mail/phút;
    * thời điểm bắt đầu;
    * thời điểm hoàn thành dự kiến/đã hoàn thành.

* **Task 3.6 (Failure Diagnostics + Retry):**
  * Mỗi mail lỗi phải lưu được:
    * exception message;
    * loại lỗi (`SMTP timeout`, `invalid recipient`, `authentication failed`, ...);
    * số lần thử;
    * thời điểm lỗi gần nhất.
  * UI phải cho phép:
    * xem chi tiết lỗi của từng mail;
    * retry từng mail;
    * retry theo nhóm mail `failed`;
    * optionally retry toàn bộ campaign nếu chỉ còn failed subset.
  * Retry phải idempotent:
    * không gửi lại những mail đã `sent`;
    * chỉ enqueue lại những recipient còn trạng thái retryable.

* **Task 3.7 (Campaign Audit & Operational Safety):**
  * Ghi log rõ ai tạo campaign, khi nào schedule, khi nào bắt đầu gửi, khi nào hoàn thành.
  * Lưu đủ dữ kiện để điều tra sau này:
    * batch nào đã dùng;
    * template nào đã dùng;
    * version nào của từng part đã được ghép vào thời điểm gửi.
  * Nếu campaign đang `dispatching`, các thay đổi lên template mới không được làm sai nội dung của campaign đã bắt đầu gửi:
    * cần snapshot hoặc khóa reference rõ ràng tại thời điểm dispatch.

* **Acceptance Criteria tổng cho Giai đoạn 3:**
  * Tạo được campaign từ `batch import + template email`.
  * Nhìn thấy DataTable người nhận đã aggregate ngay sau khi tạo campaign.
  * Preview full email đúng theo dữ liệu thật của từng khách.
  * HTML email render qua Mailpit HTML check với điểm số cao và không còn lỗi nghiêm trọng chặn gửi.
  * Toàn bộ giao diện người dùng của module gửi mail hiển thị bằng tiếng Việt có dấu 100%.
  * Tạo được schedule gửi mail.
  * Dispatch hàng loạt qua queue với throttle chống spam.
  * Dashboard hiển thị progress realtime theo campaign/batch.
  * Lỗi từng mail được ghi chi tiết.
  * Có thể retry mail lỗi mà không gửi trùng mail đã thành công.

---

## 3. Quản lý rủi ro & Kiểm thử (Quality Assurance)
* **Data Integrity:** Kiểm thử parser và thuật toán Aggregator với các bộ dữ liệu Excel biên:
  * Khách chỉ có ở sheet `Cám cá`
  * Khách tham gia nhiều chương trình khoán
  * Khách `Key Account` không được trùng với khách thường
  * Header động thay đổi theo tháng nhưng vẫn parse đúng [cite: 11, 12]
* **Persistence Integrity:** Kiểm thử tính nhất quán giữa:
  * file upload nguồn;
  * parsed record đã lưu;
  * aggregated record đã lưu;
  * trạng thái `import_batch` trên từng bước xử lý.
* **Security:** Sanitize toàn bộ dữ liệu từ Excel trước khi đưa vào Template để ngăn chặn XSS trong email.
* **Performance:** Kiểm tra tốc độ xử lý khi file Excel lên tới >5000 dòng.

---

## 4. Tài liệu tham khảo từ Yêu cầu hệ thống
* **Techstack:** Laravel 13, Vue 3, InertiaJs, Redis, TypeScript, PrimeVue v4 [cite: 237].
* **Cấu trúc dữ liệu Excel:** 4 sheets cố định bắt đầu từ dòng 1 [cite: 6, 7].
* **Quy tắc gửi mail:** 1 template hoạt động tại một thời điểm, nội dung cá nhân hóa theo từng loại khách [cite: 74, 76].
Ke_Hoach_Trien_Khai.md
Đang hiển thị Ke_Hoach_Trien_Khai.md.
