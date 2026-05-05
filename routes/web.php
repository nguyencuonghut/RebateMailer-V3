<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ImportPageController;
use App\Http\Controllers\ImportUploadController;
use App\Http\Controllers\ImportWorkbookAnalysisController;
use App\Http\Controllers\UserManagementController;
use App\Support\Authorization\PermissionName;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
})->name('home');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    Route::get('/imports', [ImportPageController::class, 'index'])
        ->middleware('permission:'.PermissionName::ImportsView->value)
        ->name('imports.index');
    Route::post('/imports/upload', [ImportUploadController::class, 'store'])
        ->middleware('permission:'.PermissionName::ImportsManage->value)
        ->name('imports.upload');
    Route::post('/imports/analyze-workbook', [ImportWorkbookAnalysisController::class, 'store'])
        ->middleware('permission:'.PermissionName::ImportsManage->value)
        ->name('imports.analyze-workbook');

    Route::get('/templates', function () {
        return Inertia::render('ModulePage', [
            'title' => 'Thiết kế mẫu email',
            'description' => 'Quản lý subject, body và mapping dữ liệu cho từng mẫu email rebate.',
            'capability' => 'Tạo và rà soát template email theo từng chương trình.',
            'status' => 'Sẵn sàng triển khai',
        ]);
    })->middleware('permission:'.PermissionName::TemplatesView->value)->name('templates.index');

    Route::get('/mail', function () {
        return Inertia::render('ModulePage', [
            'title' => 'Điều phối gửi mail',
            'description' => 'Theo dõi các đợt gửi thử, hàng đợi xử lý và kênh mail testing cục bộ.',
            'capability' => 'Giám sát luồng gửi email và trạng thái vận hành.',
            'status' => 'Mailpit đã sẵn sàng',
        ]);
    })->middleware('permission:'.PermissionName::MailView->value)->name('mail.index');

    Route::get('/tracking', function () {
        return Inertia::render('ModulePage', [
            'title' => 'Theo dõi và gửi lại',
            'description' => 'Tổng hợp lịch sử gửi, trạng thái mở thư và các ca cần gửi lại thủ công.',
            'capability' => 'Giám sát retry và xử lý ngoại lệ chiến dịch.',
            'status' => 'Đang chờ slice nghiệp vụ',
        ]);
    })->middleware('permission:'.PermissionName::TrackingView->value)->name('tracking.index');

    Route::get('/users', [UserManagementController::class, 'index'])
        ->middleware('permission:'.PermissionName::UsersView->value)
        ->name('users.index');
    Route::post('/users', [UserManagementController::class, 'store'])
        ->middleware('permission:'.PermissionName::UsersCreate->value)
        ->name('users.store');
    Route::put('/users/{user}', [UserManagementController::class, 'update'])
        ->middleware('permission:'.PermissionName::UsersUpdate->value)
        ->name('users.update');
    Route::delete('/users/{user}', [UserManagementController::class, 'destroy'])
        ->middleware('permission:'.PermissionName::UsersDelete->value)
        ->name('users.destroy');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
