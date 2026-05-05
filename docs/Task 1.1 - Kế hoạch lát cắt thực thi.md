# Task 1.1 - Kế hoạch lát cắt thực thi

**Task lớn:** 1.1 (UI Shell + Upload)  
**Mục tiêu:** chia nhỏ `Task 1.1` thành các lát cắt rất mỏng, có thể làm tuần tự và test được ngay  
**Ngày cập nhật:** 05/05/2026

## 1. Nguồn gốc kế hoạch

- Nguồn tham chiếu gốc:
  - [Mô tả phần mềm.txt](/run/media/cuong/DATA/02_Project/205_RebateFlow/RebateMailer-V3/docs/Mô%20tả%20phần%20mềm.txt:1)
  - [Task 1.1 - Thiết kế chi tiết.md](/run/media/cuong/DATA/02_Project/205_RebateFlow/RebateMailer-V3/docs/Task%201.1%20-%20Thiết%20kế%20chi%20tiết.md:1)
- Nguồn đối chiếu:
  - [Kế hoạch Triển khai (Implementat.md](/run/media/cuong/DATA/02_Project/205_RebateFlow/RebateMailer-V3/docs/Kế hoạch%20Triển%20khai%20%28Implementat.md:1)
  - current code ở [routes/web.php](/run/media/cuong/DATA/02_Project/205_RebateFlow/RebateMailer-V3/routes/web.php:1), [ModulePage.vue](/run/media/cuong/DATA/02_Project/205_RebateFlow/RebateMailer-V3/resources/js/Pages/ModulePage.vue:1), [HandleInertiaRequests.php](/run/media/cuong/DATA/02_Project/205_RebateFlow/RebateMailer-V3/app/Http/Middleware/HandleInertiaRequests.php:1)
- Skill đã dùng:
  - `.ai/rules/engineering/to-issues/SKILL.md`
  - `.ai/rules/engineering/zoom-out/SKILL.md`

## 2. Nguyên tắc chia lát cắt

- Mỗi lát cắt phải đi xuyên suốt qua đủ lớp cần thiết: `permission -> route -> UI -> request/response -> test`.
- Không chia ngang kiểu “làm hết FE trước rồi mới làm BE”.
- Một lát cắt hoàn thành phải demo được hoặc verify được độc lập.
- `Task 1.1` chỉ dừng ở `upload receipt`, không trượt sang parse workbook của `Task 1.2`.

## 3. Hiện trạng codebase

- `/imports` hiện vẫn render placeholder `ModulePage`.
- Permission `imports.view` đã tồn tại và đang bảo vệ `GET /imports`.
- Permission `imports.manage` đã tồn tại nhưng chưa dùng cho upload thật.
- App đã có:
  - `AppLayout`
  - pattern `useForm` với Inertia
  - hệ thống auth/RBAC/locale tiếng Việt
- Chưa có:
  - page `Imports/Index.vue`
  - controller upload import
  - route `POST /imports/upload`
  - preview shell cho import
  - `flash` shared prop cho import flow nếu đi theo redirect-based UX

## 3.1. Quyết định kỹ thuật cho Task 1.1

- đổi `/imports` từ closure route sang controller sớm, tránh để `Task 1.2+` phải dọn lại route layer;
- ưu tiên `Inertia useForm` để giữ cùng pattern với auth/profile/users trong repo;
- với `Task 1.1`, dùng inline status card hoặc inline error thay vì thêm Toast service mới;
- backend chỉ trả `upload receipt`, chưa parse workbook;
- file upload tạm nên lưu tách biệt dưới một convention như `imports/tmp` để không phá contract ở `Task 1.2`.

## 4. Danh sách lát cắt

### Slice 1.1-A - Mở đường vào màn Import dữ liệu

- **Loại:** `AFK`
- **Blocked by:** Không có
- **Mục tiêu:** thay placeholder `/imports` bằng page thật, có tiêu đề và mô tả đúng nghiệp vụ import file Excel chiết khấu tháng.
- **Kết quả demo:** đăng nhập bằng `Admin` hoặc `Người dùng`, bấm menu `Import dữ liệu`, thấy page thật thay vì placeholder.
- **Acceptance criteria:**
  - route `/imports` render `Imports/Index.vue`
  - menu sidebar dẫn đúng tới page import
  - page hiển thị tiêu đề, mô tả và ngữ cảnh nhập file Excel chiết khấu
- **Rủi ro nếu gộp quá to:** nếu gộp luôn upload thật vào lát này, khi fail sẽ khó biết lỗi nằm ở navigation, route hay form upload.

### Slice 1.1-B - Gắn phân quyền truy cập cho màn Import

- **Loại:** `AFK`
- **Blocked by:** `Slice 1.1-A`
- **Mục tiêu:** chốt hành vi truy cập theo permission ngay từ đầu.
- **Kết quả demo:** `Admin` và `Người dùng` vào được `/imports`; actor không phù hợp bị chặn nhất quán.
- **Acceptance criteria:**
  - `imports.view` bảo vệ `GET /imports`
  - UI không lộ action upload cho actor không có quyền thao tác
  - response bị từ chối nhất quán nếu truy cập trái quyền
- **Rủi ro nếu gộp quá to:** nếu để permission tới cuối mới làm, các lát sau sẽ được build trên giả định actor sai.

### Slice 1.1-C - Dựng UploadCard với chọn file cục bộ

- **Loại:** `AFK`
- **Blocked by:** `Slice 1.1-B`
- **Mục tiêu:** có vùng chọn file, hiển thị tên file, dung lượng và cho phép xóa lựa chọn.
- **Kết quả demo:** chọn file `.xlsx` trên UI, thấy tên file và kích thước; bấm `Xóa lựa chọn` thì quay lại trạng thái rỗng.
- **Acceptance criteria:**
  - có input chọn file và button tiếng Việt
  - state `idle` và `file_selected` hoạt động đúng
  - file info hiển thị rõ ràng mà chưa cần submit backend
- **Rủi ro nếu gộp quá to:** nếu nhảy thẳng sang submit thật, sẽ khó tách bug UI state khỏi bug request.

### Slice 1.1-D - Chặn file sai ngay tại biên UI

- **Loại:** `AFK`
- **Blocked by:** `Slice 1.1-C`
- **Mục tiêu:** chỉ cho phép chọn/submit file `.xlsx` ở mức boundary tối thiểu.
- **Kết quả demo:** chọn file sai định dạng thì UI báo lỗi tiếng Việt và không cho bấm upload.
- **Acceptance criteria:**
  - không chọn file thì không submit được
  - file không phải `.xlsx` hiện lỗi tiếng Việt
  - lỗi validation hiển thị ngay tại card upload
- **Rủi ro nếu gộp quá to:** nếu bỏ qua boundary validation ở đây, các lát upload thật sẽ lẫn lỗi kỹ thuật và lỗi trải nghiệm.

### Slice 1.1-E - Tạo endpoint upload receipt tối thiểu

- **Loại:** `AFK`
- **Blocked by:** `Slice 1.1-D`
- **Mục tiêu:** có `POST /imports/upload` nhận file thật, validate tối thiểu và trả `upload receipt` thay vì parse workbook.
- **Kết quả demo:** chọn file `.xlsx`, bấm upload, backend nhận request và trả metadata cơ bản như tên file, dung lượng, thời điểm upload.
- **Acceptance criteria:**
  - endpoint nhận `multipart/form-data` với trường `file`
  - request hợp lệ trả `status=ok` cùng metadata tối thiểu
  - request không hợp lệ trả message lỗi tiếng Việt
  - file được lưu tạm theo convention riêng cho import, không trộn với upload domain khác
- **Rủi ro nếu gộp quá to:** nếu lát này ôm luôn parse workbook, nó sẽ trượt sang `Task 1.2`.

### Slice 1.1-F - Nối UI upload với backend và trạng thái đang tải

- **Loại:** `AFK`
- **Blocked by:** `Slice 1.1-E`
- **Mục tiêu:** nối form Inertia thật, có state `uploading`, disable nút, spinner và chống submit lặp.
- **Kết quả demo:** bấm upload thấy nút bị khóa, có loading rõ ràng, xong thì trả về trạng thái thành công hoặc thất bại.
- **Acceptance criteria:**
  - khi upload đang chạy, input và submit bị disable
  - có loading text/spinner bằng tiếng Việt
  - không thể click liên tục tạo nhiều request trùng
- **Rủi ro nếu gộp quá to:** nếu để state machine tới cuối mới làm, các lát trước có thể đúng chức năng nhưng UX sai.

### Slice 1.1-G - Hiển thị receipt thành PreviewShell tối thiểu

- **Loại:** `AFK`
- **Blocked by:** `Slice 1.1-F`
- **Mục tiêu:** biến phản hồi upload thành preview shell đúng tinh thần vertical slice, nhưng chưa giả vờ parse dữ liệu 4 sheet.
- **Kết quả demo:** sau upload thành công, user thấy một khung preview với tên file, thời điểm upload, trạng thái và ghi chú sẵn sàng cho `Task 1.2`.
- **Acceptance criteria:**
  - preview shell render từ dữ liệu backend thật
  - empty state và success state đều có nội dung tiếng Việt
  - UI không hiển thị giả dữ liệu parse sheet
- **Rủi ro nếu gộp quá to:** nếu cố render preview nghiệp vụ ở đây, code sẽ nói dối về phạm vi `Task 1.1`.

### Slice 1.1-H - Chuẩn hóa lỗi và retry path cho upload

- **Loại:** `AFK`
- **Blocked by:** `Slice 1.1-G`
- **Mục tiêu:** đảm bảo luồng thất bại vẫn demo được: lỗi request, lỗi validation, upload lại sau lỗi.
- **Kết quả demo:** cố tình upload file sai hoặc backend trả lỗi, UI hiển thị lỗi tiếng Việt và cho phép thử lại ngay.
- **Acceptance criteria:**
  - state `failed` hiển thị rõ ràng, không kẹt loading
  - người dùng có thể chọn file khác và upload lại
  - flash message, inline error hoặc toast dùng tiếng Việt thống nhất
  - nếu dùng redirect flow, shared props phải có `flash`; nếu không, xử lý lỗi trực tiếp trong response upload
- **Rủi ro nếu gộp quá to:** nếu không tách riêng lát lỗi, thường chỉ demo được happy path.

### Slice 1.1-I - Khóa copy và contract theo đúng nghiệp vụ 4 sheet

- **Loại:** `AFK`
- **Blocked by:** `Slice 1.1-H`
- **Mục tiêu:** chốt wording trên UI để phản ánh đúng nguồn gốc dữ liệu: import chỉ gồm `Tổng hợp`, `Khoán NPP`, `Cám cá`, `Key Account`; chưa parse ở bước này.
- **Kết quả demo:** page import hiển thị đúng ngữ cảnh nghiệp vụ, không nhắc sai sang `Template Mail`, không khiến user hiểu nhầm hệ thống đã đọc dữ liệu sheet.
- **Acceptance criteria:**
  - toàn bộ copy trên page là tiếng Việt và đúng domain
  - có mô tả đúng 4 sheet import hợp lệ
  - không có wording ám chỉ parse hoặc merge đã hoàn thành
- **Rủi ro nếu gộp quá to:** nếu để copy tới cuối mới dọn, việc review sẽ diễn ra trên một UI có ngữ nghĩa sai.

### Slice 1.1-J - Chốt smoke test end-to-end cho Task 1.1

- **Loại:** `AFK`
- **Blocked by:** `Slice 1.1-I`
- **Mục tiêu:** có test đủ tin cậy để khóa `Task 1.1`: route, permission, upload boundary, success receipt, failure path.
- **Kết quả demo:** chạy test và thao tác tay đều xác nhận được lát cắt hoàn chỉnh từ UI tới backend upload receipt.
- **Acceptance criteria:**
  - có feature test backend cho `GET /imports` và `POST /imports/upload`
  - có ít nhất một luồng kiểm chứng UI hoặc e2e smoke cho chọn file và upload thành công
  - pass được happy path và một failure path cơ bản
- **Rủi ro nếu gộp quá to:** nếu test chỉ được thêm sau cùng, các lát trước dễ drift khỏi thiết kế.

## 5. Thứ tự triển khai đề xuất

Thực hiện đúng thứ tự sau:

1. `Slice 1.1-A`
2. `Slice 1.1-B`
3. `Slice 1.1-C`
4. `Slice 1.1-D`
5. `Slice 1.1-E`
6. `Slice 1.1-F`
7. `Slice 1.1-G`
8. `Slice 1.1-H`
9. `Slice 1.1-I`
10. `Slice 1.1-J`

Lý do:

- từ trái sang phải luôn có thứ để demo;
- không có lát nào phụ thuộc vào parser workbook;
- mỗi lát cắt có thể merge độc lập nếu cần.

## 6. Mapping ra file/code dự kiến

- `Slice 1.1-A`
  - `routes/web.php`
  - `resources/js/Pages/Imports/Index.vue`
- `Slice 1.1-B`
  - `routes/web.php`
  - conditional UI theo permission nếu cần
- `Slice 1.1-C`
  - `resources/js/Components/imports/ImportUploadCard.vue`
  - `resources/js/Pages/Imports/Index.vue`
- `Slice 1.1-D`
  - `resources/js/Pages/Imports/Index.vue`
  - `resources/js/Components/imports/ImportUploadCard.vue`
- `Slice 1.1-E`
  - `app/Http/Controllers/ImportUploadController.php`
  - `routes/web.php`
  - request validation class nếu cần
  - storage path tạm cho import
- `Slice 1.1-F`
  - `resources/js/Pages/Imports/Index.vue`
- `Slice 1.1-G`
  - `resources/js/Components/imports/ImportPreviewShell.vue`
  - `resources/js/Pages/Imports/Index.vue`
- `Slice 1.1-H`
  - `resources/js/Pages/Imports/Index.vue`
  - backend error response formatting
- `Slice 1.1-I`
  - copy UI ở page import
  - message text
- `Slice 1.1-J`
  - `tests/Feature/...`
  - e2e smoke nếu được thêm trong bước này

## 7. Definition of Done cho Task lớn 1.1

`Task 1.1` được coi là xong khi:

- `/imports` không còn là placeholder page;
- user có quyền thao tác upload được file `.xlsx` thật;
- backend nhận request thật và trả `upload receipt`;
- route `/imports` đã đi qua controller thật, không còn là closure placeholder;
- UI hiển thị đủ trạng thái `idle / file_selected / uploading / uploaded / failed`;
- toàn bộ text nhìn thấy là tiếng Việt;
- không có phần nào giả vờ parse workbook hoặc preview dữ liệu 4 sheet;
- có test đủ để bảo vệ happy path và failure path cơ bản.

## 8. Bước tiếp theo sau Task 1.1

Sau khi xong file này, chuyển sang `Task 1.2 (Workbook Boundary)`:

- thay `upload receipt` bằng metadata workbook;
- vẫn giữ nguyên page import, upload form, preview shell và permission boundary;
- không phải đập lại layout hay luồng thao tác của người dùng.
