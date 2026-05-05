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
* **Mục tiêu:** Upload file Excel và hiển thị dữ liệu đã gộp (Aggregated) lên bảng [cite: 1, 2].
* **Task 1.1 (Backend):** Xây dựng `ExcelService` sử dụng Laravel Excel để đọc theo Chunk.
* **Task 1.2 (Logic):** Viết thuật toán Aggregator để merge dữ liệu từ 4 sheet (`Tổng hợp`, `Khoán NPP`, `Cám cá`, `Key Account`) dựa trên Mã số khách hàng [cite: 6, 7, 13].
* **Task 1.3 (Validation):** Sử dụng Zod/Laravel Validation để lọc các dòng lỗi hoặc thiếu Email [cite: 5, 25].
* **Task 1.4 (Frontend):** Tạo giao diện Upload và DataTable hiển thị Preview dữ liệu sau khi parse [cite: 2].

### Giai đoạn 2: Slice 2 - Visual Template Builder
* **Mục tiêu:** Cho phép người dùng thiết kế mẫu mail bằng kéo thả và phân cấp cha/con [cite: 4, 70].
* **Task 2.1 (Component):** Phát triển bộ Designer sử dụng `vuedraggable` [cite: 70].
* **Task 2.2 (Logic):** Xử lý logic thụt lề (Indentation) và tự động định dạng (Bold cho mục cha, Regular cho mục con) [cite: 71, 72, 73].
* **Task 2.3 (Parser):** Xây dựng Engine thay thế biến động `{{tháng}}`, `{{tên khách hàng}}` trong Subject và Body [cite: 75].
* **Task 2.4 (Persistence):** Lưu trữ cấu trúc Template dưới dạng JSON vào Postgres.

### Giai đoạn 3: Slice 3 - Queue Dispatcher & Tracking
* **Mục tiêu:** Gửi mail hàng loạt với hiệu năng cao và theo dõi trạng thái [cite: 238].
* **Task 3.1 (Job):** Tạo `SendDiscountEmailJob` xử lý render HTML và gửi qua SMTP.
* **Task 3.2 (Performance):** Cấu hình Redis Throttle để giới hạn tốc độ gửi, tránh bị đánh dấu Spam [cite: 238].
* **Task 3.3 (UI):** Dashboard theo dõi tiến độ gửi (Progress Bar) theo từng Batch ID.
* **Task 3.4 (Retry):** Xây dựng tính năng "Retry" cho các bản ghi có trạng thái `failed` [cite: 5].

---

## 3. Quản lý rủi ro & Kiểm thử (Quality Assurance)
* **Data Integrity:** Kiểm thử thuật toán Aggregator với các bộ dữ liệu Excel biên (Khách chỉ có ở sheet Cám cá, Khách tham gia nhiều chương trình khoán) [cite: 11, 12].
* **Security:** Sanitize toàn bộ dữ liệu từ Excel trước khi đưa vào Template để ngăn chặn XSS trong email.
* **Performance:** Kiểm tra tốc độ xử lý khi file Excel lên tới >5000 dòng.

---

## 4. Tài liệu tham khảo từ Yêu cầu hệ thống
* **Techstack:** Laravel 13, Vue 3, InertiaJs, Redis, TypeScript, PrimeVue v4 [cite: 237].
* **Cấu trúc dữ liệu Excel:** 4 sheets cố định bắt đầu từ dòng 1 [cite: 6, 7].
* **Quy tắc gửi mail:** 1 template hoạt động tại một thời điểm, nội dung cá nhân hóa theo từng loại khách [cite: 74, 76].
Ke_Hoach_Trien_Khai.md
Đang hiển thị Ke_Hoach_Trien_Khai.md.
