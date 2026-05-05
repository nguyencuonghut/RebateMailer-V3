# Tài Liệu Đặc Tả Yêu Cầu Phần Mềm (Software Requirements Specification)

**Tên dự án:** Hệ thống Tự động Gửi Mail Chiết Khấu Hàng Tháng
**Phiên bản:** 1.0.0
**Ngày ban hành:** 05/05/2026

## 1. Giới thiệu chung (Introduction)

### 1.1. Mục đích (Purpose)
Tài liệu SRS này cung cấp đặc tả chi tiết về các yêu cầu chức năng, phi chức năng, và kiến trúc hệ thống cho "Phần mềm gửi mail chiết khấu hàng tháng". Tài liệu được thiết kế theo chuẩn kỹ sư hệ thống, kết hợp tư duy phát triển hiện đại (TypeScript/Zod validation, XState cho luồng trạng thái, và Vertical Slice Architecture) lấy cảm hứng từ bộ kỹ năng thực chiến của Matt Pocock, nhằm đảm bảo chất lượng, dễ dàng test (TDD), và tối ưu cho Solo Dev kết hợp AI.

### 1.2. Phạm vi dự án (Scope)
Hệ thống là một Web-based Admin Dashboard cho phép người dùng:
1.  **Import:** Tải lên các file Excel (.xlsx) chứa dữ liệu chiết khấu động của khách hàng.
2.  **Builder:** Thiết kế mẫu email (template) với cấu trúc kéo thả, lồng ghép (cha/con).
3.  **Dispatcher:** Tự động tổng hợp dữ liệu cá nhân hóa và gửi hàng ngàn email an toàn, không bị spam, đi kèm tính năng retry khi lỗi.

---

## 2. Mô tả tổng quan (Overall Description)

### 2.1. Phân loại đối tượng và Quy tắc nghiệp vụ (Business Rules)
Hệ thống xử lý hai tập khách hàng Mutually Exclusive (Độc quyền lẫn nhau):
* **Khách thường:** Dữ liệu được tổng hợp từ tối đa 3 sheets: `Tổng hợp` (tùy chọn), `Khoán NPP` (tùy chọn), và `Cám cá` (tùy chọn).
* **Key Account:** Dữ liệu chỉ được lấy duy nhất từ sheet `Key Account`.

*Quy tắc loại trừ:* Bất kỳ cột tiền/sản lượng nào có giá trị trống (`null`) hoặc bằng `0` sẽ bị loại bỏ hoàn toàn khỏi cây dữ liệu trước khi đưa vào template.

### 2.2. Môi trường công nghệ (Tech Stack)
* **Backend:** Laravel 13, PostgreSQL, Redis (Message Broker & Rate Limiting).
* **Frontend:** Vue 3 (Composition API), InertiaJS, TypeScript, PrimeVue v4.
* **Thư viện chuyên dụng:** `vuedraggable` (kéo thả UI), `Zod` (Runtime Type Validation), `Mailpit` (Local Mail Testing).

---

## 3. Đặc tả Yêu cầu Chức năng (Functional Requirements)
*Hệ thống được chia thành các Vertical Slices độc lập để dễ dàng triển khai và test trên UI.*

### Slice 1: File Ingestion & Data Normalization (Nhập và Chuẩn hóa dữ liệu)
* **FR-1.1 (Upload & Chunking):** Hệ thống cho phép upload file Excel dung lượng lớn. File được đọc bằng cơ chế chunking (ví dụ: 500 rows/chunk) để tránh Out of Memory.
* **FR-1.2 (Dynamic Column Parser):** Hệ thống nhận diện tự động các cột cố định (Mã số, Email, Địa chỉ) và các nhóm cột động (Ví dụ: `CT1`, `CT2`, `Nội dung CT1`).
* **FR-1.3 (Data Aggregation Engine):** * Tự động gom nhóm dựa trên định danh duy nhất: `Mã số`.
    * Gộp (Merge) dữ liệu từ nhiều sheet thành một đối tượng DTO (Data Transfer Object) thống nhất.
* **FR-1.4 (Strict Validation - Zod style):** Validate tính toàn vẹn. Ví dụ: Nếu một `Mã số` vừa xuất hiện ở `Key Account` vừa ở `Tổng hợp`, văng lỗi cảnh báo ngay lập tức.

### Slice 2: Visual Template Builder (Trình dựng mẫu Mail)
* **FR-2.1 (Drag & Drop Canvas):** Sử dụng `vuedraggable` để người dùng thiết kế body mail.
* **FR-2.2 (Nested Table Logic):** Cho phép kéo thụt lề các dòng (rows) để tạo quan hệ cha/con. Hệ thống tự động:
    * Tăng số thứ tự (Cha: I, II, III... / Con: 1, 2, 3...).
    * Thay đổi định dạng (Cha: `font-weight: 600`, Con: `font-weight: 400`).
* **FR-2.3 (Interpolation Engine):** Hỗ trợ chèn biến linh động theo cú pháp `{{ten_bien}}` vào Subject và Body.
* **FR-2.4 (Template State Management):** Chỉ cho phép 1 template được gán cờ `is_active = true` trong toàn hệ thống tại một thời điểm.

### Slice 3: Preview & Dispatcher (Xem trước và Gửi)
* **FR-3.1 (DataTable Preview):** Hiển thị dữ liệu DTO đã parse lên lưới PrimeVue DataTable. Chỉ cho phép gửi khi không có lỗi validation.
* **FR-3.2 (Redis Mail Queue):** Khi kích hoạt "Gửi", hệ thống tạo một `Batch_ID`, tách mỗi mail thành một Job độc lập đẩy vào Redis Queue.
* **FR-3.3 (Rate Limiting Anti-Spam):** Áp dụng kỹ thuật throttle của Redis để giới hạn tốc độ gửi (ví dụ: 50 emails/phút) để tránh bị server mail đích đánh dấu spam.

### Slice 4: Tracking & Retry Mechanism (Theo dõi và Thử lại)
* **FR-4.1 (Real-time Status):** Cập nhật trạng thái gửi (Pending, Success, Failed) cho từng email theo thời gian thực (Polling hoặc WebSockets).
* **FR-4.2 (Idempotent Retry):** Những mail thất bại có thể ấn nút "Retry". Hệ thống chỉ thực thi lại job cũ chứa sẵn payload, không cần parse lại file Excel.

---

## 4. Đặc tả Dữ liệu & Trạng thái (Data & State Models)

### 4.1. Cấu trúc Unified Payload DTO (TypeScript/Zod definition)
Dữ liệu chuẩn hóa trả về từ Backend cho mọi khách hàng:
