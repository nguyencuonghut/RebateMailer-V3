# Task 1.2 - Kế hoạch lát cắt thực thi

**Task lớn:** 1.2 (Workbook Boundary)  
**Mục tiêu:** chia nhỏ `Task 1.2` thành các lát cắt mỏng để mở rộng từ `upload receipt` sang `đọc cấu trúc workbook + metadata cơ bản`, có thể test ngay trên UI  
**Ngày cập nhật:** 05/05/2026

## 1. Nguồn gốc kế hoạch

- Nguồn tham chiếu gốc:
  - [Mô tả phần mềm.txt](/run/media/cuong/DATA/02_Project/205_RebateFlow/RebateMailer-V3/docs/Mô%20tả%20ph%E1%BA%A7n%20m%E1%BB%81m.txt:1)
  - workbook mẫu [Data import chuẩn_Final.xlsx](/run/media/cuong/DATA/02_Project/205_RebateFlow/RebateMailer-V3/data/Data%20import%20chu%E1%BA%A9n_Final.xlsx)
- Nguồn đối chiếu:
  - [Kế hoạch Triển khai (Implementat.md](/run/media/cuong/DATA/02_Project/205_RebateFlow/RebateMailer-V3/docs/K%E1%BA%BF%20ho%E1%BA%A1ch%20Tri%E1%BB%83n%20khai%20%28Implementat.md:1)
  - [SRS.md](/run/media/cuong/DATA/02_Project/205_RebateFlow/RebateMailer-V3/docs/SRS.md:1)
  - [Task 1.1 - Thiết kế chi tiết.md](/run/media/cuong/DATA/02_Project/205_RebateFlow/RebateMailer-V3/docs/Task%201.1%20-%20Thi%E1%BA%BFt%20k%E1%BA%BF%20chi%20ti%E1%BA%BFt.md:1)
  - [Task 1.1 - Kế hoạch lát cắt thực thi.md](/run/media/cuong/DATA/02_Project/205_RebateFlow/RebateMailer-V3/docs/Task%201.1%20-%20K%E1%BA%BF%20ho%E1%BA%A1ch%20l%C3%A1t%20c%E1%BA%AFt%20th%E1%BB%B1c%20thi.md:1)
- Skill đã dùng:
  - `.ai/rules/engineering/to-issues/SKILL.md`
  - `.ai/rules/engineering/zoom-out/SKILL.md`

## 2. Nguyên tắc chia lát cắt

- Mỗi lát cắt phải đi xuyên suốt qua đủ lớp cần thiết: `UI -> route -> controller -> service -> response -> preview -> test`.
- Không chia ngang kiểu “cài package xong để đó” hoặc “viết service xong chưa có chỗ demo”.
- Một lát cắt hoàn thành phải nhìn thấy được trên màn `/imports` hoặc verify được qua feature test rõ ràng.
- `Task 1.2` chỉ dừng ở `đọc cấu trúc workbook` và `metadata boundary`.
- Không trượt sang:
  - parse nghiệp vụ riêng của từng sheet;
  - merge theo `Mã số`;
  - validation business kiểu `Key Account` trùng `Khách thường`;
  - preview unified DTO của `Task 1.7+`.

## 3. Hiện trạng codebase

- `Task 1.1` đã xong ở mức:
  - upload file `.xlsx` thật;
  - lưu file tạm ở `imports/tmp`;
  - hiển thị `upload receipt` trên page `/imports`.
- Module import hiện đã có:
  - [ImportPageController.php](/run/media/cuong/DATA/02_Project/205_RebateFlow/RebateMailer-V3/app/Http/Controllers/ImportPageController.php:1)
  - [ImportUploadController.php](/run/media/cuong/DATA/02_Project/205_RebateFlow/RebateMailer-V3/app/Http/Controllers/ImportUploadController.php:1)
  - [StoreImportUploadRequest.php](/run/media/cuong/DATA/02_Project/205_RebateFlow/RebateMailer-V3/app/Http/Requests/Imports/StoreImportUploadRequest.php:1)
  - [ImportPageService.php](/run/media/cuong/DATA/02_Project/205_RebateFlow/RebateMailer-V3/app/Services/Imports/ImportPageService.php:1)
  - [StoreTemporaryImportFileService.php](/run/media/cuong/DATA/02_Project/205_RebateFlow/RebateMailer-V3/app/Services/Imports/StoreTemporaryImportFileService.php:1)
  - [Imports/Index.vue](/run/media/cuong/DATA/02_Project/205_RebateFlow/RebateMailer-V3/resources/js/Pages/Imports/Index.vue:1)
  - [ImportUploadCard.vue](/run/media/cuong/DATA/02_Project/205_RebateFlow/RebateMailer-V3/resources/js/Components/imports/ImportUploadCard.vue:1)
  - [ImportPreviewShell.vue](/run/media/cuong/DATA/02_Project/205_RebateFlow/RebateMailer-V3/resources/js/Components/imports/ImportPreviewShell.vue:1)
  - `resources/js/Services/imports/*`
- Chưa có:
  - endpoint đọc workbook đã upload;
  - service boundary đọc workbook;
  - metadata sheet-level;
  - diagnostics `missing / unexpected / empty sheet`;
  - preview workbook boundary thay cho preview receipt thuần túy.
- `composer.json` hiện chưa có thư viện đọc Excel chuyên dụng. Nếu chọn `Laravel Excel`, việc cài package phải được gộp vào một lát cắt end-to-end, không làm thành lát riêng.

## 3.1. Quyết định kỹ thuật cho Task 1.2

- Giữ nguyên màn `/imports`, không mở thêm page mới.
- Giữ `upload` là bước trước, rồi thêm bước kế tiếp là `đọc cấu trúc workbook` trên file đã tiếp nhận.
- Ưu tiên tạo route riêng như `POST /imports/analyze-workbook` thay vì nhồi logic vào `POST /imports/upload`.
- FE tiếp tục:
  - dùng PrimeVue v4;
  - dùng Toast lấy message từ BE;
  - tách logic TypeScript khỏi `.vue` sang `resources/js/Services/imports/...`.
- BE nếu logic đọc workbook phức tạp phải tách vào `app/Services/Imports/...`.
- `Task 1.2` chỉ cần metadata ở mức boundary, ví dụ:
  - danh sách sheet nhận diện được;
  - sheet nào thiếu, sheet nào thừa;
  - số cột header và tên header line 1;
  - số dòng dữ liệu cơ bản;
  - sheet rỗng hay có dữ liệu.
- Chưa normalize dữ liệu hàng, chưa parse block `CT`, chưa dựng DTO nghiệp vụ cho mail.

## 4. Danh sách lát cắt

### Slice 1.2-A - Mở đường từ receipt sang bước đọc workbook

- **Loại:** `AFK`
- **Blocked by:** `Task 1.1`
- **Mục tiêu:** sau khi upload xong, UI có trạng thái và action rõ ràng để chuyển từ `receipt upload` sang `đọc cấu trúc workbook`.
- **Kết quả demo:** upload thành công xong thấy nút hoặc action `Đọc cấu trúc workbook`, chưa cần đọc thật nhưng luồng thao tác đã rõ.
- **Acceptance criteria:**
  - preview khu import hiển thị được distinction giữa `receipt upload` và `phân tích workbook`
  - action `Đọc cấu trúc workbook` chỉ hiện cho actor có `imports.manage`
  - UI có empty state hoặc pre-analysis state bằng tiếng Việt
- **Rủi ro nếu gộp quá to:** nếu nhảy thẳng vào đọc workbook thật, sẽ khó tách bug điều phối UI khỏi bug parser adapter.

### Slice 1.2-B - Đọc được workbook và trả danh sách sheet

- **Loại:** `AFK`
- **Blocked by:** `Slice 1.2-A`
- **Mục tiêu:** backend nhận handle của file tạm đã upload, mở workbook thành công và trả về danh sách sheet thực tế.
- **Kết quả demo:** người dùng bấm `Đọc cấu trúc workbook`, UI thấy được danh sách sheet đọc ra từ file mẫu.
- **Acceptance criteria:**
  - có endpoint phân tích workbook thật
  - backend mở được file `.xlsx` đã lưu tạm
  - response trả được tên các sheet theo thứ tự xuất hiện trong workbook
  - Toast thành công lấy message từ BE
- **Rủi ro nếu gộp quá to:** nếu ôm luôn header, row count và validation 4 sheet vào lát này, khi fail sẽ không biết lỗi nằm ở adapter mở workbook hay logic business boundary.

### Slice 1.2-C - Khóa boundary đúng 4 sheet import hợp lệ

- **Loại:** `AFK`
- **Blocked by:** `Slice 1.2-B`
- **Mục tiêu:** hệ thống chỉ công nhận 4 sheet import hợp lệ là `Tổng hợp`, `Khoán NPP`, `Cám cá`, `Key Account`.
- **Kết quả demo:** dùng file mẫu thì pass; nếu workbook thiếu sheet hoặc có sheet ngoài contract thì UI thấy cảnh báo rõ.
- **Acceptance criteria:**
  - response trả được `expectedSheets`, `detectedSheets`, `missingSheets`, `unexpectedSheets`
  - `Template Mail` bị coi là sheet ngoài contract import
  - message cảnh báo hiển thị bằng tiếng Việt trên UI
- **Rủi ro nếu gộp quá to:** nếu để rule 4 sheet tới cuối mới làm, các lát sau có thể vô tình build trên input contract sai.

### Slice 1.2-D - Đọc header line 1 cho từng sheet hợp lệ

- **Loại:** `AFK`
- **Blocked by:** `Slice 1.2-C`
- **Mục tiêu:** trích được line 1 của từng sheet import để khóa boundary “tất cả sheet bắt đầu từ dòng 1”.
- **Kết quả demo:** preview workbook hiển thị tên các cột line 1 cho từng sheet.
- **Acceptance criteria:**
  - response có metadata header cho từng sheet hợp lệ
  - UI xem được danh sách cột line 1 của từng sheet
  - chưa parse ý nghĩa nghiệp vụ của các cột, chỉ đọc boundary header
- **Rủi ro nếu gộp quá to:** nếu gộp luôn parse cột động, lát này sẽ trượt sang `Task 1.3+`.

### Slice 1.2-E - Đếm số dòng dữ liệu và nhận diện sheet rỗng

- **Loại:** `AFK`
- **Blocked by:** `Slice 1.2-D`
- **Mục tiêu:** biết sheet nào có dữ liệu, sheet nào rỗng, và số dòng dữ liệu cơ bản sau line header.
- **Kết quả demo:** preview boundary hiển thị row count từng sheet và trạng thái `có dữ liệu / rỗng`.
- **Acceptance criteria:**
  - response có `dataRowCount` cho từng sheet
  - response có cờ kiểu `isEmpty`
  - case hợp lệ như `Khoán NPP` rỗng hoặc `Tổng hợp` không có khi khách chỉ bán `Cám cá` không bị hiểu nhầm là parser fail ở bước này
- **Rủi ro nếu gộp quá to:** nếu thiếu lớp metadata này, đến `Task 1.3+` sẽ rất khó tách lỗi “sheet không có dữ liệu” khỏi lỗi parser.

### Slice 1.2-F - Chuẩn hóa preview workbook boundary trên UI

- **Loại:** `AFK`
- **Blocked by:** `Slice 1.2-E`
- **Mục tiêu:** thay preview receipt thuần túy bằng boundary preview gồm summary workbook và trạng thái theo từng sheet.
- **Kết quả demo:** sau khi phân tích, người dùng thấy được:
  - workbook đã nhận diện thành công;
  - 4 sheet import expected;
  - metadata cơ bản theo từng sheet.
- **Acceptance criteria:**
  - UI có state `pre-analysis / analyzing / analyzed / failed`
  - preview phân biệt rõ `receipt upload` và `kết quả đọc workbook`
  - không hiển thị giả dữ liệu parse nghiệp vụ
- **Rủi ro nếu gộp quá to:** nếu vẫn giữ preview receipt là chính, `Task 1.2` sẽ không tạo ra boundary review đủ dùng cho `Task 1.3+`.

### Slice 1.2-G - Chuẩn hóa lỗi đọc workbook và retry path

- **Loại:** `AFK`
- **Blocked by:** `Slice 1.2-F`
- **Mục tiêu:** xử lý trọn các trường hợp workbook không đọc được, file tạm không còn tồn tại, hoặc contract sheet sai.
- **Kết quả demo:** cố tình phân tích file lỗi hoặc handle lỗi thì UI hiện lỗi tiếng Việt và cho phép thử lại.
- **Acceptance criteria:**
  - lỗi đọc workbook trả message rõ ràng từ BE
  - FE hiển thị toast + inline state thống nhất
  - có thể retry phân tích mà không cần reload toàn trang
- **Rủi ro nếu gộp quá to:** nếu chỉ làm happy path, `Task 1.3+` sẽ đè thêm nhiều failure mode lên một flow chưa vững.

### Slice 1.2-H - Khóa contract boundary cho parser các task sau

- **Loại:** `AFK`
- **Blocked by:** `Slice 1.2-G`
- **Mục tiêu:** chốt response contract để `Task 1.3 -> 1.6` chỉ việc bơm parser từng sheet vào đúng chỗ, không phải đập lại UI hay route.
- **Kết quả demo:** preview workbook đã ổn định shape dữ liệu và có vùng placeholder rõ cho “parser chi tiết theo sheet”.
- **Acceptance criteria:**
  - response shape có thể chứa metadata sheet-level nhất quán
  - FE composable và component không phụ thuộc trực tiếp vào implementation đọc Excel
  - route upload cũ không bị thay nghĩa
- **Rủi ro nếu gộp quá to:** nếu contract còn lỏng, mỗi task parser sau sẽ sửa ngược boundary và làm drift toàn module.

### Slice 1.2-I - Chốt smoke test cho Workbook Boundary

- **Loại:** `AFK`
- **Blocked by:** `Slice 1.2-H`
- **Mục tiêu:** có test đủ tin cậy để khóa `Task 1.2` từ thao tác UI tới response workbook metadata.
- **Kết quả demo:** chạy test xác nhận được luồng:
  - upload file;
  - phân tích workbook;
  - hiện metadata sheet;
  - trả lỗi đúng khi contract workbook sai.
- **Acceptance criteria:**
  - có feature test cho endpoint phân tích workbook
  - có test cho case file mẫu hợp lệ
  - có test cho case thiếu sheet hoặc có sheet ngoài contract
  - có ít nhất một smoke test UI hoặc integration path cho action `Đọc cấu trúc workbook`
- **Rủi ro nếu gộp quá to:** nếu test chỉ thêm sau khi sang `Task 1.3+`, boundary của `1.2` sẽ bị thay đổi mà không có lưới an toàn.

## 5. Thứ tự triển khai đề xuất

Thực hiện đúng thứ tự sau:

1. `Slice 1.2-A`
2. `Slice 1.2-B`
3. `Slice 1.2-C`
4. `Slice 1.2-D`
5. `Slice 1.2-E`
6. `Slice 1.2-F`
7. `Slice 1.2-G`
8. `Slice 1.2-H`
9. `Slice 1.2-I`

Lý do:

- bước đầu chỉ mở đường và tạo action rõ trên UI;
- sau đó mới khóa adapter workbook;
- rồi mới thêm rule 4 sheet, header, row count;
- cuối cùng mới siết contract và test, tránh overbuild quá sớm.

## 6. Mapping ra file/code dự kiến

- `Slice 1.2-A`
  - `resources/js/Pages/Imports/Index.vue`
  - `resources/js/Components/imports/ImportPreviewShell.vue`
  - `resources/js/Services/imports/useImportUploadFlow.ts`
- `Slice 1.2-B`
  - `routes/web.php`
  - `app/Http/Controllers/ImportWorkbookAnalysisController.php`
  - `app/Services/Imports/AnalyzeWorkbookBoundaryService.php`
  - request class nếu cần
- `Slice 1.2-C`
  - `app/Services/Imports/AnalyzeWorkbookBoundaryService.php`
  - response shaping service hoặc DTO mỏng nếu cần
- `Slice 1.2-D`
  - `app/Services/Imports/AnalyzeWorkbookBoundaryService.php`
  - UI preview component cho metadata header
- `Slice 1.2-E`
  - `app/Services/Imports/AnalyzeWorkbookBoundaryService.php`
  - preview component cho row count và trạng thái sheet
- `Slice 1.2-F`
  - `resources/js/Components/imports/ImportPreviewShell.vue`
  - hoặc tách mới `ImportWorkbookBoundaryShell.vue`
  - `resources/js/Services/imports/useImportWorkbookBoundaryFlow.ts`
- `Slice 1.2-G`
  - backend error response formatting
  - FE flow/composable hiển thị toast và retry
- `Slice 1.2-H`
  - service contract
  - composable types
  - page import shell
- `Slice 1.2-I`
  - `tests/Feature/...`
  - smoke/integration test cho luồng import boundary

## 7. Definition of Done cho Task lớn 1.2

`Task 1.2` được coi là xong khi:

- người dùng upload xong có thể bấm hoặc kích hoạt bước `đọc cấu trúc workbook`;
- backend mở được file `.xlsx` tạm và đọc được workbook thật;
- hệ thống kiểm tra đúng 4 sheet import:
  - `Tổng hợp`
  - `Khoán NPP`
  - `Cám cá`
  - `Key Account`
- UI hiển thị được metadata boundary:
  - danh sách sheet
  - thiếu/thừa sheet
  - header line 1
  - số dòng dữ liệu cơ bản
  - sheet rỗng hay có dữ liệu
- toàn bộ text nhìn thấy là tiếng Việt;
- FE toast tiếp tục lấy message từ BE;
- chưa có phần nào parse nội dung nghiệp vụ chi tiết của từng sheet;
- có test bảo vệ được happy path và ít nhất một failure path quan trọng.

## 8. Bước tiếp theo sau Task 1.2

Sau khi xong file này, chuyển sang các task parser riêng:

- `Task 1.3`: parser sheet `Tổng hợp`
- `Task 1.4`: parser sheet `Khoán NPP`
- `Task 1.5`: parser sheet `Cám cá`
- `Task 1.6`: parser sheet `Key Account`

Khi đó:

- không cần đập lại route upload;
- không cần đập lại flow UI import;
- chỉ mở rộng từ workbook boundary sang sheet parser theo từng lát cắt riêng.
