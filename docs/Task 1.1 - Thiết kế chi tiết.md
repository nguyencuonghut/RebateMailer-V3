# Task 1.1 - Thiết kế chi tiết

**Hạng mục:** Slice 1 - Ingestion & Data Aggregator  
**Task:** 1.1 (UI Shell + Upload) - Tạo giao diện import tối thiểu để test end-to-end luồng upload  
**Ngày cập nhật:** 05/05/2026  
**Trạng thái:** Draft để triển khai

## 1. Mục tiêu

`Task 1.1` phải tạo ra một lát cắt hoàn chỉnh có thể test ngay trên UI, dù backend parse workbook đầy đủ chưa xong.

Kết quả mong muốn:

- người dùng có quyền vào được màn `Import dữ liệu`;
- người dùng chọn file `.xlsx` và bấm upload;
- hệ thống gửi request upload thật lên backend;
- UI hiển thị được các trạng thái cơ bản:
  - chưa chọn file
  - đang upload
  - upload thành công
  - upload thất bại
- UI có khung preview tối thiểu để chuẩn bị cho `Task 1.2+`;
- toàn bộ luồng có thể test bằng thao tác thật trên trình duyệt.

## 2. Nguồn tham chiếu

- Nguồn tham chiếu gốc:
  - [Mô tả phần mềm.txt](/run/media/cuong/DATA/02_Project/205_RebateFlow/RebateMailer-V3/docs/Mô%20tả%20phần%20mềm.txt:1)
  - workbook mẫu `data/Data import chuẩn_Final.xlsx`
    - lưu ý: file mẫu có thêm sheet `Template Mail` chỉ để tham chiếu nội dung template, không thuộc input contract của import
- Nguồn đối chiếu bổ sung:
  - [Kế hoạch Triển khai (Implementat.md](/run/media/cuong/DATA/02_Project/205_RebateFlow/RebateMailer-V3/docs/Kế hoạch%20Triển%20khai%20%28Implementat.md:1)
  - [SRS.md](/run/media/cuong/DATA/02_Project/205_RebateFlow/RebateMailer-V3/docs/SRS.md:1)
  - [.ai/master_prompt.md](/run/media/cuong/DATA/02_Project/205_RebateFlow/RebateMailer-V3/.ai/master_prompt.md:1)

## 3. Phạm vi

### 3.1. Trong phạm vi

- thay placeholder `/imports` hiện tại bằng một page import thật;
- tạo form chọn file và submit file `.xlsx`;
- validate file ở mức UI và request boundary:
  - có chọn file
  - đúng extension hoặc MIME cơ bản
- gọi endpoint upload thật từ frontend;
- hiển thị status message, loading state và flash/error message bằng tiếng Việt;
- render khung preview tối thiểu với dữ liệu giả lập hoặc metadata tối thiểu từ backend;
- chuẩn bị shape dữ liệu UI đủ để `Task 1.2` nối tiếp mà không phải đập lại layout.

### 3.2. Ngoài phạm vi

- đọc workbook theo chunk;
- parse 4 sheet import;
- merge dữ liệu theo `Mã số`;
- validation business;
- DataTable preview hoàn chỉnh;
- lưu lịch sử import;
- cho phép gửi mail.

## 4. Actor và phân quyền

- `Admin`: được upload file.
- `Người dùng`: được upload file.
- `Khách`: chỉ được xem menu import nếu cần, nhưng không được upload.

Quyền áp dụng:

- `GET /imports`: `imports.view`
- `POST /imports/upload`: `imports.manage`

## 5. Quy tắc nghiệp vụ áp dụng ở Task 1.1

- file upload mục tiêu là `.xlsx`;
- dữ liệu import nghiệp vụ về sau chỉ gồm 4 sheet:
  - `Tổng hợp`
  - `Khoán NPP`
  - `Cám cá`
  - `Key Account`
- nhưng ở `Task 1.1`, UI chưa cần parse hay hiển thị nội dung 4 sheet;
- mục tiêu của bước này là dựng xong “vỏ thao tác” để người dùng upload thật và thấy hệ thống phản hồi thật.

## 6. Thiết kế UX

### 6.1. Màn hình

Trang `/imports` nên thay `ModulePage` bằng page thật, ví dụ `Imports/Index.vue`.

Các khối chính:

- `PageHeader`
  - tiêu đề: `Import dữ liệu`
  - mô tả ngắn: nêu rõ chỉ nhận file Excel chiết khấu tháng
- `UploadCard`
  - chọn file
  - tên file đã chọn
  - kích thước file
  - nút `Tải file lên`
  - nút `Xóa lựa chọn`
- `UploadStatus`
  - spinner khi đang upload
  - thông báo thành công/thất bại
- `PreviewShell`
  - card rỗng hoặc bảng placeholder
  - các ô summary tối thiểu như:
    - tên file
    - thời điểm upload
    - trạng thái parse
    - ghi chú “chi tiết preview sẽ được mở rộng ở Task 1.2+”

### 6.2. Trạng thái UI

Đề xuất state machine tối thiểu:

```ts
type ImportUploadState =
  | 'idle'
  | 'file_selected'
  | 'uploading'
  | 'uploaded'
  | 'failed';
```

Quy tắc:

- `idle`: chưa chọn file
- `file_selected`: đã chọn file, cho phép submit
- `uploading`: disable input và button submit
- `uploaded`: hiện metadata cơ bản từ backend
- `failed`: hiện lỗi và cho phép upload lại

## 7. Thiết kế thành phần

### 7.1. Frontend

Đề xuất file:

```text
resources/js/Pages/Imports/Index.vue
resources/js/Components/imports/ImportUploadCard.vue
resources/js/Components/imports/ImportPreviewShell.vue
```

Trách nhiệm:

- `Imports/Index.vue`
  - điều phối state upload
  - gọi `useForm` của Inertia
  - hiển thị flash/message
- `ImportUploadCard.vue`
  - vùng chọn file và action buttons
- `ImportPreviewShell.vue`
  - vùng preview tối thiểu, chỉ hiển thị summary trong `Task 1.1`

### 7.2. Backend

Đề xuất endpoint:

- `POST /imports/upload`

Trách nhiệm của backend trong `Task 1.1`:

- xác thực permission `imports.manage`
- validate request file ở mức tối thiểu
- nhận file upload
- trả response thành công với metadata đơn giản
- chưa parse workbook thật

Đề xuất controller/action:

```text
app/Http/Controllers/ImportUploadController.php
```

## 8. Contract request/response

### 8.1. Request

`POST /imports/upload`

`multipart/form-data`

- `file`: required

### 8.2. Response thành công

```json
{
  "status": "ok",
  "message": "Tải file lên thành công.",
  "data": {
    "originalFileName": "Data import chuẩn_Final.xlsx",
    "size": 15937026,
    "uploadedAt": "2026-05-05T10:30:00+07:00",
    "nextStep": "Sẵn sàng cho bước đọc workbook ở Task 1.2."
  }
}
```

### 8.3. Response lỗi

```json
{
  "status": "error",
  "message": "Tệp tải lên không hợp lệ.",
  "errors": {
    "file": [
      "Chỉ chấp nhận file Excel .xlsx."
    ]
  }
}
```

## 9. Route design

Đề xuất cập nhật route:

```php
Route::get('/imports', [ImportUploadController::class, 'index'])
    ->middleware('permission:imports.view')
    ->name('imports.index');

Route::post('/imports/upload', [ImportUploadController::class, 'store'])
    ->middleware('permission:imports.manage')
    ->name('imports.upload');
```

## 10. Dữ liệu trả về cho preview shell

Trong `Task 1.1`, preview chưa phải preview dữ liệu nghiệp vụ.  
Nó chỉ là `upload receipt`.

Đề xuất shape:

```ts
type ImportUploadReceipt = {
  originalFileName: string;
  size: number;
  uploadedAt: string;
  status: 'uploaded';
  nextStep: string;
};
```

Lý do:

- giữ đúng tinh thần vertical slice;
- tránh giả vờ parse workbook khi backend parse chưa làm;
- vẫn cho người dùng thấy hệ thống “đã hoạt động”;
- không khóa UI vào contract backend sâu của `Task 1.2+`.

## 11. Nội dung hiển thị bằng tiếng Việt

Toàn bộ text của page phải là tiếng Việt, gồm:

- label input
- button text
- validation message
- flash message
- empty-state preview
- loading text

Ví dụ:

- `Chọn file Excel`
- `Tải file lên`
- `Đang tải file lên...`
- `Tải file lên thành công.`
- `Vui lòng chọn file trước khi tiếp tục.`
- `Chỉ chấp nhận file Excel .xlsx.`

## 12. Kiểm thử

### 12.1. UI test thủ công

- vào `/imports` với user có quyền `imports.view`
- chọn một file `.xlsx`
- bấm upload
- thấy loading state
- nhận thông báo thành công
- thấy preview shell hiện metadata upload

### 12.2. Feature tests backend

- user có `imports.manage` upload file hợp lệ thành công
- user không có quyền bị chặn
- upload thiếu file bị lỗi validation
- upload file sai định dạng bị lỗi validation

### 12.3. Frontend behavior tests

- nút submit bị disable khi chưa chọn file
- khi upload, nút submit bị disable
- khi upload lỗi, message tiếng Việt hiển thị đúng
- khi upload xong, preview shell nhận đúng metadata

## 13. Tiêu chí hoàn thành

- menu `/imports` dẫn tới page import thật, không còn là placeholder page;
- upload `.xlsx` hoạt động được end-to-end;
- UI có loading, success, error state rõ ràng;
- response backend thật được hiển thị vào preview shell;
- permission `imports.manage` được áp vào action upload;
- toàn bộ text người dùng nhìn thấy là tiếng Việt;
- có test backend tối thiểu cho upload;
- page đủ ổn định để nối tiếp `Task 1.2`.

## 14. Phụ thuộc sang Task 1.2

`Task 1.1` phải để sẵn các điểm móc sau:

- page `/imports` đã tồn tại thật;
- form upload và endpoint upload đã nối thật;
- preview shell đã có chỗ để gắn metadata parse;
- response shape có thể mở rộng từ `ImportUploadReceipt` sang `WorkbookSummary`.

`Task 1.2` chỉ việc thay phần “upload receipt” bằng “workbook metadata”.

## 15. Kết luận

`Task 1.1` không nên làm parser backend nữa.  
Nó phải là lát cắt UI-first mỏng nhưng thật: người dùng upload được file, thấy hệ thống phản hồi, và có nền page import thật để các task parse sheet phía sau gắn vào dần.
