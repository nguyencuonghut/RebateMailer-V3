# Kế hoạch hỗ trợ nhiều email từ cột Email import

**Task lớn:** Hỗ trợ 1 hoặc nhiều email trong cột `Email` của file import  
**Mục tiêu:** cho phép cột `Email` ở 4 sheet import chứa `1 hoặc nhiều địa chỉ email`, phân tách bằng dấu chấm phẩy `;`, và khi gửi mail thì toàn bộ email trong danh sách đều nhận được mail  
**Ngày cập nhật:** 08/06/2026

## 1. Nguồn gốc kế hoạch

- Nguồn tham chiếu gốc:
  - [SRS.md](/run/media/cuong/DATA/02_Project/205_RebateFlow/RebateMailer-V3/docs/SRS.md:1)
  - [Mô tả phần mềm.txt](/run/media/cuong/DATA/02_Project/205_RebateFlow/RebateMailer-V3/docs/Mô%20tả%20ph%E1%BA%A7n%20m%E1%BB%81m.txt:1)
- Nguồn đối chiếu:
  - [Refactoring import module.md](/run/media/cuong/DATA/02_Project/205_RebateFlow/RebateMailer-V3/docs/Refactoring%20import%20module.md:1)
  - [Task 1.1 - Kế hoạch lát cắt thực thi.md](/run/media/cuong/DATA/02_Project/205_RebateFlow/RebateMailer-V3/docs/Task%201.1%20-%20K%E1%BA%BF%20ho%E1%BA%A1ch%20l%C3%A1t%20c%E1%BA%AFt%20th%E1%BB%B1c%20thi.md:1)
  - [Task 1.2 - Kế hoạch lát cắt thực thi.md](/run/media/cuong/DATA/02_Project/205_RebateFlow/RebateMailer-V3/docs/Task%201.2%20-%20K%E1%BA%BF%20ho%E1%BA%A1ch%20l%C3%A1t%20c%E1%BA%AFt%20th%E1%BB%B1c%20thi.md:1)
- Current code liên quan:
  - [ParseTongHopPreviewService.php](/run/media/cuong/DATA/02_Project/205_RebateFlow/RebateMailer-V3/app/Services/Imports/ParseTongHopPreviewService.php:1)
  - [ParseKhoanNppPreviewService.php](/run/media/cuong/DATA/02_Project/205_RebateFlow/RebateMailer-V3/app/Services/Imports/ParseKhoanNppPreviewService.php:1)
  - [ParseCamCaPreviewService.php](/run/media/cuong/DATA/02_Project/205_RebateFlow/RebateMailer-V3/app/Services/Imports/ParseCamCaPreviewService.php:1)
  - [ParseKeyAccountPreviewService.php](/run/media/cuong/DATA/02_Project/205_RebateFlow/RebateMailer-V3/app/Services/Imports/ParseKeyAccountPreviewService.php:1)
  - [AggregateImportPreviewService.php](/run/media/cuong/DATA/02_Project/205_RebateFlow/RebateMailer-V3/app/Services/Imports/AggregateImportPreviewService.php:1)
  - [CreateMailCampaignService.php](/run/media/cuong/DATA/02_Project/205_RebateFlow/RebateMailer-V3/app/Services/Mail/CreateMailCampaignService.php:1)
  - [DispatchMailCampaignRecipientJob.php](/run/media/cuong/DATA/02_Project/205_RebateFlow/RebateMailer-V3/app/Jobs/DispatchMailCampaignRecipientJob.php:1)
  - [MailCampaignRecipient.php](/run/media/cuong/DATA/02_Project/205_RebateFlow/RebateMailer-V3/app/Models/MailCampaignRecipient.php:1)
  - [2026_05_09_000000_create_mail_campaign_tables.php](/run/media/cuong/DATA/02_Project/205_RebateFlow/RebateMailer-V3/database/migrations/2026_05_09_000000_create_mail_campaign_tables.php:1)
- Skill đã dùng:
  - `.ai/master_prompt.md`
  - `.ai/rules/engineering/zoom-out/SKILL.md`
  - `.ai/rules/engineering/to-issues/SKILL.md`

## 2. Nguyên tắc chia lát cắt

- Mỗi lát cắt phải đi xuyên suốt qua đủ lớp cần thiết: `parse -> aggregate -> persistence -> mail campaign -> dispatch -> UI -> test`.
- Không chấp nhận giải pháp “nhét cả chuỗi `a@x.com;b@y.com` vào `Mail::to()` rồi hy vọng mailer tự hiểu”.
- Không làm feature này theo hướng “1 row customer nhưng gửi nhiều người” nếu điều đó làm mất tracking `pending / sent / failed / retry` theo từng địa chỉ email.
- Dữ liệu import phải được normalize sớm:
  - bỏ khoảng trắng thừa;
  - bỏ phần tử rỗng;
  - loại duplicate trong cùng một ô nếu cần;
  - validate từng email riêng lẻ.
- Toàn bộ các mail trong danh sách phải nhận được mail khi bấm gửi.
- Flow preview email theo customer vẫn giữ nguyên ngữ nghĩa hiện tại: preview nội dung mail, không cần nhân bản preview theo từng email nếu nội dung không đổi theo người nhận.

## 3. Hiện trạng codebase

- Cả 4 parser sheet hiện chỉ đọc cột `Email` như một string đơn `email`.
- Aggregate preview đang validate `Email` bằng `FILTER_VALIDATE_EMAIL` trên đúng string đó, nên chuỗi có `;` hiện bị đánh lỗi định dạng.
- Khi tạo campaign, hệ thống chỉ materialize `1 row` trong `mail_campaign_recipients` cho mỗi aggregated record, với `recipient_email` kiểu string đơn.
- Khi gửi thật, job dispatch gọi `Mail::to((string) $recipient->recipient_email)->send(...)`, tức là một recipient row hiện chỉ tương ứng với một email đích.
- Tracking, retry, export failed recipients, PDF export theo recipient hiện đều đang bám vào mô hình `1 recipient row = 1 địa chỉ đích`.

## 3.1. Kết luận kiến trúc cần chốt

- Hệ thống **chưa hỗ trợ** nhiều email cách nhau dấu `;`.
- Hướng triển khai hợp lý nhất là:
  - vẫn cho phép cột `Email` của sheet chứa chuỗi `1 hoặc nhiều email`;
  - normalize chuỗi này thành `email list`;
  - khi tạo campaign, **fan-out thành nhiều `mail_campaign_recipients` rows**, mỗi email một row.

Lý do chọn `fan-out` thay vì lưu mảng email trong một row:

- hợp với tracking hiện tại:
  - mỗi địa chỉ có trạng thái `pending / queued / sent / failed` riêng;
- hợp với retry:
  - retry từng địa chỉ lỗi mà không gửi lại cả nhóm;
- hợp với export failed recipients:
  - biết chính xác email nào lỗi;
- giảm rủi ro khi mailer hoặc SMTP phản hồi lỗi cục bộ cho một người nhận.

## 3.2. Quyết định kỹ thuật cho feature này

- **Định dạng được hỗ trợ:** cột `Email` chứa:
  - một email đơn, ví dụ `a@example.com`
  - hoặc nhiều email phân tách bằng `;`, ví dụ `a@example.com; b@example.com; c@example.com`
- **Normalize rule:**
  - trim khoảng trắng mỗi phần tử;
  - bỏ phần tử rỗng;
  - có thể loại duplicate trong cùng một ô email bằng so sánh case-insensitive;
  - giữ thứ tự xuất hiện ban đầu của danh sách sau khi normalize.
- **Nguồn dữ liệu aggregate:**
  - giữ backward-compatible trường `email` dạng string để không làm vỡ quá nhiều chỗ một lúc;
  - bổ sung thêm trường chuẩn mới, ví dụ `emails`, là `list<string>`.
- **Validation rule:**
  - một ô `Email` hợp lệ nếu sau normalize có ít nhất 1 email hợp lệ;
  - nếu một phần tử trong danh sách sai định dạng, record phải bị đánh lỗi rõ email nào sai.
- **Materialization campaign:**
  - từ `1 aggregated record` có thể sinh ra `n recipient rows`;
  - mỗi row ứng với đúng `1 recipient_email`.
- **Dispatch:**
  - giữ nguyên contract `Mail::to(one_email)` ở job gửi thật;
  - chỉ thay đổi số lượng recipient rows được queue ra.
- **Preview UI:**
  - màn import và mail page nên hiển thị được danh sách email nguồn;
  - nhưng preview body mail không cần nhân lên theo từng email vì nội dung mail giống nhau.

## 4. Phạm vi và ngoài phạm vi

### 4.1. Trong phạm vi

- parser của 4 sheet hiểu được cột `Email` chứa nhiều địa chỉ phân tách bằng `;`;
- aggregate preview validate được danh sách email;
- persistence aggregate lưu được danh sách email normalize;
- tạo campaign sinh nhiều `mail_campaign_recipients` rows từ một customer nếu customer có nhiều email;
- khi ấn gửi, toàn bộ email trong danh sách được queue và gửi;
- UI hiển thị rõ trường hợp một customer có nhiều địa chỉ nhận;
- failed/export/tracking giữ được tính chính xác theo từng email.

### 4.2. Ngoài phạm vi

- hỗ trợ phân tách bằng dấu phẩy `,` hoặc newline ngoài dấu `;`;
- hỗ trợ `cc`, `bcc`;
- hỗ trợ nội dung mail khác nhau giữa các email của cùng một customer;
- gộp nhiều email thành một SMTP envelope duy nhất với nhiều recipient trong một lần gửi;
- thay đổi business key aggregate khỏi `Mã số`.

## 5. Danh sách lát cắt

### Slice EMAIL-A - Normalize email list ở parser 4 sheet

- **Loại:** `AFK`
- **Blocked by:** Không có
- **Mục tiêu:** parser của `Tổng hợp`, `Khoán NPP`, `Cám cá`, `Key Account` hiểu được cột `Email` có một hoặc nhiều địa chỉ phân tách bằng `;`.
- **Kết quả demo:** preview parse của từng sheet trả ra cả:
  - `email` string gốc đã trim hợp lý;
  - `emails` là danh sách normalize.
- **Acceptance criteria:**
  - cả 4 parser xử lý nhất quán cùng một rule normalize;
  - chuỗi `a@x.com; b@y.com ; ; c@z.com` thành `['a@x.com', 'b@y.com', 'c@z.com']`
  - record không còn phụ thuộc duy nhất vào trường `email` string đơn
  - có unit/feature test cho 4 parser với case một email và nhiều email
- **Rủi ro nếu gộp quá to:** nếu đẩy normalize xuống tận lúc gửi mail, validation và preview import sẽ sai ngữ nghĩa.

### Slice EMAIL-B - Nâng validation aggregate từ `email string` sang `email list`

- **Loại:** `AFK`
- **Blocked by:** `Slice EMAIL-A`
- **Mục tiêu:** aggregate preview không còn coi `a@x.com;b@y.com` là sai chỉ vì đó không phải một email đơn.
- **Kết quả demo:** màn aggregate preview chấp nhận danh sách nhiều email hợp lệ, và báo lỗi chính xác khi một phần tử sai định dạng.
- **Acceptance criteria:**
  - validation đọc từ `emails`
  - mỗi phần tử trong list được validate riêng
  - record lỗi phải chỉ ra phần tử nào sai
  - empty list sau normalize bị coi là lỗi `Email: không được để trống`
- **Rủi ro nếu gộp quá to:** nếu vẫn giữ validator cũ, toàn bộ feature sẽ hỏng ngay từ bước review dữ liệu import.

### Slice EMAIL-C - Chuẩn hóa shape aggregate payload và persistence

- **Loại:** `AFK`
- **Blocked by:** `Slice EMAIL-B`
- **Mục tiêu:** aggregated payload lưu được danh sách email như nguồn sự thật cho các bước sau.
- **Kết quả demo:** `import_batch_aggregated_records.aggregated_payload` của record hợp lệ có thêm trường `emails`.
- **Acceptance criteria:**
  - payload chuẩn có `emails: list<string>`
  - các nhánh `tongHop`, `khoanNpp`, `camCa`, `keyAccount` cũng lưu được `emails` nếu cần
  - backward-compatible ở mức cần thiết với các chỗ đang đọc `email`
- **Rủi ro nếu gộp quá to:** nếu persistence chưa đổi mà campaign creation đã fan-out, logic sẽ bị chắp vá ở nhiều lớp.

### Slice EMAIL-D - Fan-out recipient rows khi tạo campaign

- **Loại:** `AFK`
- **Blocked by:** `Slice EMAIL-C`
- **Mục tiêu:** một customer có nhiều email sẽ sinh nhiều `mail_campaign_recipients` rows khi tạo campaign.
- **Kết quả demo:** một aggregated record có 3 email tạo ra 3 recipient rows, cùng customer nhưng khác `recipient_email`.
- **Acceptance criteria:**
  - `CreateMailCampaignService` đọc `emails` list thay vì `email` đơn
  - mỗi email sinh một row riêng
  - không tạo row trùng nếu cùng một email lặp lại trong cùng customer
  - test database khẳng định đúng số row được materialize
- **Rủi ro nếu gộp quá to:** nếu vẫn giữ 1 row rồi gửi nhiều người trong job, tracking/retry sẽ lệch với toàn bộ domain mail hiện tại.

### Slice EMAIL-E - Giữ nguyên dispatch contract, mở rộng số lượng job thực tế

- **Loại:** `AFK`
- **Blocked by:** `Slice EMAIL-D`
- **Mục tiêu:** luồng gửi thật không cần đổi triết lý, chỉ cần queue nhiều recipient rows hơn.
- **Kết quả demo:** bấm gửi campaign có 1 customer / 3 email sẽ queue 3 job gửi, và 3 email đều nhận được mail.
- **Acceptance criteria:**
  - `DispatchMailCampaignRecipientJob` vẫn gửi `one row -> one email`
  - `StartMailCampaignDispatchService` queue đủ mọi recipient rows
  - retry/failure log vẫn chính xác theo từng email
  - test mail fake xác nhận mail được gửi tới đủ từng địa chỉ
- **Rủi ro nếu gộp quá to:** nếu sửa luôn job sang gửi một lúc nhiều recipient, sẽ làm vỡ hành vi retry và log.

### Slice EMAIL-F - Cập nhật UI import/mail để nhìn thấy nhiều email

- **Loại:** `AFK`
- **Blocked by:** `Slice EMAIL-E`
- **Mục tiêu:** người dùng nhìn thấy rõ rằng một customer có thể có nhiều địa chỉ nhận mail.
- **Kết quả demo:** màn import preview và màn mail campaign hiển thị được danh sách email hoặc badge count tương ứng.
- **Acceptance criteria:**
  - import preview hiển thị email list theo format dễ đọc
  - mail page hiển thị nhiều recipient rows cho cùng customer mà không gây hiểu nhầm
  - nếu cần, thêm cột/phần mô tả để giải thích vì sao một customer xuất hiện nhiều dòng recipient
- **Rủi ro nếu gộp quá to:** nếu chỉ sửa backend, người dùng sẽ tưởng hệ thống đang tạo duplicate lỗi.

### Slice EMAIL-G - Regression cho failed export, retry và các flow phụ

- **Loại:** `AFK`
- **Blocked by:** `Slice EMAIL-F`
- **Mục tiêu:** khóa các flow phụ vốn đang giả định `1 customer = 1 recipient row`.
- **Kết quả demo:** failed recipients export, aggregated export, retry recipient, mail page counters vẫn đúng khi customer có nhiều email.
- **Acceptance criteria:**
  - rà lại các export/report có `recipient_email`
  - mail page summary/count không bị sai khi một customer có nhiều email
  - regression test cho:
    - tạo campaign từ record nhiều email;
    - queue đủ jobs;
    - retry một email thất bại không ảnh hưởng email còn lại
- **Rủi ro nếu gộp quá to:** các flow phụ sẽ âm thầm lệch dù happy path gửi mail nhìn có vẻ đúng.

## 6. Thứ tự triển khai đề xuất

Thực hiện đúng thứ tự sau:

1. `Slice EMAIL-A`
2. `Slice EMAIL-B`
3. `Slice EMAIL-C`
4. `Slice EMAIL-D`
5. `Slice EMAIL-E`
6. `Slice EMAIL-F`
7. `Slice EMAIL-G`

Lý do:

- phải normalize và validate đúng trước;
- rồi mới persistence;
- rồi mới fan-out campaign recipients;
- cuối cùng mới khóa UI và các regression phụ.

## 7. Mapping ra file/code dự kiến

- `Slice EMAIL-A`
  - `app/Services/Imports/ParseTongHopPreviewService.php`
  - `app/Services/Imports/ParseKhoanNppPreviewService.php`
  - `app/Services/Imports/ParseCamCaPreviewService.php`
  - `app/Services/Imports/ParseKeyAccountPreviewService.php`
  - có thể thêm helper dùng chung, ví dụ `NormalizeImportedEmailListService.php`
- `Slice EMAIL-B`
  - `app/Services/Imports/AggregateImportPreviewService.php`
  - `tests/Feature/ImportsAggregatePreviewTest.php`
- `Slice EMAIL-C`
  - `app/Services/Imports/PersistImportBatchAggregatedRecordsService.php`
  - các test persistence aggregate hiện có
- `Slice EMAIL-D`
  - `app/Services/Mail/CreateMailCampaignService.php`
  - `tests/Feature/MailCampaignStoreTest.php`
- `Slice EMAIL-E`
  - `app/Services/Mail/StartMailCampaignDispatchService.php`
  - `app/Jobs/DispatchMailCampaignRecipientJob.php`
  - `tests/Feature/MailCampaignDispatchTest.php`
  - `tests/Feature/MailCampaignRecipientSendTest.php`
- `Slice EMAIL-F`
  - `app/Services/Mail/MailCampaignPageService.php`
  - `resources/js/Pages/Mail/Index.vue`
  - import preview components nếu cần
- `Slice EMAIL-G`
  - `app/Http/Controllers/MailCampaignFailedRecipientsExportController.php`
  - `app/Http/Controllers/MailCampaignAggregatedDataExportController.php`
  - `tests/Feature/MailPageTest.php`
  - `tests/Feature/MailCampaignRecipientRetryTest.php`

## 8. Definition of Done

Feature này được coi là xong khi:

- cột `Email` của 4 sheet hỗ trợ:
  - 1 email đơn;
  - hoặc nhiều email ngăn cách bằng `;`
- aggregate preview không còn đánh sai định dạng cho danh sách email hợp lệ
- aggregated payload lưu được danh sách email chuẩn hóa
- tạo campaign từ một customer nhiều email sẽ sinh nhiều recipient rows
- khi bấm gửi, toàn bộ email trong danh sách đều được queue và gửi
- retry/failure tracking vẫn đúng theo từng địa chỉ
- UI không gây hiểu nhầm khi một customer xuất hiện nhiều recipient rows
- có regression test bảo vệ parser, aggregate, materialization và dispatch

## 9. Ghi chú quyết định cần chốt sớm

- Có loại duplicate email trong cùng một ô hay không:
  - khuyến nghị: có, theo compare case-insensitive
- Có cho phép dấu `;` ở cuối chuỗi hay không:
  - khuyến nghị: có, nhưng normalize bỏ phần tử rỗng
- Có hỗ trợ song song cả `;` và `,` hay không:
  - khuyến nghị giai đoạn này: **không**, chỉ `;` để contract rõ ràng
- Có cần hiển thị email list trong aggregated CSV export hay không:
  - khuyến nghị: có, ở dạng join bằng `; `

## 10. Bước tiếp theo hợp lý

Sau tài liệu này, bước hợp lý nhất là làm ngay `Slice EMAIL-A` và `Slice EMAIL-B` trong cùng một nhịp TDD nhỏ, vì hai lát này khóa contract parse + validation trước khi đụng tới campaign creation.
