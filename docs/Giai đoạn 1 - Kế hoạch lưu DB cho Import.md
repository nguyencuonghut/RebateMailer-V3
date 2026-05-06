# Giai đoạn 1 - Kế hoạch lưu DB cho Import

**Phạm vi:** cập nhật cách triển khai `Giai đoạn 1: Slice 1 - Ingestion & Data Aggregator` theo kết luận mới: dữ liệu import phải được lưu DB, không chỉ preview trong bộ nhớ  
**Ngày cập nhật:** 06/05/2026

## 1. Quyết định đã chốt

- Giai đoạn 1 bắt buộc phải lưu DB cho 3 lớp dữ liệu:
  - thông tin và trạng thái mỗi lần import
  - dữ liệu đã parse theo từng sheet
  - dữ liệu đã aggregate theo `Mã số`
- Preview trên UI vẫn cần, nhưng preview phải có khả năng dựa trên dữ liệu đã lưu, không phụ thuộc hoàn toàn vào state tạm trong RAM.

## 2. Nguồn tham chiếu

- Nguồn gốc nghiệp vụ:
  - [Mô tả phần mềm.txt](/run/media/cuong/DATA/02_Project/205_RebateFlow/RebateMailer-V3/docs/Mô%20t%E1%BA%A3%20ph%E1%BA%A7n%20m%E1%BB%81m.txt:1)
  - [SRS.md](/run/media/cuong/DATA/02_Project/205_RebateFlow/RebateMailer-V3/docs/SRS.md:1)
- Nguồn cập nhật roadmap:
  - [Kế hoạch Triển khai (Implementat.md](/run/media/cuong/DATA/02_Project/205_RebateFlow/RebateMailer-V3/docs/K%E1%BA%BF%20ho%E1%BA%A1ch%20Tri%E1%BB%83n%20khai%20%28Implementat.md:1)
- Skill đã dùng:
  - `.ai/rules/engineering/zoom-out/SKILL.md`
  - `.ai/rules/engineering/to-issues/SKILL.md`

## 3. Nguyên tắc thiết kế

- Định danh nghiệp vụ để gom dữ liệu là `Mã số`.
- `Khách thường` và `Key Account` là 2 nhóm loại trừ lẫn nhau.
- Không đoán precedence của các field mơ hồ giữa nhiều sheet nếu tài liệu chưa chốt.
- Dữ liệu hợp nhất ở `Task 1.7` nên lưu theo kiểu `sectioned payload`:
  - `tongHop`
  - `khoanNpp`
  - `camCa`
  - `keyAccount`
- Validation và quyết định chặn/không chặn là nhiệm vụ của `Task 1.8`, không nhét vào `Task 1.7`.

## 4. Thực thể dữ liệu cấp cao

### 4.1. `import_batches`

- Mục đích:
  - đại diện cho một lần import file Excel
  - theo dõi trạng thái xử lý end-to-end
- Thông tin cấp cao nên có:
  - `id`
  - `batch_code`
  - `original_file_name`
  - `stored_path`
  - `uploaded_by`
  - `status`
  - `workbook_summary`
  - `started_at`
  - `completed_at`

### 4.2. `import_batch_sheet_records`

- Mục đích:
  - lưu dữ liệu đã parse của từng sheet theo từng record
- Thông tin cấp cao nên có:
  - `id`
  - `import_batch_id`
  - `sheet_name`
  - `customer_code`
  - `row_number`
  - `customer_type_inferred`
  - `parsed_payload`

### 4.3. `import_batch_aggregated_records`

- Mục đích:
  - lưu dữ liệu đã aggregate theo `Mã số`
- Thông tin cấp cao nên có:
  - `id`
  - `import_batch_id`
  - `customer_code`
  - `customer_type`
  - `source_sheets`
  - `aggregated_payload`
  - `validation_state`

## 5. Trạng thái batch đề xuất

- `uploaded`
- `workbook_analyzed`
- `parsed_partial`
- `parsed_complete`
- `aggregated`
- `validated_with_warnings`
- `validated_ready`
- `failed`

## 6. Kế hoạch thực hiện từng bước

### Bước 1 - Khóa schema DB cho import batch

- **Loại:** `AFK`
- **Mục tiêu:** có migration và model cho `import_batches`
- **Kết quả cần thấy:** mỗi lần upload tạo được một batch thật trong DB
- **Acceptance criteria:**
  - có migration cho `import_batches`
  - upload tạo được bản ghi batch
  - page import nhận được `batchId` hoặc `batchCode` thật

### Bước 2 - Gắn upload hiện tại với `import_batch`

- **Loại:** `AFK`
- **Blocked by:** `Bước 1`
- **Mục tiêu:** thay `upload receipt` thuần tạm bằng `receipt + batch reference`
- **Kết quả cần thấy:** sau upload, UI thấy thông tin file và mã batch
- **Acceptance criteria:**
  - response upload trả `importBatch`
  - trạng thái ban đầu là `uploaded`
  - retry upload không làm mất batch đang xem nếu user chưa đổi file

### Bước 3 - Lưu workbook boundary vào batch

- **Loại:** `AFK`
- **Blocked by:** `Bước 2`
- **Mục tiêu:** sau khi analyze workbook, metadata được ghi vào `import_batches`
- **Kết quả cần thấy:** batch có `workbook_summary` và status `workbook_analyzed`
- **Acceptance criteria:**
  - workbook boundary không chỉ trả ra UI mà còn được persist
  - lịch sử import đọc lại được metadata cơ bản mà không cần parse lại file

### Bước 4 - Tạo persistence cho parsed record từng sheet

- **Loại:** `AFK`
- **Blocked by:** `Bước 3`
- **Mục tiêu:** tạo bảng `import_batch_sheet_records` và chuẩn lưu payload parse
- **Kết quả cần thấy:** mỗi parser sheet lưu được record theo `import_batch_id`
- **Acceptance criteria:**
  - có schema `import_batch_sheet_records`
  - mỗi record parse giữ được `sheet_name`, `customer_code`, `row_number`, `parsed_payload`
  - các parser `Tổng hợp`, `Khoán NPP`, `Cám cá`, `Key Account` có thể ghi cùng một contract lưu trữ

### Bước 5 - Refactor parser từng sheet sang `parse + persist`

- **Loại:** `AFK`
- **Blocked by:** `Bước 4`
- **Mục tiêu:** chuyển 4 parser hiện có từ `preview only` sang `preview from persisted data`
- **Kết quả cần thấy:** UI preview từng sheet vẫn giống hiện tại nhưng dữ liệu đã được lấy từ DB sau khi persist
- **Acceptance criteria:**
  - parse sheet tạo record DB
  - preview sheet đọc lại từ DB theo batch hiện tại
  - reload trang vẫn xem lại được dữ liệu parse theo batch

### Bước 6 - Tạo persistence cho aggregated record

- **Loại:** `AFK`
- **Blocked by:** `Bước 5`
- **Mục tiêu:** tạo bảng `import_batch_aggregated_records`
- **Kết quả cần thấy:** kết quả gom theo `Mã số` được lưu thật
- **Acceptance criteria:**
  - có schema `import_batch_aggregated_records`
  - mỗi record aggregate lưu `customer_code`, `customer_type`, `source_sheets`, `aggregated_payload`

### Bước 7 - Refactor aggregator sang `aggregate + persist`

- **Loại:** `AFK`
- **Blocked by:** `Bước 6`
- **Mục tiêu:** thay aggregator in-memory bằng aggregator ghi DB
- **Kết quả cần thấy:** preview aggregator đọc từ bảng aggregate
- **Acceptance criteria:**
  - dùng parsed records đã lưu làm input
  - persist aggregated records
  - preview aggregator đọc lại từ DB theo `import_batch_id`

### Bước 8 - Bổ sung màn quản lý lịch sử import

- **Loại:** `AFK`
- **Blocked by:** `Bước 7`
- **Mục tiêu:** người dùng thấy danh sách các batch import đã tạo
- **Kết quả cần thấy:** UI có bảng lịch sử import với status và thời gian
- **Acceptance criteria:**
  - có danh sách batch
  - xem được số record parse và aggregate theo batch
  - có thể mở lại preview của một batch cũ

### Bước 9 - Gắn validation state vào batch và aggregate record

- **Loại:** `AFK`
- **Blocked by:** `Bước 8`
- **Mục tiêu:** chuẩn bị hạ tầng cho `Task 1.8`
- **Kết quả cần thấy:** batch và aggregated record có chỗ để ghi warning/error state
- **Acceptance criteria:**
  - có field `validation_state`
  - có contract để `Task 1.8` ghi lỗi/cảnh báo mà không đổi schema lần nữa

## 7. Cách hiển thị trên UI sau khi có lưu DB

### 7.1. Trên màn import hiện tại

- hiển thị:
  - `Mã batch`
  - tên file
  - thời điểm upload
  - trạng thái batch
- mỗi preview section (`Tổng hợp`, `Khoán NPP`, `Cám cá`, `Key Account`, `Aggregator`) nên cho biết:
  - đang đọc từ batch nào
  - số record đã lưu

### 7.2. Trên màn lịch sử import

- cột nên có:
  - `Mã batch`
  - file nguồn
  - người import
  - thời gian import
  - trạng thái
  - số record parsed
  - số record aggregated

## 8. Definition of Done

Kế hoạch này được coi là hoàn tất khi:

- roadmap đã phản ánh rõ yêu cầu lưu DB
- tồn tại kế hoạch theo từng bước đủ để triển khai không mơ hồ
- kiến trúc lưu trữ đã tách rõ:
  - `import_batches`
  - `import_batch_sheet_records`
  - `import_batch_aggregated_records`
- UI tương lai có hướng hiển thị rõ cho `batch import`, `parsed data`, `aggregated data`
