<?php

use App\Http\Controllers\ImportAggregatePreviewController;
use App\Http\Controllers\ImportBatchStatusController;
use App\Http\Controllers\ImportCamCaPreviewController;
use App\Http\Controllers\ImportKeyAccountPreviewController;
use App\Http\Controllers\DashboardPageController;
use App\Http\Controllers\MailCampaignPageController;
use App\Http\Controllers\MailCampaignDispatchController;
use App\Http\Controllers\MailCampaignFailedRecipientsExportController;
use App\Http\Controllers\MailCampaignRecipientRetryController;
use App\Http\Controllers\MailCampaignSampleSendController;
use App\Http\Controllers\MailCampaignScheduleController;
use App\Http\Controllers\MailCampaignStoreController;
use App\Http\Controllers\ImportPageController;
use App\Http\Controllers\ImportKhoanNppPreviewController;
use App\Http\Controllers\ImportProcessBatchController;
use App\Http\Controllers\ImportTongHopPreviewController;
use App\Http\Controllers\ImportUploadController;
use App\Http\Controllers\ImportWorkbookAnalysisController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TemplatePageController;
use App\Http\Controllers\TemplateCanvasCompositionUpdateController;
use App\Http\Controllers\TemplateCanvasPartBindingUpdateController;
use App\Http\Controllers\TemplateActivateController;
use App\Http\Controllers\TemplatePartUpdateController;
use App\Http\Controllers\TemplateSectionStoreController;
use App\Http\Controllers\TemplateStructureUpdateController;
use App\Http\Controllers\TemplateStoreController;
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
    Route::get('/dashboard', DashboardPageController::class)->name('dashboard');

    Route::get('/imports', [ImportPageController::class, 'index'])
        ->middleware('permission:'.PermissionName::ImportsView->value)
        ->name('imports.index');
    Route::post('/imports/upload', [ImportUploadController::class, 'store'])
        ->middleware('permission:'.PermissionName::ImportsManage->value)
        ->name('imports.upload');
    Route::post('/imports/process-batch', [ImportProcessBatchController::class, 'store'])
        ->middleware('permission:'.PermissionName::ImportsManage->value)
        ->name('imports.process-batch');
    Route::get('/imports/batches/{importBatch}/status', [ImportBatchStatusController::class, 'show'])
        ->middleware('permission:'.PermissionName::ImportsManage->value)
        ->name('imports.batch-status');
    Route::post('/imports/analyze-workbook', [ImportWorkbookAnalysisController::class, 'store'])
        ->middleware('permission:'.PermissionName::ImportsManage->value)
        ->name('imports.analyze-workbook');
    Route::post('/imports/preview-tong-hop', [ImportTongHopPreviewController::class, 'store'])
        ->middleware('permission:'.PermissionName::ImportsManage->value)
        ->name('imports.preview-tong-hop');
    Route::post('/imports/preview-khoan-npp', [ImportKhoanNppPreviewController::class, 'store'])
        ->middleware('permission:'.PermissionName::ImportsManage->value)
        ->name('imports.preview-khoan-npp');
    Route::post('/imports/preview-cam-ca', [ImportCamCaPreviewController::class, 'store'])
        ->middleware('permission:'.PermissionName::ImportsManage->value)
        ->name('imports.preview-cam-ca');
    Route::post('/imports/preview-key-account', [ImportKeyAccountPreviewController::class, 'store'])
        ->middleware('permission:'.PermissionName::ImportsManage->value)
        ->name('imports.preview-key-account');
    Route::post('/imports/preview-aggregated', [ImportAggregatePreviewController::class, 'store'])
        ->middleware('permission:'.PermissionName::ImportsManage->value)
        ->name('imports.preview-aggregated');

    Route::get('/templates', [TemplatePageController::class, 'index'])
        ->middleware('permission:'.PermissionName::TemplatesView->value)
        ->name('templates.index');
    Route::post('/templates', [TemplateStoreController::class, 'store'])
        ->middleware('permission:'.PermissionName::TemplatesManage->value)
        ->name('templates.store');
    Route::post('/templates/{mailTemplate}/sections', [TemplateSectionStoreController::class, 'store'])
        ->middleware('permission:'.PermissionName::TemplatesManage->value)
        ->name('templates.sections.store');
    Route::put('/templates/{mailTemplate}/canvas-composition', [TemplateCanvasCompositionUpdateController::class, 'update'])
        ->middleware('permission:'.PermissionName::TemplatesManage->value)
        ->name('templates.canvas.update');
    Route::put('/templates/{mailTemplate}/canvas-part-binding', [TemplateCanvasPartBindingUpdateController::class, 'update'])
        ->middleware('permission:'.PermissionName::TemplatesManage->value)
        ->name('templates.canvas-part-binding.update');
    Route::put('/templates/{mailTemplate}/activate', [TemplateActivateController::class, 'update'])
        ->middleware('permission:'.PermissionName::TemplatesManage->value)
        ->name('templates.activate');
    Route::put('/templates/{mailTemplate}/parts', [TemplatePartUpdateController::class, 'update'])
        ->middleware('permission:'.PermissionName::TemplatesManage->value)
        ->name('templates.parts.update');
    Route::put('/templates/{mailTemplate}/structure', [TemplateStructureUpdateController::class, 'update'])
        ->middleware('permission:'.PermissionName::TemplatesManage->value)
        ->name('templates.structure.update');

    Route::get('/mail', [MailCampaignPageController::class, 'index'])
        ->middleware('permission:'.PermissionName::MailView->value)
        ->name('mail.index');
    Route::post('/mail/campaigns', [MailCampaignStoreController::class, 'store'])
        ->middleware('permission:'.PermissionName::MailSend->value)
        ->name('mail.campaigns.store');
    Route::post('/mail/campaigns/{mailCampaign}/schedule', MailCampaignScheduleController::class)
        ->middleware('permission:'.PermissionName::MailSend->value)
        ->name('mail.campaigns.schedule');
    Route::post('/mail/campaigns/{mailCampaign}/dispatch', MailCampaignDispatchController::class)
        ->middleware('permission:'.PermissionName::MailSend->value)
        ->name('mail.campaigns.dispatch');
    Route::post('/mail/campaigns/{mailCampaign}/recipients/{mailCampaignRecipient}/retry', MailCampaignRecipientRetryController::class)
        ->middleware('permission:'.PermissionName::MailSend->value)
        ->name('mail.campaigns.recipients.retry');
    Route::get('/mail/campaigns/{mailCampaign}/recipients/export-failed', MailCampaignFailedRecipientsExportController::class)
        ->middleware('permission:'.PermissionName::MailView->value)
        ->name('mail.campaigns.recipients.export-failed');
    Route::post('/mail/campaigns/{mailCampaign}/send-sample', MailCampaignSampleSendController::class)
        ->middleware('permission:'.PermissionName::MailSend->value)
        ->name('mail.campaigns.send-sample');

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
