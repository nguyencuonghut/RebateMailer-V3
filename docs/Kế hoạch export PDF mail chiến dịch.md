# Kế hoạch export PDF mail chiến dịch

**Task lớn:** Export PDF hàng loạt cho mail campaign  
**Mục tiêu:** bổ sung khả năng export các mail của một chiến dịch ra `.pdf`, kể cả khi campaign chưa gửi thật, có chèn chữ ký người đại diện theo loại khách (`Khách thường` / `Key Account`), trong đó chữ ký được thiết kế như `1 composite part` của canvas ở màn `Thiết kế mẫu email`  
**Ngày cập nhật:** 21/05/2026

## 1. Nguồn gốc kế hoạch

- Nguồn tham chiếu gốc:
  - [SRS.md](/run/media/cuong/DATA/02_Project/205_RebateFlow/RebateMailer-V3/docs/SRS.md:1)
  - [Mô tả phần mềm.txt](/run/media/cuong/DATA/02_Project/205_RebateFlow/RebateMailer-V3/docs/Mô%20tả%20phần%20mềm.txt:1)
- Nguồn đối chiếu:
  - [Kế hoạch Triển khai (Implementat.md](/run/media/cuong/DATA/02_Project/205_RebateFlow/RebateMailer-V3/docs/Kế%20hoạch%20Triển%20khai%20%28Implementat.md:1)
  - [Task 1.1 - Kế hoạch lát cắt thực thi.md](/run/media/cuong/DATA/02_Project/205_RebateFlow/RebateMailer-V3/docs/Task%201.1%20-%20K%E1%BA%BF%20ho%E1%BA%A1ch%20l%C3%A1t%20c%E1%BA%AFt%20th%E1%BB%B1c%20thi.md:1)
  - [Task 1.2 - Kế hoạch lát cắt thực thi.md](/run/media/cuong/DATA/02_Project/205_RebateFlow/RebateMailer-V3/docs/Task%201.2%20-%20K%E1%BA%BF%20ho%E1%BA%A1ch%20l%C3%A1t%20c%E1%BA%AFt%20th%E1%BB%B1c%20thi.md:1)
- Current code liên quan:
  - [routes/web.php](/run/media/cuong/DATA/02_Project/205_RebateFlow/RebateMailer-V3/routes/web.php:105)
  - [MailCampaignPageService.php](/run/media/cuong/DATA/02_Project/205_RebateFlow/RebateMailer-V3/app/Services/Mail/MailCampaignPageService.php:1)
  - [BuildMailCampaignRecipientPreviewService.php](/run/media/cuong/DATA/02_Project/205_RebateFlow/RebateMailer-V3/app/Services/Mail/BuildMailCampaignRecipientPreviewService.php:1)
  - [BuildMailCampaignRecipientEmailHtmlService.php](/run/media/cuong/DATA/02_Project/205_RebateFlow/RebateMailer-V3/app/Services/Mail/BuildMailCampaignRecipientEmailHtmlService.php:1)
  - [DispatchMailCampaignRecipientJob.php](/run/media/cuong/DATA/02_Project/205_RebateFlow/RebateMailer-V3/app/Jobs/DispatchMailCampaignRecipientJob.php:1)
  - [campaign-recipient-preview.blade.php](/run/media/cuong/DATA/02_Project/205_RebateFlow/RebateMailer-V3/resources/views/mail/campaign-recipient-preview.blade.php:1)
  - [MailCampaignAggregatedDataExportController.php](/run/media/cuong/DATA/02_Project/205_RebateFlow/RebateMailer-V3/app/Http/Controllers/MailCampaignAggregatedDataExportController.php:1)
  - [MailCampaignFailedRecipientsExportController.php](/run/media/cuong/DATA/02_Project/205_RebateFlow/RebateMailer-V3/app/Http/Controllers/MailCampaignFailedRecipientsExportController.php:1)
  - [Mail/Index.vue](/run/media/cuong/DATA/02_Project/205_RebateFlow/RebateMailer-V3/resources/js/Pages/Mail/Index.vue:1)
- Skill đã dùng:
  - `.ai/rules/engineering/zoom-out/SKILL.md`
  - `.ai/rules/engineering/to-issues/SKILL.md`

## 2. Nguyên tắc chia lát cắt

- Mỗi lát cắt phải đi xuyên suốt qua đủ lớp cần thiết: `DB -> service -> route/controller -> UI -> test`.
- Với recipient chưa gửi, export PDF được phép dùng đúng luồng preview/render hiện tại của campaign.
- Với recipient đã gửi, không thêm export PDF bằng cách re-render tạm bợ từ template hiện tại nếu mục tiêu là “mail đã gửi”.
- Chữ ký đại diện là một phần của nội dung mail, nên không được hardcode trong Blade view hay tách thành cấu hình rời khỏi canvas.
- `Khách thường` và `Key Account` là hai loại khách đã có trong domain; feature mới phải tái sử dụng phân loại này, không tạo taxonomy mới.
- Vì campaign có thể gửi số lượng lớn mail, export PDF hàng loạt không nên chạy đồng bộ trong request web.

## 3. Hiện trạng codebase

- Module mail đã có đủ xương sống:
  - page điều phối campaign;
  - danh sách recipient theo campaign;
  - preview email HTML theo từng recipient;
  - gửi thật qua queue;
  - export CSV danh sách lỗi;
  - export CSV dữ liệu aggregate.
- `mail_campaign_recipients` hiện đã có `customer_type`, `delivery_status`, `sent_at`, nhưng chưa lưu snapshot của `subject/html` đã gửi. Nếu template bị sửa sau khi gửi, hệ thống hiện không tái dựng chính xác “mail đã gửi”.
- Ở chiều ngược lại, module mail đã có đủ seam preview để render nội dung mail cho recipient chưa gửi từ campaign + canvas + aggregate data hiện tại.
- HTML email đang được dựng tập trung qua:
  - `BuildMailCampaignRecipientPreviewService`
  - `BuildMailCampaignRecipientEmailHtmlService`
  - view `mail/campaign-recipient-preview.blade.php`
- Route `/mail` hiện đã có pattern thêm export action theo campaign, nên feature mới có thể bám theo seam sẵn có.
- `composer.json` hiện chưa có thư viện PDF chuyên dụng.
- Template composition hiện mới có các `TemplatePartType` cho `subject`, `greeting`, và các bảng dữ liệu; chưa có composite part cho chữ ký đại diện.

## 3.1. Quyết định kỹ thuật cho feature này

- **Nguồn sự thật cho export PDF:**
  - recipient `sent`: ưu tiên snapshot gắn với `mail_campaign_recipient`;
  - recipient chưa `sent`: dùng luồng preview/render hiện tại của campaign.
- **Điều kiện cho phép export:** campaign có ít nhất 1 recipient render preview thành công, không bắt buộc đã `sent`.
- **Dạng file:** phase đầu tạo `1 file PDF / 1 campaign export`, trong đó mỗi recipient chiếm `1 page` hoặc `1 block có page-break`.
- **Kiểu xử lý:** request web chỉ tạo yêu cầu export; queue job sinh PDF và lưu file vào storage để tải về sau.
- **Yêu cầu font/Unicode:** file PDF xuất ra phải hiển thị tiếng Việt có dấu ổn định 100%, không chấp nhận fallback font làm lỗi glyph.
- **Yêu cầu pagination bảng:** nếu nội dung table vượt quá 1 page, phần còn lại của table phải tiếp tục ở page sau, không bị cắt mất các dòng cuối; page mới vẫn phải giữ margin trên/dưới nhất quán như các page khác.
- **Yêu cầu performance:** thiết kế phải chịu được campaign có hàng ngàn recipient mà không timeout request web, không load toàn bộ payload vào memory một cách ngây thơ, và có khả năng quan sát tiến độ export.
- **Chữ ký đại diện:** được mô hình hóa thành `1 composite part` của canvas template, chứa đồng thời 2 block con:
  - block cho `Khách thường`
  - block cho `Key Account`
- **Chiến dịch dùng chữ ký nào:** campaign không có màn cấu hình chữ ký riêng; campaign dùng chữ ký đi kèm canvas/version đã chọn ở bước tạo campaign.
- **Khóa lịch sử:** khi mail gửi thành công, hệ thống snapshot luôn:
  - subject đã gửi;
  - html body đã gửi;
  - metadata chữ ký áp dụng tại thời điểm đó.
- **PDF adapter đề xuất:** dùng `barryvdh/laravel-dompdf` hoặc adapter Dompdf tương đương vì HTML email hiện chủ yếu là inline styles + table layout, không cần JS runtime.

## 4. Phạm vi và ngoài phạm vi

### 4.1. Trong phạm vi

- tạo yêu cầu export PDF từ màn campaign, kể cả trước khi gửi thật;
- có nút export PDF cho từng recipient ngay tại danh sách recipient, đặt cạnh nút preview;
- cấu hình chữ ký trong màn `Thiết kế mẫu email` như một composite part của canvas;
- upload ảnh chữ ký;
- snapshot subject/body/signature tại thời điểm gửi mail thành công;
- render PDF hàng loạt cho các recipient preview được;
- tải file PDF sau khi job hoàn tất;
- hiển thị trạng thái `đang tạo / hoàn tất / lỗi`.
- đảm bảo PDF hiển thị tiếng Việt có dấu đầy đủ;
- đảm bảo bảng dài qua nhiều page không bị mất nội dung cuối và không vỡ margin bottom.

### 4.2. Ngoài phạm vi

- thay đổi nội dung mail gửi thật để chèn chữ ký vào email outbound;
- hỗ trợ nhiều chữ ký động ngoài 2 block con chuẩn trong cùng composite part;
- chỉnh sửa text cố định `Đại diện công ty`;
- export recipient không render preview được;
- chia nhỏ PDF thành `.zip` nhiều file;
- ký số PDF hoặc bảo vệ file bằng mật khẩu.

## 5. Danh sách lát cắt

### Slice PDF-A - Khóa snapshot của mail đã gửi

- **Loại:** `AFK`
- **Blocked by:** Không có
- **Mục tiêu:** tạo nền dữ liệu để export recipient đã gửi về sau không bị drift khi template thay đổi, đồng thời không cản trở flow export trước khi gửi.
- **Kết quả demo:** sau khi một recipient được gửi thành công, DB có đủ `subject/html` đã gửi để dùng lại mà không cần render lại từ template hiện thời.
- **Acceptance criteria:**
  - `mail_campaign_recipients` có thêm cột snapshot phù hợp, ví dụ:
    - `sent_subject_snapshot`
    - `sent_html_snapshot`
    - `sent_signature_snapshot`
    - `snapshot_version` hoặc cờ tương đương nếu cần
  - `DispatchMailCampaignRecipientJob` ghi snapshot khi gửi thành công
  - test xác nhận recipient `sent` lưu đúng snapshot
- **Rủi ro nếu gộp quá to:** nếu để tới bước export mới nghĩ về snapshot, file PDF của recipient đã gửi sẽ không còn phản ánh “mail đã gửi”.

### Slice PDF-B - Thêm composite part chữ ký vào canvas template

- **Loại:** `AFK`
- **Blocked by:** `Slice PDF-A`
- **Mục tiêu:** mở rộng module `Thiết kế mẫu email` để canvas có thêm `1 composite part` chữ ký, trong đó người dùng cấu hình được cả block `Khách thường` và block `Key Account`.
- **Kết quả demo:** trong màn template builder, người dùng chỉnh được text/ảnh/chức vụ/tên cho 2 block chữ ký, lưu thành công, preview được cấu trúc part ngay trên canvas.
- **Acceptance criteria:**
  - `TemplatePartType` có thêm part mới, ví dụ `representative-signature`
  - part mới có `kind` phù hợp với payload composite, không nhầm với `text` hay `table`
  - `template_parts` seed thêm part chữ ký trong catalog composition
  - structure payload của part chứa đủ 2 block:
    - `normalCustomer`
    - `keyAccountCustomer`
  - mỗi block có đủ 4 phần:
    - text `Đại diện công ty`
    - ảnh chữ ký
    - chức vụ
    - tên người đại diện
  - UI builder hỗ trợ upload ảnh, chỉnh text và preview part
- **Rủi ro nếu gộp quá to:** nếu không đưa vào canvas mà làm cấu hình rời, preview template và export PDF sẽ dễ drift.

### Slice PDF-C - Resolve và snapshot chữ ký từ composite part khi gửi thành công

- **Loại:** `AFK`
- **Blocked by:** `Slice PDF-B`
- **Mục tiêu:** khóa đúng block chữ ký của canvas đang gắn với campaign, theo `customer_type` của recipient, tại thời điểm gửi thật.
- **Kết quả demo:** 2 recipient thuộc 2 loại khách trong cùng campaign hoặc khác campaign có snapshot chữ ký khác nhau đúng theo canvas/version đã chọn.
- **Acceptance criteria:**
  - hệ thống resolve block chữ ký từ composite part của `mail_template_canvas` gắn với campaign
  - `Khách thường` lấy block `normalCustomer`, `Key Account` lấy block `keyAccountCustomer`
  - khi gửi thành công, snapshot chữ ký được lưu cùng recipient
  - nếu composite part thiếu block cho loại khách tương ứng, luồng gửi hoặc export báo lỗi rõ ràng
  - có test cho cả `Khách thường` và `Key Account`
- **Rủi ro nếu gộp quá to:** nếu export mới resolve chữ ký từ canvas hiện tại thay vì snapshot, file export cũ sẽ đổi theo lần sửa template sau này.

### Slice PDF-D - Tách seam HTML dùng chung giữa email preview và PDF export

- **Loại:** `AFK`
- **Blocked by:** `Slice PDF-C`
- **Mục tiêu:** tránh copy-paste HTML email sang một view PDF riêng rồi drift dần về sau, đồng thời cho phép export cả với recipient chưa gửi bằng đúng luồng preview hiện tại.
- **Kết quả demo:** email preview hiện tại vẫn chạy, đồng thời có thêm renderer PDF dùng chung phần body mail và render thêm composite part chữ ký đúng theo recipient; recipient chưa gửi nhưng preview được thì cũng export được.
- **Acceptance criteria:**
  - trích phần thân mail dùng chung ra partial hoặc service renderer có locality tốt hơn
  - `BuildMailCampaignRecipientEmailHtmlService` tiếp tục render đúng email hiện tại
  - có thêm renderer cho PDF export dùng chung nội dung body mail
  - renderer hiểu composite part chữ ký và chọn đúng block theo `customer_type`
  - renderer hỗ trợ 2 mode:
    - mode `snapshot` cho recipient đã gửi;
    - mode `preview/current-render` cho recipient chưa gửi
  - chữ ký luôn nằm `bên phải, dưới cùng` trong trang PDF
  - renderer PDF có contract rõ cho table pagination:
    - bảng dài được phép nối sang page sau;
    - không được mất các dòng cuối của table;
    - margin bottom của page chứa phần cuối table vẫn nhất quán với các page khác
- **Rủi ro nếu gộp quá to:** nếu giữ 2 bản HTML tách rời, chỉ cần thay template một nơi là preview và PDF sẽ lệch nhau.

### Slice PDF-E - Tạo yêu cầu export PDF theo campaign và theo dõi trạng thái

- **Loại:** `AFK`
- **Blocked by:** `Slice PDF-D`
- **Mục tiêu:** từ màn campaign, người dùng có thể bấm tạo export PDF và thấy trạng thái tiến trình mà không cần chờ campaign gửi xong.
- **Kết quả demo:** ở `/mail`, campaign có ít nhất 1 recipient preview được sẽ hiện nút `Export PDF`; bấm xong hệ thống tạo record export ở trạng thái `queued`.
- **Acceptance criteria:**
  - có bảng/record theo dõi export, ví dụ `mail_campaign_exports`
  - có route/controller tạo export request
  - UI campaign hiển thị action export mới cạnh các nút export CSV hiện có
  - nếu campaign không có recipient preview được, action bị disable hoặc báo rõ lý do
  - có hiển thị trạng thái `queued / processing / completed / failed`
  - trong bảng recipient có thêm nút `Export PDF` cho từng dòng, đặt cạnh nút `Preview`
  - nút export từng dòng chỉ enable khi recipient đó preview/render được
- **Rủi ro nếu gộp quá to:** nếu request web vừa tạo vừa render PDF ngay, campaign lớn sẽ dễ timeout.

### Slice PDF-F - Sinh PDF hàng loạt bất đồng bộ và lưu file tải về

- **Loại:** `AFK`
- **Blocked by:** `Slice PDF-E`
- **Mục tiêu:** queue job lấy toàn bộ recipient exportable, dựng nội dung PDF theo đúng mode dữ liệu của từng recipient, ghép thành 1 file campaign, lưu storage.
- **Kết quả demo:** một campaign có nhiều recipient được export thành công thành 1 file `.pdf`, mỗi khách một trang, có chữ ký đúng theo loại khách, kể cả khi campaign chưa gửi thật.
- **Acceptance criteria:**
  - có job riêng để generate export
  - ngoài export theo campaign, có endpoint/service export PDF cho `1 recipient`
  - chỉ lấy recipient exportable:
    - recipient `sent` có snapshot hợp lệ; hoặc
    - recipient chưa `sent` nhưng preview/render thành công
  - dùng snapshot subject/html/signature để dựng PDF cho recipient đã gửi
  - dùng preview/current-render để dựng PDF cho recipient chưa gửi
  - tên file có `campaign name + timestamp`
  - file được lưu vào storage path tách biệt, ví dụ `mail-exports/pdf`
  - có endpoint download file sau khi hoàn tất
  - export từng recipient có thể trả file trực tiếp nếu payload nhỏ, không cần queue nếu không có lý do kỹ thuật bắt buộc
  - adapter PDF được cấu hình với font Unicode hỗ trợ tiếng Việt có dấu 100%
  - job export xử lý batch lớn theo hướng tiết kiệm memory:
    - không render toàn bộ recipient theo kiểu eager load vô hạn vào RAM;
    - có chunk/streaming strategy hoặc chiến lược tương đương;
    - có số liệu progress để UI theo dõi được
  - với table dài quá 1 page:
    - phần nội dung còn lại phải tiếp tục ở page sau;
    - không bị mất các dòng `Tổng cộng` / `Bằng chữ` / summary rows;
    - page sau vẫn giữ margin bottom như page chuẩn
- **Rủi ro nếu gộp quá to:** nếu không tách job và storage contract rõ, feature sẽ khó recover khi file lớn hoặc job fail giữa chừng.

### Slice PDF-G - Chuẩn hóa lỗi, lịch sử tải và kiểm thử

- **Loại:** `AFK`
- **Blocked by:** `Slice PDF-F`
- **Mục tiêu:** khóa failure path và tạo đủ test để feature này đứng vững khi thay template hoặc đổi chữ ký về sau.
- **Kết quả demo:** thiếu composite part chữ ký, thiếu block cho một loại khách, preview lỗi, snapshot lỗi, PDF adapter lỗi hoặc campaign không có recipient exportable đều trả thông báo tiếng Việt và không để UI kẹt trạng thái.
- **Acceptance criteria:**
  - có feature test cho:
    - route tạo export theo campaign;
    - route download file export theo campaign;
    - route export PDF từng recipient
  - có unit/service test cho resolver composite part chữ ký theo `customer_type`
  - có test job cho case:
    - campaign hợp lệ;
    - campaign không có recipient exportable;
    - thiếu snapshot hoặc thiếu composite part chữ ký;
    - recipient chưa gửi nhưng preview render lỗi;
    - `Khách thường` và `Key Account` ra đúng block chữ ký khác nhau
  - có test hoặc fixture regression cho:
    - text tiếng Việt có dấu trong subject/body/signature không bị lỗi glyph;
    - table dài hơn 1 page vẫn giữ đủ dòng cuối;
    - page chứa phần cuối table vẫn có margin bottom đúng;
    - export batch lớn vẫn cập nhật progress và hoàn tất trong giới hạn tài nguyên chấp nhận được
  - UI có retry path hoặc tạo lại export mới khi export cũ thất bại
  - UI test hoặc assertion payload cho trạng thái enable/disable của nút export từng dòng cạnh nút preview
- **Rủi ro nếu gộp quá to:** nếu chỉ làm happy path, feature sẽ rất dễ vỡ khi batch lớn hoặc dữ liệu cấu hình thiếu.

## 5.1. Checklist kỹ thuật bắt buộc theo lát cắt

### Checklist cho Unicode tiếng Việt

- `Slice PDF-D`
  - chốt contract HTML/CSS cho PDF chỉ dùng font family có bản Unicode đầy đủ;
  - xác định rõ view/partial nào là nguồn render cuối cho subject/body/signature trong PDF.
- `Slice PDF-F`
  - chọn PDF adapter và font runtime cụ thể;
  - cấu hình mặc định để mọi text tiếng Việt có dấu render ổn định, không phụ thuộc fallback ngẫu nhiên của hệ điều hành;
  - có fixture chứa đủ chữ thường, chữ hoa, dấu thanh, ký tự như `Đ/đ`, `ư`, `ơ`, `ă`, `â`, `ê`, `ô`.
- `Slice PDF-G`
  - có regression test hoặc golden fixture cho chuỗi tiếng Việt có dấu ở subject/body/signature;
  - nếu engine PDF có nguy cơ lỗi glyph, phải có tiêu chí fail rõ ràng trước khi ship.

### Checklist cho performance batch lớn

- `Slice PDF-E`
  - record export phải lưu đủ số liệu để UI theo dõi tiến độ thật:
    - `total_recipients`
    - `exported_recipients`
    - `status`
    - timestamps chính
  - action request web chỉ tạo request, tuyệt đối không render PDF trong request cycle.
  - export từng recipient là luồng riêng, không được phụ thuộc queue bulk nếu không cần.
- `Slice PDF-F`
  - không `eager load` toàn bộ recipient + HTML payload của hàng ngàn khách vào RAM cùng lúc;
  - phải có chiến lược `chunk`, `cursor`, `stream`, hoặc chiến lược tương đương;
  - phải cập nhật progress theo batch để UI không đứng ở trạng thái mù;
  - phải chốt trước giới hạn chấp nhận được:
    - số recipient thử nghiệm;
    - memory ceiling;
    - thời gian xử lý mục tiêu.
- `Slice PDF-G`
  - có test hoặc benchmark nhỏ cho batch lớn;
  - có log/telemetry đủ để biết job đang `queued`, `processing`, đang đi tới recipient thứ bao nhiêu, và fail ở đâu.

### Checklist cho table vượt quá 1 page

- `Slice PDF-D`
  - HTML renderer phải tách rõ:
    - khối nội dung thường;
    - khối table;
    - khối signature;
  - không dùng layout wrapper cản khả năng paginate tự nhiên của table trừ khi đã chứng minh được engine PDF xử lý đúng.
- `Slice PDF-F`
  - phải chốt quy tắc pagination cho table:
    - phần còn lại của table được tiếp tục ở page sau;
    - không làm mất các dòng cuối;
    - các dòng tổng/số tiền/bằng chữ phải được ưu tiên giữ nguyên vẹn;
    - page có phần cuối table vẫn giữ margin bottom chuẩn;
  - tránh CSS có nguy cơ cắt nội dung như `overflow:hidden` trên wrapper bảng nếu engine PDF xử lý không ổn;
  - nếu engine không đảm bảo tự paginate đúng, phải có plan B:
    - tách summary rows thành khối riêng;
    - hoặc render table theo lát cắt nhỏ có kiểm soát.
- `Slice PDF-G`
  - có fixture regression cho ít nhất 1 table dài hơn 1 page;
  - verify cụ thể các case:
    - dòng cuối thường không bị cắt;
    - dòng `Tổng cộng` vẫn còn;
    - dòng `Bằng chữ` / summary rows vẫn còn;
    - margin bottom của page cuối không bị vỡ.

### Checklist cho block chữ ký

- `Slice PDF-D`
  - chốt rõ layout rule:
    - chữ ký luôn canh phải;
    - xuất hiện sau nội dung mail;
    - ưu tiên nằm dưới cùng của phần nội dung recipient;
    - không được tách 4 phần của block chữ ký thành nhiều page.
- `Slice PDF-F`
  - nếu layout chữ ký cạnh tranh không gian với table dài, ưu tiên bảo toàn dữ liệu table trước, sau đó mới tối ưu vị trí chữ ký;
  - không được vì giữ chữ ký ở đáy trang mà làm mất dòng cuối của table.
- `Slice PDF-G`
  - có regression case cho page gần đầy + chữ ký dài/ảnh lớn;
  - xác nhận chữ ký không tự đứng một mình trên page mới nếu vẫn còn cách bố trí hợp lệ khác.

### Checklist cho export từng dòng recipient

- `Slice PDF-E`
  - xác định rõ vị trí nút trên UI: cùng cụm thao tác với `Preview`, không nằm tách khu vực khác;
  - nút chỉ xuất hiện hoặc chỉ enable khi recipient đó export được.
- `Slice PDF-F`
  - có service/endpoint riêng cho export `1 recipient`;
  - phải dùng đúng cùng renderer với export campaign để không lệch nội dung;
  - với recipient `sent` dùng snapshot, với recipient chưa `sent` dùng current render.
- `Slice PDF-G`
  - có test cho 2 case:
    - recipient export được;
    - recipient không export được vì preview lỗi hoặc thiếu dữ liệu.

## 6. Thứ tự triển khai đề xuất

Thực hiện đúng thứ tự sau:

1. `Slice PDF-A`
2. `Slice PDF-B`
3. `Slice PDF-C`
4. `Slice PDF-D`
5. `Slice PDF-E`
6. `Slice PDF-F`
7. `Slice PDF-G`

Lý do:

- phải khóa snapshot trước khi bàn tới “mail đã gửi”;
- phải có composite part chữ ký trong canvas trước khi snapshot chữ ký;
- phải tách renderer dùng chung trước khi đổ thêm PDF adapter và trước khi hỗ trợ export campaign chưa gửi;
- phải tạo request/export record trước khi chạy queue job bulk.
- phải chốt font Unicode và chiến lược paginate table trước khi tối ưu sâu phần layout chữ ký.

## 7. Mapping ra file/code dự kiến

- `Slice PDF-A`
  - `database/migrations/*_add_sent_snapshots_to_mail_campaign_recipients.php`
  - `app/Jobs/DispatchMailCampaignRecipientJob.php`
  - `tests/Feature/MailCampaignRecipientSendTest.php`
- `Slice PDF-B`
  - `app/Support/Templates/TemplatePartType.php`
  - `database/migrations/2026_05_07_000100_create_template_composition_tables.php`
  - `app/Services/Templates/TemplatePartCatalogService.php`
  - `app/Services/Templates/TemplateSectionCatalogService.php`
  - `resources/js/Pages/Templates/Index.vue`
  - `resources/js/Components/templates/TemplateBuilderCanvas.vue`
- `Slice PDF-C`
  - `app/Services/Templates/ResolveTemplateCanvasSectionService.php`
  - `app/Services/Mail/BuildRepresentativeSignatureSnapshotService.php`
  - `app/Services/Mail/ResolveRepresentativeSignatureFromCanvasService.php`
  - `app/Jobs/DispatchMailCampaignRecipientJob.php`
- `Slice PDF-D`
  - `resources/views/mail/campaign-recipient-preview.blade.php`
  - `resources/views/mail/partials/*`
  - `resources/views/mail/campaign-recipient-export-pdf.blade.php`
  - `app/Services/Mail/BuildMailCampaignRecipientEmailHtmlService.php`
  - `app/Services/Mail/BuildMailCampaignRecipientPdfHtmlService.php`
- `Slice PDF-E`
  - `database/migrations/*_create_mail_campaign_exports_table.php`
  - `app/Models/MailCampaignExport.php`
  - `app/Http/Controllers/MailCampaignPdfExportStoreController.php`
  - `app/Services/Mail/MailCampaignPageService.php`
  - `resources/js/Pages/Mail/Index.vue`
  - `routes/web.php`
- `Slice PDF-F`
  - `app/Jobs/GenerateMailCampaignPdfExportJob.php`
  - `app/Services/Mail/GenerateMailCampaignPdfExportService.php`
  - `app/Http/Controllers/MailCampaignPdfExportDownloadController.php`
  - `app/Http/Controllers/MailCampaignRecipientPdfExportController.php`
  - `app/Services/Mail/BuildMailCampaignRecipientPdfService.php`
  - `config/filesystems.php` nếu cần thêm disk/path convention
- `Slice PDF-G`
  - `tests/Feature/*`
  - `tests/Unit/*`
  - các message/state ở `resources/js/Pages/Mail/Index.vue`

## 8. Definition of Done cho feature này

Feature được coi là xong khi:

- người dùng vào màn campaign và tạo được yêu cầu export PDF ngay khi campaign có recipient preview được, không cần chờ `sent`;
- người dùng có thể export PDF cho từng recipient ngay trong bảng recipient, bằng nút đặt cạnh nút preview;
- hệ thống export được cả 2 nhóm recipient:
  - recipient đã gửi thành công;
  - recipient chưa gửi nhưng render preview thành công;
- mỗi recipient `sent` trong PDF giữ đúng body mail đã gửi theo snapshot, không phụ thuộc template hiện tại;
- mỗi recipient chưa `sent` trong PDF dùng đúng nội dung render hiện tại của campaign/template;
- block chữ ký luôn xuất hiện ở bên phải, dưới cùng, và khác nhau đúng giữa `Khách thường` và `Key Account`;
- file PDF hiển thị tiếng Việt có dấu đầy đủ, không lỗi font/glyph;
- khi table dài vượt 1 page, nội dung tiếp tục ở page sau và không làm mất các dòng cuối hoặc summary rows;
- page có phần cuối của table vẫn giữ margin bottom nhất quán;
- export campaign có hàng ngàn khách vẫn chạy bất đồng bộ ổn định và có progress để theo dõi;
- thông tin chữ ký có thể đổi ngay trong màn `Thiết kế mẫu email` như một phần của canvas mà không cần sửa code;
- file PDF được sinh bất đồng bộ, lưu lại để tải về sau;
- có test bảo vệ happy path và các failure path chính.

## 9. Ghi chú quyết định cần chốt sớm

- Phase đầu nên thống nhất rõ:
  - recipient đã gửi chỉ đảm bảo tính chính xác tuyệt đối nếu đã có snapshot;
  - recipient chưa gửi là bản render hiện tại tại thời điểm export, không phải “bản đã gửi”.
- Với campaign cũ đã gửi trước khi có snapshot, nên chọn một trong hai hướng:
  - chặn export và yêu cầu resend theo flow mới;
  - hoặc cho phép export theo kiểu `best-effort reconstructed`, đồng thời cảnh báo đây không phải bản snapshot tuyệt đối.
- Với batch lớn hàng ngàn khách, nên chốt sớm một chiến lược kỹ thuật:
  - `1 PDF lớn / 1 campaign` nhưng render theo chunk + merge an toàn; hoặc
  - nhiều file con rồi gói `.zip` ở phase sau nếu `1 PDF lớn` không còn ổn về memory/time.
- Với layout table nhiều dòng, nên chốt sớm rằng:
  - ưu tiên tính toàn vẹn dữ liệu và pagination đúng;
  - không hy sinh các dòng cuối hoặc margin page chỉ để giữ layout trang “đẹp”.
- Hướng hiện tại đã chấp nhận:
  - chữ ký là một phần của `template composition`;
  - chữ ký có thể khác nhau giữa các campaign thông qua canvas/version mà campaign chọn;
  - cùng một canvas phải chứa đủ 2 block chữ ký cho `Khách thường` và `Key Account`.
- Nếu sau này xuất hiện nhu cầu:
  - nhiều hơn 2 block chữ ký trong cùng một part;
  - override chữ ký ngay trong màn tạo campaign mà không cần tạo canvas/version mới;
  - hoặc chữ ký cũng phải xuất hiện trong email outbound;
  thì khi đó mới cân nhắc mở rộng composite part hoặc tách thêm seam riêng.
