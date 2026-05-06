# Phương án xử lý 2 vấn đề Production — Module Import

## Vấn đề 1: Card "Slice 1.7" chiếm diện tích không cần thiết

### Phân tích hiện trạng
Card lớn bên trái (`xl:grid-cols-[minmax(0,1.7fr)_...]`) trong `Index.vue` chứa:
- Label "Slice 1.7" (dev progress)
- Message về slice + bước kế tiếp (dev context)
- "Chính sách tiếp nhận file" + danh sách 4 sheet tags

Content duy nhất có giá trị production là **danh sách 4 sheet hợp lệ** và **chính sách file `.xlsx`**.

### Phương án
**Xóa card left-panel hoàn toàn**. Thay bằng một `PageHeader` nhỏ gọn nằm trên cùng page gồm:
- Tiêu đề "Import dữ liệu"
- Mô tả ngắn 1 dòng
- Tags 4 sheet hợp lệ (inline, compact)

Layout mới: **full-width 1 cột** thay vì 2 cột split, giúp phần upload + preview có không gian rộng hơn.

---

## Vấn đề 2: Phải nhấn Preview thủ công — không hợp lý cho production

### Phân tích hiện trạng
Hiện tại người dùng phải nhấn **5 nút** theo thứ tự:
1. Upload → 2. Đọc workbook → 3. Preview Tổng hợp → 4. Preview Khoán NPP → ... → 6. Preview Aggregator

Mỗi bước tạo thêm cards dài xuống, gây **page rất dài** với file thực tế hàng nghìn dòng.

### Phương án

#### Backend: "One-shot aggregate" ngay sau upload
Gộp toàn bộ pipeline `analyze → parse 4 sheets → aggregate → persist` vào **một action duy nhất** khi user bấm Upload. Cụ thể:
- `POST /imports/upload` giữ nguyên (chỉ lưu file, trả receipt)  
- **Thêm endpoint** `POST /imports/process-batch` — chạy pipeline đầy đủ (analyze + parse + aggregate + persist)  
- FE tự động gọi `process-batch` ngay sau khi upload thành công, **không cần người dùng bấm thêm**

Ưu điểm:
- DB được populate ngay sau upload  
- User chỉ cần chờ 1 lần (1 spinner duy nhất)  
- Vẫn giữ được phân tách concern (upload ≠ process)

#### Frontend: Kết quả hiển thị theo TabView thay vì xếp dọc
Sau khi batch được process xong, **thay toàn bộ phần preview cards** bằng một `TabView` PrimeVue gồm 5 tabs:

| Tab | Nội dung |
|---|---|
| `Tổng hợp` | DataTable import_batch_sheet_records cho sheet Tổng hợp |
| `Khoán NPP` | DataTable import_batch_sheet_records cho sheet Khoán NPP |
| `Cám cá` | DataTable import_batch_sheet_records cho sheet Cám cá |
| `Key Account` | DataTable import_batch_sheet_records cho sheet Key Account |
| `Aggregator` | DataTable import_batch_aggregated_records (view hợp nhất) |

**Tab chỉ load dữ liệu khi được activate** (lazy loading per tab) → không kéo hết dữ liệu về cùng lúc.

---

## Proposed Changes

### Backend

#### [NEW] `app/Http/Controllers/ImportProcessBatchController.php`
Controller nhận `importBatchId`, gọi `PrepareAggregatePreviewService` (đã có) để chạy toàn bộ pipeline.

Route mới:
```
POST /imports/process-batch  → ImportProcessBatchController
     middleware: imports.manage
```

#### [MODIFY] `app/Http/Controllers/ImportUploadController.php`
Không thay đổi — vẫn chỉ store file + tạo batch. FE sẽ tự gọi process-batch sau khi nhận receipt.

#### [MODIFY] `app/Services/Imports/ImportPageService.php`
Cập nhật `currentSlice` và các prop liên quan đến dev context để loại bỏ nội dung development-only.

---

### Frontend

#### [MODIFY] `resources/js/Pages/Imports/Index.vue`
- Xóa card "Slice 1.7" left-panel
- Thêm `PageHeader` component nhỏ gọn với tiêu đề + sheet tags
- Sau upload xong → tự động gọi `process-batch` (thay vì chờ user bấm từng bước)
- Thay các preview cards dọc bằng `TabView` (5 tabs)
- Layout chuyển về **full-width** (bỏ split 2 cột)

#### [NEW] `resources/js/Services/imports/useImportProcessBatchFlow.ts`
Composable xử lý call `POST /imports/process-batch`:
- State: `isProcessing`, `processingError`
- Auto-trigger sau khi upload thành công

#### [MODIFY] `resources/js/Services/imports/useImportUploadFlow.ts`
Sau khi upload thành công → emit event hoặc callback để trigger `process-batch`.

#### [NEW] `resources/js/Components/imports/ImportResultTabs.vue`
TabView component bọc 5 tabs:
- Props: `batchId`, `activeBatchId`
- Mỗi tab lazy-load data khi activate lần đầu
- Dùng lại `ImportTongHopPreview`, `ImportKhoanNppPreview`, v.v. bên trong tab

#### [MODIFY] `resources/js/Components/imports/ImportPreviewShell.vue`
Đơn giản hóa: chỉ hiển thị upload receipt info (file name, batch code, status). Bỏ workbook boundary detail (đã không cần thiết khi pipeline chạy tự động).

---

## Luồng mới sau refactor

```
User chọn file → bấm Upload
  → POST /imports/upload                    (receipt + batch created)
  → [auto] POST /imports/process-batch      (analyze + parse 4 sheets + aggregate)
  → [loading spinner 1 lần]
  → Khi xong: hiện TabView với 5 tabs
       Tab "Tổng hợp"   → đọc từ DB (on-demand khi switch tab)
       Tab "Khoán NPP"  → đọc từ DB (on-demand khi switch tab)
       Tab "Cám cá"     → đọc từ DB (on-demand khi switch tab)
       Tab "Key Account"→ đọc từ DB (on-demand khi switch tab)
       Tab "Aggregator" → đọc từ DB (on-demand khi switch tab)
```

---

## Quyết định thiết kế đã chốt

- **Tab mặc định:** "Dữ liệu hợp nhất" (Aggregator) — ở vị trí **đầu tiên**
- **Thứ tự tabs:** Dữ liệu hợp nhất | Tổng hợp | Khoán NPP | Cám cá | Key Account
- **Workbook boundary:** giữ nguyên, hiển thị phía trước TabView

## Verification Plan

### Automated Tests
- Thêm feature test cho `POST /imports/process-batch` — happy path và error case
- Đảm bảo `php vendor/bin/phpunit` toàn suite vẫn pass

### Manual Verification
- Upload file mẫu → verify DB có data trong cả 3 bảng sau 1 lần bấm Upload
- Switch tabs → verify lazy loading hoạt động đúng
- Mở lại batch cũ từ lịch sử → verify TabView hiện đúng data đã persist

