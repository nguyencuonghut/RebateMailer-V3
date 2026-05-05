# Task 1.1 - Thiết kế chi tiết

**Hạng mục:** Slice 1 - Ingestion & Data Aggregator  
**Task:** 1.1 (Backend) - Xây dựng `ExcelService` sử dụng Laravel Excel để đọc theo Chunk  
**Ngày cập nhật:** 05/05/2026  
**Trạng thái:** Draft để triển khai

## 1. Mục tiêu

Thiết kế backend ingestion layer để:

- nhận file Excel `.xlsx` từ người dùng có quyền thao tác import;
- đọc workbook theo chunk để tránh vượt bộ nhớ;
- nhận diện và parse đúng 4 sheet nghiệp vụ:
  - `Tổng hợp`
  - `Khoán NPP`
  - `Cám cá`
  - `Key Account`
- chuẩn hóa dữ liệu thô thành một contract trung gian ổn định cho `Task 1.2` xử lý merge theo `Mã số`;
- trả về metadata parse, warnings, errors và sample rows để phục vụ preview ở `Task 1.4`.

## 2. Nguồn tham chiếu

- Nguồn tham chiếu gốc:
  - [Mô tả phần mềm.txt](/run/media/cuong/DATA/02_Project/205_RebateFlow/RebateMailer-V3/docs/Mô%20tả%20phần%20mềm.txt:1)
  - workbook mẫu `data/Data import chuẩn_Final.xlsx`
    - lưu ý: file mẫu có thêm sheet `Template Mail` chỉ để tham chiếu nội dung template, không thuộc input contract của luồng import
- Nguồn đối chiếu bổ sung:
  - [SRS.md](/run/media/cuong/DATA/02_Project/205_RebateFlow/RebateMailer-V3/docs/SRS.md:1)
  - [Kế hoạch Triển khai (Implementat.md](/run/media/cuong/DATA/02_Project/205_RebateFlow/RebateMailer-V3/docs/Kế hoạch%20Triển%20khai%20%28Implementat.md:1)
  - [.ai/master_prompt.md](/run/media/cuong/DATA/02_Project/205_RebateFlow/RebateMailer-V3/.ai/master_prompt.md:1)
  - skill `.ai/rules/engineering/zoom-out/SKILL.md`

## 3. Phạm vi

### 3.1. Trong phạm vi

- Validate file upload ở mức kỹ thuật: loại file, khả năng đọc workbook, cấu trúc sheet.
- Đọc file bằng `Laravel Excel` theo chunk.
- Chuẩn hóa tên sheet, header và raw rows theo từng sheet strategy.
- Tách block cột lặp `Nội dung CT n / SL / đ/kg / Thành tiền` thành dữ liệu có cấu trúc.
- Tạo payload trung gian để bước sau dùng lại mà không phải parse workbook lần nữa.
- Tạo lỗi và cảnh báo kỹ thuật để UI preview hiển thị.

### 3.2. Ngoài phạm vi

- Merge dữ liệu giữa các sheet theo `Mã số`.
- Áp quy tắc business loại trừ giữa `Key Account` và khách thường ở mức kết luận cuối cùng.
- Loại bỏ các khoản `null` hoặc `0` khỏi cây dữ liệu cuối cùng.
- Validation business hoàn chỉnh như thiếu email, trùng mã giữa `Key Account` và `Tổng hợp`.
- Render DataTable preview ở frontend.
- Lưu template mail, render mail hoặc enqueue gửi mail.

## 4. Actor và phân quyền

- `Admin`: được upload và parse file.
- `Người dùng`: được upload và parse file.
- `Khách`: chỉ xem khu vực import, không được upload/parse.

Quyền đề xuất:

- `GET /imports`: `imports.view`
- `POST /imports/preview`: `imports.manage`

## 5. Quy tắc nghiệp vụ cần giữ ở Task 1.1

- Chỉ chấp nhận file `.xlsx`.
- Chỉ coi 4 sheet `Tổng hợp`, `Khoán NPP`, `Cám cá`, `Key Account` là nguồn dữ liệu nghiệp vụ.
- Input contract của luồng import chỉ gồm 4 sheet nghiệp vụ `Tổng hợp`, `Khoán NPP`, `Cám cá`, `Key Account`.
- Nếu file mẫu có thêm `Template Mail`, sheet này chỉ được xem như tài liệu tham chiếu, không được tính là sheet import.
- Tất cả dữ liệu của 4 sheet nghiệp vụ đều bắt đầu từ line `1`.
- Hệ thống có 2 loại khách hàng:
  - `Khách thường`
  - `Key Account`
- `Key Account` và `Khách thường` là 2 tập khách hàng loại trừ lẫn nhau.
- Nếu một khách xuất hiện trong sheet `Key Account` thì không được xuất hiện trong `Tổng hợp`, `Khoán NPP`, `Cám cá`.
- `Tổng hợp` là sheet nền của `Khách thường`, nhưng có trường hợp một `Khách thường` chỉ bán `cám cá` nên chỉ có dữ liệu ở sheet `Cám cá`.
- `Khoán NPP` là sheet bổ sung, chỉ xuất hiện khi `Khách thường` tham gia chương trình khoán.
- Mỗi sheet dùng schema riêng; không ép dùng một schema phẳng chung cho cả workbook.
- Dữ liệu phải được đọc theo chunk. Mốc khởi tạo đề xuất: `500 rows/chunk`.
- `Task 1.1` chỉ xử lý validation kỹ thuật và structural validation, chưa xử lý validation business sâu.

## 6. Quan sát từ workbook mẫu

### 6.1. Input contract của dữ liệu import

- `Tổng hợp`
- `Khoán NPP`
- `Cám cá`
- `Key Account`

### 6.2. Ghi chú về file mẫu

File mẫu hiện có thêm sheet `Template Mail` để diễn giải subject/body mail. Sheet này không thuộc phạm vi import dữ liệu rebate và không tham gia contract parse của `Task 1.1`.

### 6.3. Đặc điểm schema

- `Tổng hợp`: gồm cột cố định và cột thay đổi theo tháng.
  - cột cố định:
    - `STT`
    - `Tháng`
    - `Mã số`
    - `Mã & tên khách hàng`
    - `Tên khách hàng`
    - `Email`
    - `Địa chỉ`
    - `Thức ăn chăn nuôi`
    - `Tổng sản lượng (gồm cám thủy sản)`
    - `Doanh thu (gồm cám thủy sản)`
    - `Tiền chiết khấu theo Hóa đơn`
    - `Thưởng cam kết tháng`
    - `Chiết khấu cám cá`
    - `Chiết khấu khác ( Không thể hiện trên hóa đơn)`
    - `Tổng cộng`
    - `Bằng chữ`
- `Khoán NPP`: gồm cột cố định và nhóm cột động lặp theo chương trình.
  - cột cố định:
    - `STT`
    - `Tháng`
    - `Mã số`
    - `Mã & tên khách hàng`
    - `Email`
    - `Địa chỉ`
    - `Thức ăn chăn nuôi`
    - `Tổng cộng`
    - `Bằng chữ`
  - nhóm cột động:
    - `Nội dung CT n`
    - `SL`
    - `đ/kg`
    - `Thành tiền`
- `Cám cá`: gồm cột cố định và 2 loại cột thay đổi.
  - cột cố định:
    - `STT`
    - `Tháng`
    - `Mã số`
    - `Mã & tên khách hàng`
    - `Email`
    - `Địa chỉ`
    - `Thức ăn chăn nuôi`
    - `Tổng sản lượng`
    - `Doanh thu`
    - `Tiền chiết khấu theo Hóa đơn`
    - `Chiết khấu khác ( Không thể hiện trên hóa đơn)`
    - `Tổng cộng`
    - `Bằng chữ`
  - nhóm cột thay đổi loại 1:
    - các cột rời rạc, không ràng buộc với nhau, thay đổi theo tháng
  - nhóm cột thay đổi loại 2:
    - các cặp `CTn` + `Thành tiền`
- `Key Account`: gồm cột cố định và 2 loại cột thay đổi.
  - cột cố định:
    - `STT`
    - `Tháng`
    - `Mã số`
    - `Mã & tên khách hàng`
    - `Email`
    - `Địa chỉ`
    - `Thức ăn chăn nuôi`
    - `Tổng sản lượng`
    - `Doanh thu`
    - `Chiết khấu theo hóa đơn`
    - `Tổng cộng`
    - `Bằng chữ`
  - nhóm cột thay đổi loại 1:
    - các cột rời rạc, không ràng buộc với nhau, thay đổi theo tháng
  - nhóm cột thay đổi loại 2:
    - các block `Nội dung CT n / SL / đ/kg / Thành tiền`

### 6.4. Đặc điểm dữ liệu ảnh hưởng thiết kế

- `Mã số` không phải lúc nào cũng thuần số, ví dụ có dạng `90182TS`.
- Có cả ô trống, ô `0`, giá trị âm và text dài.
- Header chứa nhiều khoảng trắng đầu dòng và tên cột dài theo ngữ cảnh nghiệp vụ.
- Nhiều cột động thay đổi theo tháng, nên parser không được hardcode toàn bộ danh sách cột động.
- Với `Khoán NPP` và `Key Account`, nội dung chương trình có thể xuống nhiều dòng trong cùng một ô.
- Với `Cám cá`, cần phân biệt rõ:
  - cột rời rạc mang nghĩa business độc lập
  - cặp cột `CTn / Thành tiền` mang nghĩa chương trình động
- Không thấy nhu cầu tính formula tại bước này; có thể đọc giá trị đã lưu trong workbook.

## 7. Quyết định thiết kế

### 7.1. Boundary

`ExcelService` chỉ phụ trách đọc và chuẩn hóa workbook thành raw structured payload.  
Nó không chứa logic merge business của `Task 1.2`.

### 7.2. Kiểu kiến trúc

Dùng `sheet strategy` thay vì một parser chung:

- `TongHopSheetParser`
- `KhoanNppSheetParser`
- `CamCaSheetParser`
- `KeyAccountSheetParser`

Lý do:

- tên cột và nghĩa cột khác nhau rõ rệt;
- `Khoán NPP` và `Key Account` có nhóm block lặp `Nội dung CT n / SL / đ/kg / Thành tiền`;
- `Cám cá` có 2 loại cột động khác bản chất, không thể ép chung vào parser của `Tổng hợp`;
- test từng sheet độc lập sẽ rõ và rẻ hơn.

### 7.3. Chuẩn hóa header

Mọi header phải được đi qua một lớp `HeaderNormalizer`:

- trim khoảng trắng đầu/cuối;
- gộp nhiều khoảng trắng liên tiếp;
- chuẩn hóa khác biệt nhỏ về chữ hoa/thường;
- map về canonical key nội bộ.

Ví dụ:

- `                    Mã & tên khách hàng` -> `customer_label`
- `Tổng cộng` -> `grand_total`
- `Bằng chữ` -> `amount_in_words`
- `Chiết khấu theo hóa đơn` và `Tiền chiết khấu theo Hóa đơn` cần được map khác nhau theo ngữ cảnh sheet thay vì ép đồng nhất bằng tên hiển thị.

### 7.4. Chuẩn hóa block động

Với `Khoán NPP` và `Key Account`, không giữ dạng field phẳng `ct_1_content`, `ct_1_qty`, ... trong domain payload.  
Thay vào đó normalize thành mảng:

```ts
type ProgramDetail = {
  order: number;
  content: string | null;
  quantity: string | null;
  unitRate: string | null;
  amount: string | null;
};
```

Với `Cám cá`, cần tách 2 loại dynamic field:

```ts
type FishFeedDynamicMetric = {
  key: string;
  label: string;
  amount: string | null;
};

type FishFeedProgramDetail = {
  order: number;
  content: string | null;
  amount: string | null;
};
```

Điều này giúp `Task 1.2`, `Task 1.4` và builder mail không phụ thuộc vào số lượng cột tối đa hay cách đặt tên chương trình theo tháng.

## 8. Thiết kế thành phần

### 8.1. Controller / Action

Đề xuất endpoint backend:

- `POST /imports/preview`

Trách nhiệm:

- nhận `UploadedFile`;
- kiểm tra auth + permission `imports.manage`;
- gọi `ExcelService`;
- trả JSON preview payload hoặc Inertia partial payload;
- không lưu vào database ở `Task 1.1`.

### 8.2. Service chính

Đề xuất interface:

```php
interface ExcelService
{
    public function parseWorkbook(\Illuminate\Http\UploadedFile $file, ImportActor $actor): RawWorkbookPayload;
}
```

### 8.3. Thành phần phụ trợ

- `WorkbookInspector`
  - đọc workbook metadata, liệt kê sheet, xác định sheet hợp lệ.
- `HeaderNormalizer`
  - chuẩn hóa header từng sheet.
- `SheetParserRegistry`
  - map `sheet name -> parser`.
- `BaseSheetParser`
  - logic dùng chung: header row, empty row detection, row sample collection.
- `SheetParseResultFactory`
  - đóng gói rows, warnings, errors, counters.

### 8.4. DTO nội bộ đề xuất

```ts
type RawWorkbookPayload = {
  workbook: WorkbookSummary;
  sheets: ParsedSheet[];
  warnings: ParseWarning[];
  errors: ParseError[];
  canProceed: boolean;
};

type WorkbookSummary = {
  originalFileName: string;
  uploadedAt: string;
  uploadedByUserId: number;
  recognizedSheets: string[];
  missingRequiredSheets: string[];
};

type ParsedSheet = {
  sheetName: 'Tổng hợp' | 'Khoán NPP' | 'Cám cá' | 'Key Account';
  rowCount: number;
  headerMap: Record<string, string>;
  samples: ParsedRow[];
  rows: ParsedRow[];
};

type ParsedRow = {
  rowNumber: number;
  customerCode: string | null;
  customerLabel: string | null;
  customerType: 'regular' | 'key_account';
  email: string | null;
  address: string | null;
  month: string | null;
  brand: string | null;
  metrics: Record<string, string | null>;
  programDetails?: ProgramDetail[];
  fishFeedProgramDetails?: FishFeedProgramDetail[];
  dynamicMetrics?: FishFeedDynamicMetric[];
  raw: Record<string, string | null>;
};
```

Ghi chú:

- `rows` là raw normalized rows cho backend nội bộ.
- `customerType` được gán theo sheet nguồn ngay từ `Task 1.1` để hỗ trợ `Task 1.2` phát hiện xung đột giữa `regular` và `key_account`.
- Khi trả cho frontend preview, có thể dùng payload rút gọn hơn để tránh response quá lớn.
- Nếu cần giữ kết quả parse cho bước kế tiếp, nên cache payload bằng Redis theo `preview_token` ở `Task 1.2` hoặc `1.4`, không chốt ở tài liệu này.

## 9. Luồng xử lý đề xuất

### 9.1. Luồng chính

1. Người dùng upload file tại màn import.
2. Backend validate MIME, extension và size.
3. `WorkbookInspector` đọc workbook metadata.
4. Hệ thống xác định:
   - sheet hợp lệ
   - sheet thiếu
5. Với từng sheet hợp lệ, parser tương ứng đọc theo chunk.
6. Header row được normalize và map sang canonical keys.
7. Mỗi row được chuyển thành `ParsedRow`, đồng thời gắn `customerType` theo sheet nguồn:
   - `regular` cho `Tổng hợp`, `Khoán NPP`, `Cám cá`
   - `key_account` cho `Key Account`
8. Hệ thống thu thập:
   - `rowCount`
   - `samples`
   - `warnings`
   - `errors`
9. Trả `RawWorkbookPayload`.

### 9.2. Luồng lỗi

- File không mở được: dừng ngay, trả `fatal error`.
- Thiếu toàn bộ 4 sheet nghiệp vụ: dừng ngay, trả `fatal error`.
- Thiếu một phần sheet: chưa dừng, trả `warning` hoặc `error` tùy sheet.
- Header thiếu cột bắt buộc: đánh `error` cho sheet tương ứng.
- Row lỗi đơn lẻ: giữ parse tiếp, đánh `warning` cho row đó.

## 10. Chính sách lỗi và cảnh báo

### 10.1. Fatal error

- File không phải `.xlsx`
- Workbook hỏng hoặc không thể đọc
- Không tìm thấy parser cho sheet nghiệp vụ được yêu cầu
- Thiếu cột định danh tối thiểu của một sheet:
  - `Mã số`
  - `Email`
  - `Địa chỉ`
  - hoặc cột bắt buộc tương đương theo schema sheet

### 10.2. Error

- Tên sheet nghiệp vụ bị sai
- Header không map được hoàn chỉnh
- Dòng có format email rõ ràng sai
- Cột cố định bắt buộc của sheet không hiện diện ở dòng header số `1`

### 10.3. Warning

- Dòng trống xen kẽ
- Ô tiền/sản lượng để trống
- Giá trị âm cần review
- `Mã số` và email có dấu hiệu không đồng nhất
- Có sheet ngoài phạm vi 4 sheet import nghiệp vụ
- Một cột động hoặc nhãn chương trình mới xuất hiện nhưng vẫn parse được theo rule động

## 11. Thiết kế API đề xuất

### 11.1. Request

`POST /imports/preview`

`multipart/form-data`

- `file`: required, `.xlsx`

### 11.2. Response thành công

```json
{
  "status": "ok",
  "previewToken": "imp_prev_01J...",
  "workbook": {
    "recognizedSheets": ["Tổng hợp", "Khoán NPP", "Cám cá", "Key Account"],
    "missingRequiredSheets": []
  },
  "sheets": [
    {
      "sheetName": "Tổng hợp",
      "rowCount": 1250,
      "sampleCount": 5,
      "headers": ["month", "customer_code", "email", "grand_total"]
    }
  ],
  "warnings": [],
  "errors": [],
  "canProceed": true
}
```

### 11.3. Response lỗi

```json
{
  "status": "error",
  "message": "Không thể đọc file Excel hoặc cấu trúc sheet không hợp lệ.",
  "errors": [
    {
      "code": "excel.invalid_structure",
      "sheet": "Khoán NPP",
      "detail": "Thiếu cột bắt buộc: Mã số"
    }
  ]
}
```

## 12. Cấu trúc thư mục đề xuất

```text
app/
├── Actions/Imports/
│   └── PreviewImportAction.php
├── Data/Imports/
│   ├── RawWorkbookPayload.php
│   ├── ParsedSheet.php
│   ├── ParsedRow.php
│   ├── ParseWarning.php
│   └── ParseError.php
├── Services/Imports/
│   ├── ExcelService.php
│   ├── WorkbookInspector.php
│   ├── HeaderNormalizer.php
│   └── SheetParserRegistry.php
└── Services/Imports/Parsers/
    ├── TongHopSheetParser.php
    ├── KhoanNppSheetParser.php
    ├── CamCaSheetParser.php
    └── KeyAccountSheetParser.php
```

## 13. Hiệu năng và vận hành

- Chunk size đề xuất mặc định: `500`.
- Sample preview mỗi sheet: `5-10` dòng đầu hợp lệ.
- Không trả toàn bộ `rows` về frontend nếu file lớn; chỉ trả sample + summary.
- Nếu frontend cần thao tác tiếp trên payload đầy đủ, nên lưu vào cache Redis bằng `previewToken`.
- Ghi log các chỉ số:
  - thời gian parse
  - số sheet hợp lệ
  - số dòng mỗi sheet
  - số warnings/errors

## 14. TDD và kiểm thử

### 14.1. Unit tests

- `HeaderNormalizerTest`
- `WorkbookInspectorTest`
- `TongHopSheetParserTest`
- `KhoanNppSheetParserTest`
- `CamCaSheetParserTest`
- `KeyAccountSheetParserTest`

### 14.2. Feature tests

- upload file hợp lệ và nhận preview summary
- file không đúng định dạng bị chặn
- workbook thiếu sheet bắt buộc
- header lệch chuẩn nhưng vẫn normalize được
- file chỉ được parse theo đúng 4 sheet import nghiệp vụ

### 14.3. Test data cần có

- file chuẩn đủ 4 sheet
- file thiếu `Key Account`
- file có `Mã số` dạng text
- file có giá trị âm
- file có thêm sheet lạ

## 15. Tiêu chí hoàn thành của Task 1.1

- Có service đọc workbook `.xlsx` theo chunk.
- Parse được 4 sheet nghiệp vụ bằng strategy riêng.
- Chỉ parse đúng 4 sheet import nghiệp vụ.
- Nhận diện được 2 loại khách hàng theo sheet nguồn:
  - `regular`
  - `key_account`
- Tách đúng các nhóm cột động:
  - `Khoán NPP`: block `Nội dung CT n / SL / đ/kg / Thành tiền`
  - `Cám cá`: cột rời rạc + cặp `CTn / Thành tiền`
  - `Key Account`: cột rời rạc + block `Nội dung CT n / SL / đ/kg / Thành tiền`
- Trả được payload trung gian ổn định, có summary, warnings, errors và sample rows.
- Có test bao phủ cho các case kỹ thuật chính.
- Không trộn logic merge/validation business của `Task 1.2` và `Task 1.3`.

## 16. Rủi ro còn mở

- `Mô tả phần mềm.txt` và workbook mẫu là nguồn gốc để chốt nghiệp vụ import; nếu có khác biệt với `SRS.md` thì ưu tiên theo 2 nguồn này.
- `SRS.md` hiện bị cắt ở phần DTO, nên canonical payload trên đây là đề xuất thiết kế để triển khai, chưa phải đặc tả DTO đã đóng băng.
- Cần quyết định ở bước triển khai:
  - có cache full payload bằng Redis ngay ở `Task 1.1` hay để sang `Task 1.4`;
  - sheet nào là “bắt buộc tuyệt đối” và sheet nào là “tùy chọn có cảnh báo”;
  - danh sách cột bắt buộc tối thiểu cho từng sheet.

## 17. Kết luận

`Task 1.1` nên được triển khai như một ingestion boundary rõ ràng: đọc workbook lớn an toàn, chuẩn hóa dữ liệu thô theo từng sheet, và phát ra contract trung gian sạch cho các task sau.  
Nếu boundary này được giữ chặt, `Task 1.2` chỉ còn tập trung vào merge theo `Mã số`, còn `Task 1.4` chỉ việc dựng preview trên payload đã ổn định.
