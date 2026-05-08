# Giai đoạn 2 - Kế hoạch lát cắt thực thi

**Giai đoạn lớn:** 2 (Slice 2 - Visual Template Builder)  
**Mục tiêu:** chia nhỏ `Giai đoạn 2` thành các lát cắt rất mỏng, có thể làm tuần tự và test được ngay trên UI  
**Ngày cập nhật:** 08/05/2026

## 1. Nguồn gốc kế hoạch

- Nguồn tham chiếu gốc:
  - [Mô tả phần mềm.txt](/run/media/cuong/DATA/02_Project/205_RebateFlow/RebateMailer-V3/docs/Mô%20tả%20ph%E1%BA%A7n%20m%E1%BB%81m.txt:1)
  - [SRS.md](/run/media/cuong/DATA/02_Project/205_RebateFlow/RebateMailer-V3/docs/SRS.md:1)
- Nguồn đối chiếu:
  - [Kế hoạch Triển khai (Implementat.md](/run/media/cuong/DATA/02_Project/205_RebateFlow/RebateMailer-V3/docs/K%E1%BA%BF%20ho%E1%BA%A1ch%20Tri%E1%BB%83n%20khai%20%28Implementat.md:1)
  - current code ở [routes/web.php](/run/media/cuong/DATA/02_Project/205_RebateFlow/RebateMailer-V3/routes/web.php:1), [ModulePage.vue](/run/media/cuong/DATA/02_Project/205_RebateFlow/RebateMailer-V3/resources/js/Pages/ModulePage.vue:1), [package.json](/run/media/cuong/DATA/02_Project/205_RebateFlow/RebateMailer-V3/package.json:1)
- Skill đã dùng:
  - `.ai/master_prompt.md`
  - `.ai/rules/engineering/to-issues/SKILL.md`
- Agent đã dùng:
  - 1 agent rà requirement và business rule của builder
  - 1 agent rà hiện trạng codebase và dependency thực tế

## 2. Nguyên tắc chia lát cắt

- Mỗi lát cắt phải đi xuyên suốt qua đủ lớp cần thiết: `permission -> route -> page -> service -> persistence -> test`.
- Không chia ngang kiểu “làm xong toàn bộ drag-drop rồi mới làm save/load”.
- Một lát cắt hoàn thành phải demo được ngay hoặc verify được bằng test.
- Chỉ dùng các biến, quy tắc template và ràng buộc đã được xác nhận từ tài liệu gốc.
- `Giai đoạn 2` phải bám thực tế repo hiện tại: mở đường bằng `Templates/Index` thật trước, rồi mới đi dần tới drag-drop builder.
- Các lát cắt của builder phải bám đúng 6 phần nghiệp vụ thực tế của email:
  - `Subject`
  - `Lời chào`
  - `Table Chế độ tháng`
  - `Table Chương trình khoán đặc biệt`
  - `Table Chiết khấu cám cá`
  - `Table Chiết khấu Key Account`

## 3. Hiện trạng codebase

- `/templates` hiện vẫn render placeholder `ModulePage`, chưa có page, controller hay service riêng.
- Permission đã có sẵn:
  - `templates.view`
  - `templates.manage`
- Frontend đã có đủ nền để làm builder theo pattern hiện tại:
  - Inertia + Vue 3 + TypeScript
  - PrimeVue v4
  - composable/service pattern trong `resources/js/Services/...`
  - toast và layout dashboard production
- Chưa có:
  - dependency `vuedraggable`
  - DB schema cho template mail
  - page quản lý template
  - visual builder thực sự
  - engine interpolation cho subject/body
  - preview email từ dữ liệu aggregate

## 3.1. Sự thật nghiệp vụ đã được xác nhận

- Builder cho phép kéo thả.
- Builder cho phép mục cha/con trong table.
- Số thứ tự phải auto nhảy.
- Mặc định:
  - mục cha `bold`
  - mục con `regular`
- Chỉ có `1 template active` tại một thời điểm.
- Template gồm ít nhất:
  - `Subject`
  - `Body`
- Các biến đã được xác nhận trong tài liệu:
  - `{{tháng}}`
  - `{{mã & tên khách hàng}}`
  - `{{địa chỉ}}`
  - `{{thức ăn chăn nuôi}}`
- Cấu trúc template phải được lưu dưới dạng `JSON` vào `Postgres`.
- Sheet mẫu trong workbook là `Template Mail`, không phải `Template Email`.
- Sheet `Template Mail` hiện là ví dụ output email cuối cùng, không phải cấu trúc builder JSON.
- Body mail mẫu hiện được chia rõ thành:
  - `Lời chào`
  - `Chi tiết chiết khấu 'Khách thường'`
  - `Chi tiết chiết khấu 'Key Account'`
- Với `Khách thường`, ví dụ hiện có 3 bảng:
  - `Chế độ tháng`
  - `Chương trình khoán đặc biệt`
  - `Chiết khấu cám cá`
- Với `Key Account`, ví dụ hiện có 1 bảng chi tiết riêng.
- STT trong email mẫu không chỉ là số thường, mà có ít nhất 2 tầng:
  - `I`, `II`
  - `1`, `2`, `3`, ...
- Trong email mẫu có các dòng tổng kết cố định:
  - `Cộng`
  - `Bằng chữ`
- Cột `Nhóm` xuất hiện trong ví dụ bảng `Chế độ tháng` của `Khách thường`, nhưng các dòng ví dụ hiện không có value ở cột này.
- Email mẫu `Key Account` hiện vẫn hiển thị một số dòng có giá trị `0`, nên builder/preview không được mặc định suy diễn rằng mọi dòng `0` đều phải bị ẩn.

## 3.2. Quyết định kỹ thuật cho Giai đoạn 2

- Mở đầu bằng `Templates/Index` thật thay cho placeholder, không nhảy thẳng vào drag-drop canvas.
- Tách logic TypeScript khỏi `.vue` theo cùng pattern đã dùng ở import module: page/component mỏng, logic nằm trong `resources/js/Services/templates/...`.
- Chỉ cài `vuedraggable` khi đã có page template thật, vì dependency này hiện chưa tồn tại trong repo.
- Thiết kế persistence theo 2 lớp:
  - metadata template
  - `structure_json` cho builder
- Không đoán thêm biến mới ngoài các biến đã được xác nhận; nếu cần danh sách biến mở rộng thì phải được suy ra từ dữ liệu aggregate thật ở giai đoạn sau.
- Preview email phải đi từ dữ liệu đã aggregate/persist của Giai đoạn 1, không dựng mock domain mới để “minh họa”.
- Builder phải đủ khả năng biểu diễn các row type khác nhau của email mẫu:
  - section heading
  - data row thường
  - row cha kiểu `I`, `II`
  - row con kiểu `1`, `2`, `3`
  - summary row `Cộng`
  - text row `Bằng chữ`
- Không được lấy dãy STT cụ thể trong sheet mẫu làm rule đánh số của hệ thống, vì ví dụ `Khoán NPP` hiện có STT không liên tục `1, 4, 5, 7`.
- Việc ẩn/hiện dòng theo giá trị `0` phải được xem là rule nghiệp vụ riêng cần chốt sau; chưa được phép cứng hóa trong kế hoạch này vì sheet `Template Mail` còn hiển thị nhiều dòng `0` ở ví dụ `Key Account`.
- Kế hoạch phải tách riêng từng phần template để dễ test và dễ khóa phạm vi:
  - `Subject` là một flow riêng
  - `Lời chào` là một flow riêng
  - mỗi bảng dữ liệu là một flow riêng gắn với đúng sheet nguồn

## 3.3. Đính chính kiến trúc cốt lõi

- Mô hình hiện tại `mail_templates.subject_template + structure_json` là quá monolithic cho bài toán thật.
- Kiến trúc đúng phải phản ánh 2 lớp:
  - `template part`: một bộ phận tái sử dụng được, có nhiều `version`, có `active/inactive`
  - `email template canvas`: một bản phối linh động ghép từ nhiều `template part version`
- Một email template hoàn chỉnh không phải là một blob JSON tự thân, mà là một composition từ **một version bất kỳ** của từng part.
- Ví dụ composition hợp lệ:
  - `subject_v1`
  - `greeting_v1`
  - `tong_hop_table_v1`
  - `khoan_npp_table_v2`
  - `cam_ca_table_v2`
  - `key_account_table_v4`
- Dãy `v1/v2/v4` ở ví dụ trên chỉ để minh họa rằng mỗi part có thể chọn một version khác nhau; không mang ý nghĩa cố định version nào phải đi với version nào.

## 3.4. Hệ quả nghiệp vụ đã chốt

- `Subject`:
  - thay đổi ít
  - thường chỉ có `1 version active`
- `Lời chào`:
  - thay đổi ít
  - có thể đồng thời tồn tại khoảng `2 version active` để chọn ghép vào canvas
- `4 bảng dữ liệu`:
  - thay đổi theo tháng
  - mỗi loại bảng có nhiều `version`
  - tại mỗi thời điểm chỉ có `1 version active` theo từng loại bảng
- Một `email template canvas` phải được phép ghép linh động từ các version đang có, không ép tất cả phần phải cùng chung một version line.

## 3.5. Định hướng refactor BE / DB / FE

### Backend

- Tách rõ aggregate root:
  - `template part`
  - `template part version`
  - `mail template canvas`
  - `mail template canvas part bindings`
- Không dùng 1 action `templates.structure.update` để lưu cả canvas nữa.
- Mỗi phần phải có write path riêng:
  - lưu `subject version`
  - lưu `greeting version`
  - lưu `table version` theo từng loại bảng
- Canvas chỉ lưu quan hệ chọn version nào của từng part.

### Database

- Cần refactor schema hiện tại khỏi mô hình 1 bảng `mail_templates` ôm hết mọi thứ.
- Hướng đúng:
  - `template_parts`
    - định danh part gốc: `subject`, `greeting`, `tong-hop-table`, `khoan-npp-table`, `cam-ca-table`, `key-account-table`
  - `template_part_versions`
    - nội dung/version của từng part
    - với `subject/greeting`: text template
    - với các bảng: `structure_json` của builder
    - có `status`, `version_no`, `effective_month` nếu cần về sau
  - `mail_template_canvases`
    - metadata của một canvas email
  - `mail_template_canvas_parts`
    - binding từ canvas sang version cụ thể của từng part
- Ràng buộc active phải đi theo `part type`, không còn chỉ là “1 template active toàn hệ thống”.

### Frontend

- UI `/templates` phải tách làm 2 mode:
  - quản lý `part versions`
  - quản lý `canvas composition`
- Trong từng tab:
  - `Subject` và `Lời chào` phải có danh sách version riêng + editor riêng
  - mỗi bảng phải có builder riêng + version list riêng
- Canvas không còn là nơi edit trực tiếp toàn bộ dữ liệu gốc của tất cả part, mà là nơi chọn/ghép version.

## 3.6. Trạng thái code hiện tại cần coi là bước đệm

- Những gì đã làm đến hiện tại ở `/templates` vẫn hữu ích như prototype UI/UX:
  - page thật
  - permission
  - builder canvas
  - preview
- Tuy nhiên lớp persistence hiện tại chỉ phù hợp như bước đệm.
- Các slice tiếp theo phải đi theo refactor dần từ monolith sang composition model, không tiếp tục mở rộng `mail_templates.structure_json` như nguồn sự thật cuối cùng.

## 4. Danh sách lát cắt

### Slice 2.0-R1 - Tách domain model `template part` và `canvas`

- **Loại:** `AFK`
- **Blocked by:** Không có
- **Mục tiêu:** chốt lại backend/domain model để các phần template có version riêng, canvas chỉ làm composition.
- **Kết quả demo:** tài liệu + code model/service ban đầu phản ánh rõ:
  - `template part`
  - `template part version`
  - `mail template canvas`
- **Acceptance criteria:**
  - không coi `mail_templates.structure_json` là nguồn sự thật cuối cùng nữa
  - mọi phần chính đều map được sang một `part type`
  - canvas model không chứa business content đầy đủ của tất cả part

### Slice 2.0-R2 - Refactor schema DB sang `part versions + canvas bindings`

- **Loại:** `AFK`
- **Blocked by:** `Slice 2.0-R1`
- **Mục tiêu:** thay persistence monolith bằng schema composition.
- **Kết quả demo:** DB có thể lưu:
  - nhiều version của `subject`
  - nhiều version của `greeting`
  - nhiều version của từng bảng
  - một canvas ghép tới đúng version đã chọn
- **Acceptance criteria:**
  - có migration mới cho:
    - `template_parts`
    - `template_part_versions`
    - `mail_template_canvases`
    - `mail_template_canvas_parts`
  - có strategy migrate dữ liệu prototype hiện tại sang schema mới
  - active state được quản lý đúng theo `part type`

### Slice 2.0-R3 - Refactor API write path theo từng phần

- **Loại:** `AFK`
- **Blocked by:** `Slice 2.0-R2`
- **Mục tiêu:** không còn `saveStructure()` một cục cho toàn canvas.
- **Kết quả demo:** mỗi tab có action save riêng xuống DB.
- **Acceptance criteria:**
  - `subject` save riêng
  - `greeting` save riêng
  - từng bảng save riêng
  - canvas composition save riêng

### Slice 2.0-R4 - Refactor UI `/templates` thành 2 bề mặt: `Part Versions` và `Canvas Composition`

- **Loại:** `AFK`
- **Blocked by:** `Slice 2.0-R3`
- **Mục tiêu:** FE phản ánh đúng bản chất domain mới.
- **Kết quả demo:** người dùng phân biệt rõ:
  - đang sửa một `version` của part
  - hay đang ghép canvas từ các version đã có
- **Acceptance criteria:**
  - UI có list version theo từng part
  - UI có state active/inactive cho từng version
  - UI có canvas composer để chọn version cho từng phần

### Slice 2.0-R5 - Migrate prototype hiện tại sang mô hình mới

- **Loại:** `AFK`
- **Blocked by:** `Slice 2.0-R4`
- **Mục tiêu:** bảo toàn những gì đã dựng ở prototype builder hiện tại.
- **Kết quả demo:** dữ liệu đang có trong `mail_templates` cũ được chuyển sang `part version` + `canvas`.
- **Acceptance criteria:**
  - có migration/command chuyển dữ liệu
  - preview hiện tại vẫn render được từ mô hình mới
  - không mất các section đã thiết kế ở prototype

### Ghi chú thực thi

- Các slice `2.1+` bên dưới được hiểu là các lát cắt UI/prototype đã hoặc đang mở đường.
- Trước khi đi sâu thêm vào builder cho `Khoán NPP`, `Cám cá`, `Key Account`, cần ưu tiên hoàn tất chuỗi refactor `2.0-R1 -> 2.0-R5`.

### Slice 2.1-A - Mở đường vào màn Quản lý template

- **Loại:** `AFK`
- **Blocked by:** Không có
- **Mục tiêu:** thay placeholder `/templates` bằng page thật để quản lý template mail.
- **Kết quả demo:** vào menu `Thiết kế mẫu email`, thấy page thật với tiêu đề, mô tả, trạng thái active template và khung nội dung rỗng.
- **Acceptance criteria:**
  - route `/templates` render `Templates/Index.vue`
  - actor có `templates.view` vào được page
  - UI không còn dùng `ModulePage`

### Slice 2.1-B - Khóa phân quyền đọc/ghi cho template

- **Loại:** `AFK`
- **Blocked by:** `Slice 2.1-A`
- **Mục tiêu:** tách rõ actor chỉ xem và actor được chỉnh sửa template.
- **Kết quả demo:** actor có `templates.view` xem được; actor có `templates.manage` mới thấy action tạo/sửa/active template.
- **Acceptance criteria:**
  - read path dùng `templates.view`
  - write actions dùng `templates.manage`
  - UI không lộ action quản trị cho actor chỉ có quyền xem

### Slice 2.1-C - Tạo schema DB tối thiểu cho template

- **Loại:** `AFK`
- **Blocked by:** `Slice 2.1-B`
- **Mục tiêu:** có persistence cho template metadata và `structure_json`.
- **Kết quả demo:** tạo được record template trong DB với subject, body structure và cờ active.
- **Acceptance criteria:**
  - có migration + model cho template
  - có trường đủ để lưu:
    - tên template
    - subject template
    - structure JSON
    - `is_active`
    - metadata audit tối thiểu
  - có ràng buộc chỉ một template active tại một thời điểm ở lớp application

### Slice 2.1-D - Dựng danh sách template + active state

- **Loại:** `AFK`
- **Blocked by:** `Slice 2.1-C`
- **Mục tiêu:** hiển thị danh sách template đang có và template nào đang active.
- **Kết quả demo:** thấy được template list, template active được đánh dấu rõ.
- **Acceptance criteria:**
  - page đọc dữ liệu từ controller/service thật
  - list có trạng thái `Đang hoạt động` / `Ngừng hoạt động`
  - empty state tiếng Việt rõ ràng khi chưa có template nào

### Slice 2.1-E - Tạo template bằng form cơ bản chưa cần drag-drop

- **Loại:** `AFK`
- **Blocked by:** `Slice 2.1-D`
- **Mục tiêu:** có thể tạo template đầu tiên bằng form cơ bản để mở đường cho builder.
- **Kết quả demo:** nhập tên template, subject, khung body cơ bản rồi lưu được.
- **Acceptance criteria:**
  - có modal hoặc page form tạo template
  - validate backend/frontend cho các field bắt buộc
  - save được `structure_json` dạng khởi tạo tối thiểu

### Slice 2.2-A - Cài dependency `vuedraggable` và dựng Builder canvas thật

- **Loại:** `AFK`
- **Blocked by:** `Slice 2.1-E`
- **Mục tiêu:** đưa visual builder thật vào page template.
- **Kết quả demo:** trong template editor có canvas các block/table row kéo thả được.
- **Acceptance criteria:**
  - `vuedraggable` được cài và dùng thực tế
  - không phá build hiện tại
  - builder canvas render từ `structure_json`

### Slice 2.2-B - Thêm block section cho 6 phần chính của email

- **Loại:** `AFK`
- **Blocked by:** `Slice 2.2-A`
- **Mục tiêu:** builder hiểu đúng 6 phần nghiệp vụ chính của template email.
- **Kết quả demo:** trong editor có thể thêm/chọn đúng các section:
  - `Subject`
  - `Lời chào`
  - `Table Chế độ tháng`
  - `Table Chương trình khoán đặc biệt`
  - `Table Chiết khấu cám cá`
  - `Table Chiết khấu Key Account`
- **Acceptance criteria:**
  - structure JSON có type rõ cho từng section
  - editor không trộn lẫn 4 bảng vào một block generic duy nhất
  - mỗi table section giữ được liên kết với sheet nguồn của nó

### Slice 2.2-C - Thêm block table row và reorder bằng kéo thả

- **Loại:** `AFK`
- **Blocked by:** `Slice 2.2-B`
- **Mục tiêu:** người dùng thêm/xóa/sắp xếp các dòng trong table.
- **Kết quả demo:** thêm vài dòng nội dung, kéo đổi thứ tự, state UI cập nhật đúng.
- **Acceptance criteria:**
  - add row
  - delete row
  - drag-drop reorder
  - save lại được structure sau reorder

### Slice 2.2-D - Hỗ trợ cha/con bằng indentation level

- **Loại:** `AFK`
- **Blocked by:** `Slice 2.2-C`
- **Mục tiêu:** cho phép một dòng trở thành mục cha hoặc mục con.
- **Kết quả demo:** tăng/giảm cấp dòng và thấy hierarchy phản ánh ngay trên canvas.
- **Acceptance criteria:**
  - mỗi row có `indentLevel` hoặc cấu trúc tương đương
  - mục con hiển thị thụt lề
  - structure JSON lưu được hierarchy đó

### Slice 2.2-E - Auto numbering theo hierarchy

- **Loại:** `AFK`
- **Blocked by:** `Slice 2.2-D`
- **Mục tiêu:** sinh STT tự động theo cấu trúc cha/con, đủ để biểu diễn kiểu `I/II` và `1/2/3` như email mẫu.
- **Kết quả demo:** thêm, xóa, kéo thả hoặc đổi indentation thì STT tự cập nhật và preview được ít nhất 2 cấp numbering.
- **Acceptance criteria:**
  - numbering không nhập tay
  - thay đổi thứ tự sẽ recalculated numbering
  - quy tắc numbering ổn định giữa preview và dữ liệu lưu
  - preview được group heading kiểu La Mã và row con kiểu số thường

### Slice 2.2-F - Auto format bold/regular theo vai trò cha/con

- **Loại:** `AFK`
- **Blocked by:** `Slice 2.2-E`
- **Mục tiêu:** mục cha mặc định bold, mục con mặc định regular.
- **Kết quả demo:** builder và preview đều phản ánh đúng format mặc định.
- **Acceptance criteria:**
  - parent row mặc định `font-weight: bold`
  - child row mặc định `font-weight: regular`
  - có thể lưu rule format này trong structure nếu cần

### Slice 2.3-A - Khai báo contract biến cho Subject và Lời chào

- **Loại:** `AFK`
- **Blocked by:** `Slice 2.2-F`
- **Mục tiêu:** chốt danh sách biến được phép dùng trong builder.
- **Kết quả demo:** UI có panel gợi ý biến xác nhận được từ tài liệu.
- **Acceptance criteria:**
  - chỉ hiển thị các biến đã được xác nhận
  - subject/body có thể chèn biến qua click hoặc nhập tay
  - contract biến được tách riêng khỏi template editor

### Slice 2.3-B - Thiết kế và preview riêng cho Subject

- **Loại:** `AFK`
- **Blocked by:** `Slice 2.3-A`
- **Mục tiêu:** render subject preview với biến động như một phần độc lập của template.
- **Kết quả demo:** subject như `Chế độ tháng {{tháng}} của khách hàng {{mã & tên khách hàng}}` được preview bằng dữ liệu thật.
- **Acceptance criteria:**
  - parser nhận đúng cú pháp `{{...}}`
  - preview subject render được dữ liệu thật
  - lỗi biến không hợp lệ được báo tiếng Việt

### Slice 2.3-C - Thiết kế và preview riêng cho Lời chào

- **Loại:** `AFK`
- **Blocked by:** `Slice 2.3-B`
- **Mục tiêu:** render được phần `Lời chào` như một phần độc lập của body email.
- **Kết quả demo:** lời chào và thông tin khách hiển thị đúng với dữ liệu aggregate đã persist.
- **Acceptance criteria:**
  - body text support interpolation
  - giá trị preview lấy từ dữ liệu aggregate thật
  - không dùng mock value đoán tay trong code production path

### Slice 2.3-D - Thiết kế và preview `Table Chế độ tháng` từ sheet `Tổng hợp`

- **Loại:** `AFK`
- **Blocked by:** `Slice 2.3-C`
- **Mục tiêu:** preview riêng bảng `Chế độ tháng` từ dữ liệu `Tổng hợp`.
- **Kết quả demo:** chọn một batch/khách mẫu và thấy bảng `Chế độ tháng` render đúng dữ liệu tương ứng.
- **Acceptance criteria:**
  - preview dùng record aggregate thật
  - section này chỉ dùng dữ liệu từ `Tổng hợp`
  - hierarchy và numbering của builder được phản ánh trong preview
  - preview render được các row type `Cộng` và `Bằng chữ` khi template có khai báo

### Slice 2.3-E - Thiết kế và preview `Table Chương trình khoán đặc biệt` từ sheet `Khoán NPP`

- **Loại:** `AFK`
- **Blocked by:** `Slice 2.3-D`
- **Mục tiêu:** preview riêng bảng `Chương trình khoán đặc biệt` từ dữ liệu `Khoán NPP`.
- **Kết quả demo:** khách có dữ liệu khoán sẽ render đúng bảng `Nội dung chương trình | Sản lượng | Mức hỗ trợ | Tổng tiền`.
- **Acceptance criteria:**
  - section này chỉ dùng dữ liệu từ `Khoán NPP`
  - preview phản ánh đúng row structure của builder
  - section không hiện nếu khách không có dữ liệu `Khoán NPP`
  - không áp mental model của `Tổng hợp` cho section này

#### Ghi chú thiết kế bắt buộc cho `Khoán NPP`

- `Khoán NPP` khác bản chất với `Tổng hợp`:
  - `Tổng hợp`: `Nội dung` lấy từ **tên cột**
  - `Khoán NPP`: `Nội dung chương trình` lấy từ **value của các cột `Nội dung CT i`**
- Vì vậy builder của `Khoán NPP` không nên dùng row model “mỗi dòng map tới một column key tĩnh” như `Tổng hợp`.
- Row model đúng cho `Khoán NPP` phải là **repeater theo `programItems[]`** đã parse từ sheet:
  - mỗi `programItem` tương ứng 1 CT thực tế
  - data source của 1 row lặp gồm:
    - `content <- programItem.content`
    - `quantity <- programItem.quantity`
    - `supportRate <- programItem.supportRate`
    - `amount <- programItem.amount`
- Từ workbook mẫu `Data import chuẩn_Final.xlsx` đã xác nhận:
  - sheet `Khoán NPP` có tối đa `16` block `Nội dung CT i | SL | đ/kg | Thành tiền`
  - dữ liệu mẫu hiện dùng tới `CT4`
  - parser hiện tại đã chuẩn hóa thành `programItems[]`
  - có CT chỉ có `content + amount`, còn `SL` và `đ/kg` để trống
  - vì vậy không được ép một row dữ liệu khoán phải luôn có đủ `SL` và `đ/kg`
- `Table rows` của builder `Khoán NPP` nên đi theo 4 row type sau:
  - `program-loop`
    - lặp qua toàn bộ `programItems[]` hợp lệ
    - phải giữ được `sourceProgramIndex` của từng CT
    - rule số hiển thị hiện còn mâu thuẫn giữa:
      - ví dụ prose trong `Mô tả phần mềm.txt` đang dùng STT tuần tự `1, 2, 3, 4`
      - sheet `Template Mail` trong workbook mẫu lại đang hiển thị `1, 4, 5, 7`
    - vì vậy trước khi chốt renderer thật, system phải lưu riêng:
      - `sourceProgramIndex`
      - `displayNumber`
    - không được hard-code ngay rằng `Khoán NPP` luôn renumber tuần tự như `Tổng hợp`
  - `blank`
    - dòng trống nếu người dùng muốn chèn khoảng cách
  - `total`
    - lấy từ `grandTotal`
    - label mặc định: `Cộng`
  - `in-words`
    - lấy từ `totalInWords`
    - label mặc định: `Bằng chữ:`
- UI `Table rows` cho `Khoán NPP` nên tối giản hơn `Tổng hợp`:
  - không có input text tay cho từng dòng `Nội dung chương trình`
  - không có dropdown chọn `columnKey` theo kiểu cột tĩnh
  - thay vào đó phải hiển thị rõ rằng row `program-loop` đang bind cố định tới `programItems[]`
- Rule render đúng:
  - một CT được render nếu parser đã tạo ra `programItem`
  - CT có `content + amount` nhưng `SL` và `đ/kg` rỗng vẫn phải render
  - CT hoàn toàn rỗng hoặc chỉ còn giá trị `0` thì không render
  - không thêm rule ẩn dòng mới trong builder nếu rule đó đã được parser xử lý ở nguồn
  - `total` và `in-words` là row tĩnh của section, không phải row phát sinh từ `CT i`

### Slice 2.3-F - Thiết kế và preview `Table Chiết khấu cám cá` từ sheet `Cám cá`

- **Loại:** `AFK`
- **Blocked by:** `Slice 2.3-E`
- **Mục tiêu:** preview riêng bảng `Chiết khấu cám cá` từ dữ liệu `Cám cá`.
- **Kết quả demo:** khách có dữ liệu cám cá sẽ render đúng bảng `STT | Nội dung | Tổng`.
- **UX preview bắt buộc:**
  - preview phải cho phép chọn khách từ danh sách `aggregated records` đã xử lý
  - dropdown phải search được theo `Mã & tên khách hàng`
  - khi đổi khách, preview phải apply đúng dữ liệu thật của khách vừa chọn
  - selection này phải đi theo cùng pattern đã dùng cho preview `Subject`, `Lời chào`, `Tổng hợp`, `Khoán NPP`
- **Dữ kiện đã xác nhận từ docs + parser + workbook mẫu:**
  - sheet `Cám cá` có 2 nhóm dữ liệu khác bản chất:
    - nhóm cột tĩnh / discrete:
      - `Tổng sản lượng`
      - `Doanh thu`
      - `Tiền chiết khấu theo Hóa đơn`
      - `Chiết khấu khác ( Không thể hiện trên hóa đơn)`
      - các cột thưởng / hỗ trợ thay đổi theo tháng như `Thưởng sản lượng tháng 03.2026`, `Hỗ trợ vận chuyển`, ...
    - nhóm cột CT:
      - `CT1 | Thành tiền`
      - `CT2 | Thành tiền`
      - `CT3 | Thành tiền`
      - ... có thể thay đổi theo tháng
  - parser thật ở `ParseCamCaPreviewService` đang xuất ra:
    - `discreteItems[] = [{ label, value }]`
    - `programItems[] = [{ programIndex, content, amount }]`
    - `grandTotal`
    - `totalInWords`
  - `Template Mail` xác nhận bảng `Chiết khấu cám cá` là hybrid:
    - có các dòng lấy từ tên cột thật của sheet `Cám cá` như `Tổng sản lượng`, `Doanh thu`
    - có nhóm cha/con:
      - `I | Tiền chiết khấu theo hóa đơn`
      - các dòng con `1..N` lấy từ cả discrete columns lẫn các `CT i`
      - `II | Chiết khấu khác ( không thể hiện trên hóa đơn)`
      - các dòng con tiếp theo lấy từ discrete columns của nhóm này
- **Thiết kế `Table rows` đúng bản chất:**
  - `value-row`
    - bind vào 1 giá trị đơn từ `Cám cá`
    - nguồn có thể là:
      - top-level fixed values như `Tổng sản lượng`, `Doanh thu`
      - hoặc 1 phần tử trong `discreteItems[]`
  - `program-loop`
    - lặp qua `programItems[]`
    - `content <- programItem.content`
    - `amount <- programItem.amount`
    - STT auto increment theo số CT thực render
  - `parent`
    - row cha hiển thị La Mã như `I`, `II`
    - dùng để nhóm các row con phía dưới
  - `blank`
  - `total`
    - lấy từ `grandTotal`
    - label mặc định: `Cộng`
  - `in-words`
    - lấy từ `totalInWords`
    - label mặc định: `Bằng chữ:`
- **Hệ quả cho builder UI:**
  - khác `Tổng hợp`, không thể chỉ dùng `columnKey` tĩnh cho toàn bộ section
  - khác `Khoán NPP`, không thể chỉ có mỗi `program-loop`
  - builder phải cho phép trộn trong cùng 1 bảng:
    - row bind vào `value-row`
    - row bind vào `program-loop`
    - row cha `parent`
    - row `total`
    - row `in-words`
- **Rule render đúng theo dữ liệu đã xác nhận:**
  - row `program-loop` chỉ render các CT có dữ liệu thật; CT rỗng hoàn toàn không render
  - row `value-row` lấy theo đúng nguồn bind đã chọn; không suy đoán lại từ text hiển thị
  - `total` và `in-words` là row tĩnh của section, không phát sinh từ `CT i`
- **Acceptance criteria:**
  - section này chỉ dùng dữ liệu từ `Cám cá`
  - preview phải cho phép chọn khách từ danh sách aggregate đã xử lý
  - preview phải render được cả 2 nguồn dữ liệu:
    - row từ cột / discrete values
    - row từ `CT i | Thành tiền`
  - preview render được các row type `parent`, `program-loop`, `total`, `in-words`
  - section không hiện nếu khách không có dữ liệu `Cám cá`

### Slice 2.3-G - Thiết kế và preview `Table Chiết khấu Key Account` từ sheet `Key Account`

- **Loại:** `AFK`
- **Blocked by:** `Slice 2.3-F`
- **Mục tiêu:** preview riêng bảng `Chiết khấu Key Account` từ dữ liệu `Key Account`.
- **Kết quả demo:** khách `Key Account` render đúng bảng `STT | Nội dung | Sản lượng | Mức hỗ trợ | Tổng`.
- **Acceptance criteria:**
  - section này chỉ dùng dữ liệu từ `Key Account`
  - `Table rows` hỗ trợ cả:
    - dòng bind vào tên cột thật của sheet `Key Account`
    - dòng lặp từ các cụm `Nội dung CT i | SL | đ/kg | Thành tiền`
  - preview cho phép chọn khách từ danh sách aggregated records đã xử lý
  - dropdown chọn khách search được theo `Mã & tên khách hàng`
  - preview phản ánh đúng hierarchy và numbering của builder
  - preview không lấy nhầm dữ liệu từ `Khách thường`

### Slice 2.3-H - Audit rule hiển thị dòng giá trị `0/rỗng`

- **Loại:** `DONE / DOC-CLEANUP`
- **Blocked by:** `Slice 2.3-G`
- **Mục tiêu ban đầu:** chốt rõ template preview có giữ hay ẩn các dòng có giá trị `0`.
- **Trạng thái thực tế:** rule này đã được absorb vào các lát cắt trước, không còn là một slice implementation riêng.
- **Evidence đã có trong code:**
  - `Tổng hợp`: có `Ẩn khi = 0 hoặc rỗng` và dùng shared render service
  - `Khoán NPP`: ẩn dòng CT khi cả cụm `Nội dung | SL | đ/kg | Thành tiền` đều `0/rỗng`
  - `Cám cá`: có `Ẩn khi = 0 hoặc rỗng` cho các dòng bind dữ liệu
  - `Key Account`: có `Ẩn khi = 0 hoặc rỗng` cho các dòng bind dữ liệu
- **Kết luận:** không cần mở thêm slice BE/FE riêng cho `2.3-H`; chỉ cần giữ test và tài liệu đồng bộ với behavior hiện tại.

### Slice 2.4-A - Persistence cho builder/composition model

- **Loại:** `DONE`
- **Blocked by:** `Slice 2.3-H` trong kế hoạch cũ, nhưng hiện không còn phụ thuộc thực tế
- **Mục tiêu ban đầu:** chỉnh sửa builder xong thì lưu được JSON structure chuẩn.
- **Trạng thái thực tế:** scope này đã hoàn tất và vượt qua version kế hoạch ban đầu.
- **Evidence đã có trong code:**
  - save/update đi qua service BE thật
  - dữ liệu không còn chỉ là một blob JSON duy nhất; đã được refactor sang `part versions + canvas composition`
  - reload editor không mất state
  - read/write path đã đi qua DB thật và có test bảo vệ
- **Kết luận:** `2.4-A` được coi là hoàn tất; không nên giữ như backlog mở nữa

### Slice 2.4-B - Chuyển active template an toàn

- **Loại:** `DONE`
- **Blocked by:** `Slice 2.4-A`
- **Mục tiêu:** chỉ cho phép đúng 1 template active tại một thời điểm.
- **Kết quả demo:** active template mới thì template cũ bị disable tự động.
- **Acceptance criteria:**
  - UI có action `Đặt làm template hoạt động`
  - service BE bảo đảm chỉ một template active
  - DB không rơi vào trạng thái hai template cùng active

### Slice 2.4-C - Smoke test end-to-end cho Giai đoạn 2

- **Loại:** `DONE`
- **Blocked by:** `Slice 2.4-B`
- **Mục tiêu:** khóa toàn bộ flow template builder bằng test và verify UI.
- **Kết quả demo:** tạo template, kéo thả, lưu, active, reload và preview lại được.
- **Acceptance criteria:**
  - có feature test cho CRUD template và active-state
  - có test cho interpolation engine
  - có smoke path UI cho builder tối thiểu
  - đã có smoke feature test đi qua full flow: create canvas -> add parts -> save content -> activate -> reload page với preview context thật

## 5. Thứ tự triển khai đề xuất

Thực hiện đúng thứ tự sau:

1. `Slice 2.1-A`
2. `Slice 2.1-B`
3. `Slice 2.1-C`
4. `Slice 2.1-D`
5. `Slice 2.1-E`
6. `Slice 2.2-A`
7. `Slice 2.2-B`
8. `Slice 2.2-C`
9. `Slice 2.2-D`
10. `Slice 2.2-E`
11. `Slice 2.2-F`
12. `Slice 2.3-A`
13. `Slice 2.3-B`
14. `Slice 2.3-C`
15. `Slice 2.3-D`
16. `Slice 2.3-E`
17. `Slice 2.3-F`
18. `Slice 2.3-G`
19. `Slice 2.4-B`
20. `Slice 2.4-C`

Lý do:

- mở đường bằng page thật và persistence tối thiểu trước;
- chỉ cài drag-drop khi đã có editor thật để gắn vào;
- `Subject` và `Lời chào` phải tách riêng trước 4 bảng dữ liệu;
- 4 table phải được tách thành 4 flow riêng vì mỗi bảng gắn với một sheet nguồn khác nhau;
- rule hiển thị dòng `0/rỗng` hiện đã được chốt dần trong từng table slice, không còn là một lát cắt mở độc lập;
- active state nên làm sau khi persistence/composition model đã vững;
- smoke test chỉ chốt ở cuối khi full flow đã có đủ evidence.

## 6. Mapping ra file/code dự kiến

- `Slice 2.1-A` đến `2.1-B`
  - `routes/web.php`
  - `app/Http/Controllers/TemplatePageController.php`
  - `app/Services/Templates/TemplatePageService.php`
  - `resources/js/Pages/Templates/Index.vue`
  - `resources/js/Services/templates/...`
- `Slice 2.1-C` đến `2.1-E`
  - migration/model template
  - request validation
  - create/update services
  - template list/editor components
- `Slice 2.2-A` đến `2.2-E`
  - `package.json`
  - builder components
  - drag-drop services/composables
  - structure normalization helpers
- `Slice 2.3-A` đến `2.3-D`
  - interpolation services
  - preview builder components
  - bridge tới aggregated record data
- `Slice 2.4-B` đến `2.4-C`
  - active template service
  - feature tests / e2e smoke

## 6.1. Ghi chú cập nhật trạng thái

- `Slice 2.3-H` không còn là backlog implementation riêng; coi như đã được absorb vào các lát cắt preview/render của từng bảng.
- `Slice 2.4-A` đã hoàn tất bởi refactor persistence hiện tại:
  - `template_parts`
  - `template_part_versions`
  - `mail_template_canvases`
  - `mail_template_canvas_parts`
- Backlog thực còn lại của `Giai đoạn 2` nên tập trung vào:
  - `2.4-B`
  - `2.4-C`

## 7. Definition of Done cho Giai đoạn 2

`Giai đoạn 2` được coi là xong khi:

- `/templates` không còn là placeholder;
- có page quản lý template thật;
- người có `templates.manage` tạo, sửa và lưu template được;
- builder hỗ trợ kéo thả và hierarchy cha/con;
- STT auto nhảy, hỗ trợ ít nhất 2 cấp hiển thị như email mẫu, và bold/regular mặc định hoạt động đúng;
- `Subject` hỗ trợ interpolation bằng các biến đã được xác nhận;
- `Lời chào` hỗ trợ interpolation bằng các biến đã được xác nhận;
- preview được riêng 4 bảng:
  - `Chế độ tháng`
  - `Chương trình khoán đặc biệt`
  - `Chiết khấu cám cá`
  - `Chiết khấu Key Account`
- mỗi bảng dùng đúng sheet nguồn của nó;
- preview biểu diễn được các dòng `Cộng` và `Bằng chữ`;
- chỉ có đúng `1 template active` tại một thời điểm;
- cấu trúc template được lưu dưới dạng JSON trong Postgres;
- có test đủ để khóa CRUD, active-state, interpolation và preview tối thiểu.

## 8. Bước tiếp theo sau file kế hoạch này

Sau khi chốt kế hoạch này, bước hợp lý tiếp theo là tạo tài liệu `Thiết kế chi tiết` cho `Slice 2.1-A -> 2.1-E`, vì đó là cụm mở đường nhỏ nhất để biến `/templates` từ placeholder thành module thật.
