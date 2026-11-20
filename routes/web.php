<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Modules\DailyWorkController;
use App\Http\Controllers\Modules\EventController;
use App\Http\Controllers\Modules\InventoryController;
use App\Http\Controllers\Modules\GasDetectorController;
use App\Http\Controllers\Modules\WorkforceController;
use App\Http\Controllers\Modules\ManHoursController;
use App\Http\Controllers\Modules\IncidentController;
use App\Http\Controllers\Modules\IssueFollowUpController;
use App\Http\Controllers\Modules\UserController;
use App\Http\Controllers\Modules\BudgetMonitoringController;
use App\Http\Controllers\Modules\EnvironmentController;

/*
|--------------------------------------------------------------------------
| Halaman Login & Autentikasi
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.process');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

/*
|--------------------------------------------------------------------------
| Dashboard
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'not.inventory'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/export', [DashboardController::class, 'export'])->name('dashboard.export')->middleware('can.export');
});

/*
|--------------------------------------------------------------------------
| Daily Work
|--------------------------------------------------------------------------
*/

Route::prefix('daily-work')->name('daily-work.')->middleware(['auth', 'not.inventory'])->group(function () {
    // Read & Export (Semua Role: Admin Management, Admin Inventory, User)
    Route::get('/', [DailyWorkController::class, 'index'])->name('index');
    Route::get('/check-duplicate', [DailyWorkController::class, 'checkDuplicate'])->name('check-duplicate');
    Route::get('/export', [DailyWorkController::class, 'export'])->name('export')->middleware('can.export');
    Route::get('/masters/all', [DailyWorkController::class, 'getMasters'])->name('masters.index');
    Route::get('/{daily_work}', [DailyWorkController::class, 'show'])->name('show');

    // Write actions (Hanya Admin Management)
    Route::middleware(['admin'])->group(function () {
        Route::post('/', [DailyWorkController::class, 'store'])->name('store');
        Route::put('/{daily_work}', [DailyWorkController::class, 'update'])->name('update');
        Route::delete('/{daily_work}', [DailyWorkController::class, 'destroy'])->name('destroy');
        Route::post('/{daily_work}/complete', [DailyWorkController::class, 'complete'])->name('complete');
        Route::patch('/{daily_work}/complete', [DailyWorkController::class, 'complete'])->name('complete.patch');

        // Master Data CRUD (Perusahaan & SIKA)
        Route::post('/masters/companies', [DailyWorkController::class, 'storeCompany'])->name('masters.companies.store');
        Route::put('/masters/companies/{id}', [DailyWorkController::class, 'updateCompany'])->name('masters.companies.update');
        Route::delete('/masters/companies/{id}', [DailyWorkController::class, 'destroyCompany'])->name('masters.companies.destroy');
        Route::post('/masters/companies/{id}/toggle', [DailyWorkController::class, 'toggleCompany'])->name('masters.companies.toggle');

        Route::post('/masters/sika', [DailyWorkController::class, 'storeSikka'])->name('masters.sika.store');
        Route::put('/masters/sika/{id}', [DailyWorkController::class, 'updateSikka'])->name('masters.sika.update');
        Route::delete('/masters/sika/{id}', [DailyWorkController::class, 'destroySikka'])->name('masters.sika.destroy');
        Route::post('/masters/sika/{id}/toggle', [DailyWorkController::class, 'toggleSikka'])->name('masters.sika.toggle');

        Route::post('/masters/sikka', [DailyWorkController::class, 'storeSikka'])->name('masters.sikka.store');
        Route::put('/masters/sikka/{id}', [DailyWorkController::class, 'updateSikka'])->name('masters.sikka.update');
        Route::delete('/masters/sikka/{id}', [DailyWorkController::class, 'destroySikka'])->name('masters.sikka.destroy');
        Route::post('/masters/sikka/{id}/toggle', [DailyWorkController::class, 'toggleSikka'])->name('masters.sikka.toggle');
    });
});

/*
|--------------------------------------------------------------------------
| Events
|--------------------------------------------------------------------------
*/

Route::prefix('events')->name('events.')->middleware(['auth', 'not.inventory'])->group(function () {
    // Read & Check (Semua Role: Admin Management, User; Admin Inventory ditolak)
    Route::get('/', [EventController::class, 'index'])->name('index');
    Route::get('/check-duplicate', [EventController::class, 'checkDuplicate'])->name('check-duplicate');
    Route::get('/companies', [EventController::class, 'getCompanies'])->name('companies.index');
    Route::get('/documentation/{documentId}/view', [EventController::class, 'viewDocumentation'])->name('documentation.view');
    Route::get('/documentation/{documentId}/download', [EventController::class, 'downloadDocumentation'])->name('documentation.download');
    Route::get('/{event}/view-doc', [EventController::class, 'viewEventDocumentation'])->name('view-doc');
    Route::get('/{event}/download-doc', [EventController::class, 'downloadEventDocumentation'])->name('download-doc');

    // Export PDF (Admin Management & User; Admin Inventory ditolak)
    Route::get('/export', [EventController::class, 'export'])->name('export')->middleware('can.export:events');

    // Write actions (Hanya Admin Management)
    Route::middleware(['admin'])->group(function () {
        Route::post('/companies', [EventController::class, 'storeCompany'])->name('companies.store');
        Route::delete('/companies/{nameOrId}', [EventController::class, 'destroyCompany'])->name('companies.destroy');
        Route::post('/', [EventController::class, 'store'])->name('store');
        Route::put('/{event}', [EventController::class, 'update'])->name('update');
        Route::delete('/{event}', [EventController::class, 'destroy'])->name('destroy');
        Route::post('/{event}/upload', [EventController::class, 'uploadDocumentation'])->name('upload');
    });

    Route::get('/{event}', [EventController::class, 'show'])->name('show');
});

/*
|--------------------------------------------------------------------------
| Inventory
|--------------------------------------------------------------------------
*/

Route::prefix('inventory')->name('inventory.')->middleware(['auth', 'active.user'])->group(function () {
    // Read & Export
    Route::get('/', [InventoryController::class, 'index'])->name('index');
    Route::get('/export', [InventoryController::class, 'export'])->name('export')->middleware('can.export');
    Route::get('/transactions', [InventoryController::class, 'transactions'])->name('transactions')->middleware('not.inventory');
    Route::get('/search-items', [InventoryController::class, 'searchItems'])->name('search-items');
    Route::get('/units', [InventoryController::class, 'getUnits'])->name('units.index')->middleware('admin');

    // Write actions Master Barang, Satuan, & Catat Transaksi (Hanya Admin Management)
    Route::middleware(['admin'])->group(function () {
        Route::post('/', [InventoryController::class, 'storeItem'])->name('store');
        Route::put('/{inventory}', [InventoryController::class, 'updateItem'])->name('update');
        Route::delete('/{inventory}', [InventoryController::class, 'destroyItem'])->name('destroy');
        Route::post('/transactions', [InventoryController::class, 'storeTransaction'])->name('transactions.store');

        // Master Satuan CRUD
        Route::post('/units', [InventoryController::class, 'storeUnit'])->name('units.store');
        Route::put('/units/{unit}', [InventoryController::class, 'updateUnit'])->name('units.update');
        Route::delete('/units/{unit}', [InventoryController::class, 'destroyUnit'])->name('units.destroy');
        Route::post('/units/{unit}/toggle', [InventoryController::class, 'toggleUnit'])->name('units.toggle');
    });

    // Peminjaman & Pengembalian Barang (Admin Management & Admin Inventory, Ditolak untuk User)
    Route::middleware(['role:admin_management,admin_inventory'])->group(function () {
        Route::post('/borrowings', [InventoryController::class, 'storeBorrowing'])->name('borrowings.store');
        Route::post('/borrowings/{borrowing}/return', [InventoryController::class, 'returnBorrowing'])->name('borrowings.return');
        Route::match(['post', 'put', 'patch'], '/borrowings/{borrowing}/due-date', [InventoryController::class, 'updateBorrowingDueDate'])->name('borrowings.due-date');
    });
});

/*
|--------------------------------------------------------------------------
| Gas Detector / Gas Monitoring
|--------------------------------------------------------------------------
*/

Route::prefix('gas-detector')->name('gas-detector.')->middleware(['auth', 'not.inventory'])->group(function () {
    // Read & Export (Semua Role)
    Route::get('/', [GasDetectorController::class, 'index'])->name('index');
    Route::get('/check-duplicate', [GasDetectorController::class, 'checkDuplicate'])->name('check-duplicate');
    Route::get('/export', [GasDetectorController::class, 'export'])->name('export')->middleware('can.export');
    Route::get('/measurements/export', [GasDetectorController::class, 'exportMeasurements'])->name('measurements.export')->middleware('can.export');
    Route::get('/measurements/{gasMonitoring}', [GasDetectorController::class, 'showMeasurement'])->name('measurements.show');
    Route::get('/well-pads/data', [GasDetectorController::class, 'getWellPadsData'])->name('well-pads.data');

    // Write actions (Hanya Admin Management)
    Route::middleware(['admin'])->group(function () {
        Route::post('/measurements/batch', [GasDetectorController::class, 'storeBatchMeasurements'])->name('measurements.store-batch');
        Route::post('/measurements', [GasDetectorController::class, 'storeMeasurement'])->name('measurements.store');
        Route::put('/measurements/{gasMonitoring}', [GasDetectorController::class, 'updateMeasurement'])->name('measurements.update');
        Route::delete('/measurements/{gasMonitoring}', [GasDetectorController::class, 'destroyMeasurement'])->name('measurements.destroy');

        Route::post('/well-pads', [GasDetectorController::class, 'storeWellPad'])->name('well-pads.store');
        Route::put('/well-pads/{wellPad}', [GasDetectorController::class, 'updateWellPad'])->name('well-pads.update');
        Route::delete('/well-pads/{wellPad}', [GasDetectorController::class, 'destroyWellPad'])->name('well-pads.destroy');

        Route::post('/', [GasDetectorController::class, 'store'])->name('store');
        Route::put('/{gas_detector}', [GasDetectorController::class, 'update'])->name('update');
        Route::delete('/{gas_detector}', [GasDetectorController::class, 'destroy'])->name('destroy');
    });

    Route::get('/{gas_detector}', [GasDetectorController::class, 'show'])->name('show');
});

// Alias routes untuk gas-monitoring
Route::prefix('gas-monitoring')->name('gas-monitoring.')->middleware(['auth', 'not.inventory'])->group(function () {
    Route::get('/', [GasDetectorController::class, 'index'])->name('index');
    Route::get('/export', [GasDetectorController::class, 'exportMeasurements'])->name('export')->middleware('can.export');

    Route::middleware(['admin'])->group(function () {
        Route::post('/', [GasDetectorController::class, 'storeMeasurement'])->name('store');
        Route::put('/{gasMonitoring}', [GasDetectorController::class, 'updateMeasurement'])->name('update');
        Route::delete('/{gasMonitoring}', [GasDetectorController::class, 'destroyMeasurement'])->name('destroy');
    });
});

Route::get('/gasdetektor', function () {
    return redirect()->route('gas-detector.index');
})->middleware(['auth', 'not.inventory']);

/*
|--------------------------------------------------------------------------
| Workforce / Manpower
|--------------------------------------------------------------------------
*/

Route::prefix('workforce')->name('workforce.')->middleware(['auth', 'not.inventory'])->group(function () {
    // Read & Export (Semua Role)
    Route::get('/', [WorkforceController::class, 'index'])->name('index');
    Route::get('/check-duplicate', [WorkforceController::class, 'checkDuplicate'])->name('check-duplicate');
    Route::get('/by-date', [WorkforceController::class, 'getByDate'])->name('by-date');
    Route::get('/export', [WorkforceController::class, 'export'])->name('export')->middleware('can.export');
    Route::get('/{workforce}', [WorkforceController::class, 'show'])->name('show');

    // Write actions (Hanya Admin Management)
    Route::middleware(['admin'])->group(function () {
        Route::post('/batch', [WorkforceController::class, 'storeBatch'])->name('store-batch');
        Route::post('/units', [WorkforceController::class, 'storeUnit'])->name('units.store');
        Route::put('/units/{unit}', [WorkforceController::class, 'updateUnit'])->name('units.update');
        Route::patch('/units/{unit}/toggle', [WorkforceController::class, 'toggleUnit'])->name('units.toggle');
        Route::post('/', [WorkforceController::class, 'store'])->name('store');
        Route::put('/{workforce}', [WorkforceController::class, 'update'])->name('update');
        Route::delete('/{workforce}', [WorkforceController::class, 'destroy'])->name('destroy');
        Route::post('/destroy-bulk', [WorkforceController::class, 'destroyBulk'])->name('destroy-bulk');
    });
});

/*
|--------------------------------------------------------------------------
| Man Hours / Manhour (Terintegrasi dengan Workforce / Manpower)
|--------------------------------------------------------------------------
*/

Route::prefix('man-hours')->name('man-hours.')->middleware(['auth', 'not.inventory'])->group(function () {
    // Read & Export (Semua Role)
    Route::get('/', [ManHoursController::class, 'index'])->name('index');
    Route::get('/by-date', [ManHoursController::class, 'getByDate'])->name('by-date');
    Route::get('/export', [ManHoursController::class, 'export'])->name('export')->middleware('can.export');
    Route::get('/export-excel', [ManHoursController::class, 'exportExcel'])->name('export-excel')->middleware('can.export');

    // Write actions (Hanya Admin Management)
    Route::middleware(['admin'])->group(function () {
        Route::post('/batch', [ManHoursController::class, 'storeBatch'])->name('store-batch');
        Route::delete('/date/{date}', [ManHoursController::class, 'destroyDate'])->name('destroy-date');
        Route::post('/units', [ManHoursController::class, 'storeUnit'])->name('units.store');
        Route::put('/units/{unit}', [ManHoursController::class, 'updateUnit'])->name('units.update');
        Route::patch('/units/{unit}/toggle', [ManHoursController::class, 'toggleUnit'])->name('units.toggle');
        Route::put('/units/{unit}/accumulated-hours', [ManHoursController::class, 'updateUnitAccumulatedHours'])->name('units.accumulated-hours');
        Route::post('/units/{unit}/accumulated-hours', [ManHoursController::class, 'updateUnitAccumulatedHours'])->name('units.accumulated-hours.post');
        Route::put('/{man_hour}', [ManHoursController::class, 'update'])->name('update');
    });

    Route::get('/{man_hour}', [ManHoursController::class, 'show'])->name('show');
});

/*
|--------------------------------------------------------------------------
| Incident
|--------------------------------------------------------------------------
*/

Route::prefix('incident')->name('incident.')->middleware(['auth', 'not.inventory'])->group(function () {
    // Read & Export (Semua Role)
    Route::get('/', [IncidentController::class, 'index'])->name('index');
    Route::get('/umum', [IncidentController::class, 'umum'])->name('umum');
    Route::get('/ebtke', [IncidentController::class, 'ebtke'])->name('ebtke');
    Route::get('/export', [IncidentController::class, 'export'])->name('export')->middleware('can.export');
    Route::get('/export-pdf', [IncidentController::class, 'export'])->name('export-pdf')->middleware('can.export');
    Route::get('/documents/{attachmentId}/view', [IncidentController::class, 'viewDocument'])->name('documents.view');
    Route::get('/{incident}/legacy-document', [IncidentController::class, 'viewLegacyDocument'])->name('legacy-document');

    // Write actions (Hanya Admin Management)
    Route::middleware(['admin'])->group(function () {
        Route::post('/', [IncidentController::class, 'store'])->name('store');
        Route::put('/{incident}', [IncidentController::class, 'update'])->name('update');
        Route::delete('/{incident}', [IncidentController::class, 'destroy'])->name('destroy');
    });
});

/*
|--------------------------------------------------------------------------
| Issue & Follow Up
|--------------------------------------------------------------------------
*/

Route::prefix('issue-follow-up')->name('issue-follow-up.')->middleware(['auth', 'not.inventory'])->group(function () {
    // Read & Export (Semua Role)
    Route::get('/', [IssueFollowUpController::class, 'index'])->name('index');
    Route::get('/export', [IssueFollowUpController::class, 'export'])->name('export')->middleware('can.export');
    Route::get('/sources', [IssueFollowUpController::class, 'getSources'])->name('sources.index');
    // Documentations & Attachments View & Download (Authenticated non-inventory roles)
    Route::get('/documentations/{docId}/view', [IssueFollowUpController::class, 'viewDocumentation'])->name('documentation.view');
    Route::get('/documentations/{docId}/download', [IssueFollowUpController::class, 'downloadDocumentation'])->name('documentation.download');
    Route::get('/documentations/{docId}/view-alias', [IssueFollowUpController::class, 'viewDocumentation'])->name('documentations.view');
    Route::get('/documentations/{docId}/download-alias', [IssueFollowUpController::class, 'downloadDocumentation'])->name('documentations.download');
    Route::get('/{issue_follow_up}/attachment', [IssueFollowUpController::class, 'viewAttachment'])->name('attachment.view');
    Route::get('/logs/{logId}/attachment', [IssueFollowUpController::class, 'viewLogAttachment'])->name('logs.attachment.view');

    // Follow-up & Status Actions (Authenticated non-inventory users)
    Route::post('/{issue_follow_up}/follow-up', [IssueFollowUpController::class, 'addFollowUp'])->name('follow-up');
    Route::match(['post', 'patch'], '/{issue_follow_up}/close', [IssueFollowUpController::class, 'close'])->name('close');
    Route::match(['post', 'patch'], '/{issue_follow_up}/reopen', [IssueFollowUpController::class, 'reopen'])->name('reopen');

    // Admin-only Management Actions
    Route::middleware(['admin'])->group(function () {
        Route::get('/create', [IssueFollowUpController::class, 'create'])->name('create');
        Route::post('/', [IssueFollowUpController::class, 'store'])->name('store');
        Route::post('/sources', [IssueFollowUpController::class, 'storeSource'])->name('sources.store');
        Route::delete('/sources/{nameOrId}', [IssueFollowUpController::class, 'destroySource'])->name('sources.destroy');
        Route::post('/{issue_follow_up}/documentations', [IssueFollowUpController::class, 'storeDocumentation'])->name('documentation.store');
        Route::post('/{issue_follow_up}/documentations-alias', [IssueFollowUpController::class, 'storeDocumentation'])->name('documentations.store');
        Route::delete('/documentations/{docId}', [IssueFollowUpController::class, 'destroyDocumentation'])->name('documentation.destroy');
        Route::delete('/documentations/{docId}/alias', [IssueFollowUpController::class, 'destroyDocumentation'])->name('documentations.destroy');
        Route::get('/{issue_follow_up}/edit', [IssueFollowUpController::class, 'edit'])->name('edit');
        Route::match(['put', 'patch'], '/{issue_follow_up}', [IssueFollowUpController::class, 'update'])->name('update');
        Route::delete('/{issue_follow_up}', [IssueFollowUpController::class, 'destroy'])->name('destroy');
    });

    // Detail Temuan (setelah rute spesifik)
    Route::get('/{issue_follow_up}', [IssueFollowUpController::class, 'show'])->name('show');
});

/*
|--------------------------------------------------------------------------
| User Management (Manajemen Pengguna - Hanya Admin Management)
|--------------------------------------------------------------------------
*/

Route::prefix('users')->name('users.')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/', [UserController::class, 'index'])->name('index');
    Route::get('/create', [UserController::class, 'create'])->name('create');
    Route::post('/', [UserController::class, 'store'])->name('store');
    Route::get('/activities/all', [UserController::class, 'allActivities'])->name('activities.all');
    Route::get('/{user}/activities', [UserController::class, 'activities'])->name('activities');
    Route::get('/{user}/edit', [UserController::class, 'edit'])->name('edit');
    Route::put('/{user}', [UserController::class, 'update'])->name('update');
    Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy');
    Route::get('/{user}', [UserController::class, 'show'])->name('show');
});

/*
|--------------------------------------------------------------------------
| Monitoring Anggaran (HSE & Safeguard Budget Monitoring)
|--------------------------------------------------------------------------
*/

Route::prefix('monitoring-anggaran')->name('budget-monitoring.')->middleware(['auth', 'not.inventory'])->group(function () {
    // Read & Export (Semua Role: Admin Management, User)
    Route::get('/', [BudgetMonitoringController::class, 'index'])->name('index');
    Route::get('/wbs/{wbs}', [BudgetMonitoringController::class, 'showWbs'])->name('wbs.show');
    Route::get('/export-pdf', [BudgetMonitoringController::class, 'exportPdf'])->name('export-pdf')->middleware('can.export');
    Route::get('/export-excel', [BudgetMonitoringController::class, 'exportExcel'])->name('export-excel')->middleware('can.export');
    Route::get('/files/{type}/{id}/{index}', [BudgetMonitoringController::class, 'viewDocument'])->name('files.show');

    // Admin Management Only Actions
    Route::middleware(['admin'])->group(function () {
        // WBS
        Route::post('/wbs', [BudgetMonitoringController::class, 'storeWbs'])->name('wbs.store');
        Route::put('/wbs/{wbs}', [BudgetMonitoringController::class, 'updateWbs'])->name('wbs.update');
        Route::delete('/wbs/{wbs}', [BudgetMonitoringController::class, 'destroyWbs'])->name('wbs.destroy');
        Route::post('/wbs/{wbs}/adjust', [BudgetMonitoringController::class, 'adjustBudget'])->name('wbs.adjust');

        // Pengadaan / PO
        Route::post('/po', [BudgetMonitoringController::class, 'storePo'])->name('po.store');
        Route::put('/po/{po}', [BudgetMonitoringController::class, 'updatePo'])->name('po.update');
        Route::delete('/po/{po}', [BudgetMonitoringController::class, 'destroyPo'])->name('po.destroy');

        // Realisasi Pembayaran PO
        Route::post('/po/{po}/payments', [BudgetMonitoringController::class, 'storePoPayment'])->name('po-payment.store');
        Route::delete('/payments/{payment}', [BudgetMonitoringController::class, 'destroyPoPayment'])->name('po-payment.destroy');
        Route::delete('/po-payment/{payment}', [BudgetMonitoringController::class, 'destroyPoPayment']);

        // Realisasi Non-PO
        Route::post('/non-po', [BudgetMonitoringController::class, 'storeNonPo'])->name('non-po.store');
        Route::put('/non-po/{nonPo}', [BudgetMonitoringController::class, 'updateNonPo'])->name('non-po.update');
        Route::delete('/non-po/{nonPo}', [BudgetMonitoringController::class, 'destroyNonPo'])->name('non-po.destroy');

        // Pengalihan Anggaran Antar-WBS
        Route::post('/transfers', [BudgetMonitoringController::class, 'storeTransfer'])->name('transfers.store');
        Route::delete('/transfers/{transfer}', [BudgetMonitoringController::class, 'destroyTransfer'])->name('transfers.destroy');

        // Reset Data Monitoring Anggaran
        Route::post('/reset-data', [BudgetMonitoringController::class, 'resetData'])->name('reset-data');
    });
});

// Alias route untuk fleksibilitas URL
Route::get('/budget-monitoring', function () {
    return redirect()->route('budget-monitoring.index');
})->middleware(['auth', 'not.inventory']);

/*
|--------------------------------------------------------------------------
| Environment (Pengelolaan & Pemantauan Lingkungan)
|--------------------------------------------------------------------------
*/

Route::prefix('environment')->name('environment.')->middleware(['auth', 'not.inventory'])->group(function () {
    // Ringkasan Utama / Dashboard Lingkungan
    Route::get('/', [EnvironmentController::class, 'index'])->name('index');
    Route::get('/export-pdf', [EnvironmentController::class, 'overviewExportPdf'])->name('export-pdf')->middleware('can.export');
    Route::get('/files/{module}/{id}/{index}', [EnvironmentController::class, 'viewDocument'])->name('files.show');

    // 1. LB3
    Route::get('/lb3', [EnvironmentController::class, 'lb3Index'])->name('lb3.index');
    Route::get('/lb3/export-pdf', [EnvironmentController::class, 'lb3ExportPdf'])->name('lb3.export-pdf')->middleware('can.export');
    Route::get('/lb3/export-excel', [EnvironmentController::class, 'lb3ExportExcel'])->name('lb3.export-excel')->middleware('can.export');

    // 2. Non-LB3
    Route::get('/non-lb3', [EnvironmentController::class, 'nonLb3Index'])->name('non-lb3.index');
    Route::get('/non-lb3/export-pdf', [EnvironmentController::class, 'nonLb3ExportPdf'])->name('non-lb3.export-pdf')->middleware('can.export');
    Route::get('/non-lb3/export-excel', [EnvironmentController::class, 'nonLb3ExportExcel'])->name('non-lb3.export-excel')->middleware('can.export');

    // 3. RKL–RPL
    Route::get('/rkl-rpl', [EnvironmentController::class, 'rklRplIndex'])->name('rkl-rpl.index');
    Route::get('/rkl-rpl/export-pdf', [EnvironmentController::class, 'rklRplExportPdf'])->name('rkl-rpl.export-pdf')->middleware('can.export');
    Route::get('/rkl-rpl/export-excel', [EnvironmentController::class, 'rklRplExportExcel'])->name('rkl-rpl.export-excel')->middleware('can.export');

    // 4. Inspeksi (Sebelumnya PPKH)
    Route::get('/inspeksi', [EnvironmentController::class, 'inspeksiIndex'])->name('inspeksi.index');
    Route::get('/inspeksi/export-pdf', [EnvironmentController::class, 'inspeksiExportPdf'])->name('inspeksi.export-pdf')->middleware('can.export');
    Route::get('/inspeksi/export-excel', [EnvironmentController::class, 'inspeksiExportExcel'])->name('inspeksi.export-excel')->middleware('can.export');

    // Legacy PPKH & PPICH routes alias
    Route::get('/ppkh', [EnvironmentController::class, 'inspeksiIndex'])->name('ppkh.index');
    Route::get('/ppkh/export-pdf', [EnvironmentController::class, 'inspeksiExportPdf'])->name('ppkh.export-pdf')->middleware('can.export');
    Route::get('/ppkh/export-excel', [EnvironmentController::class, 'inspeksiExportExcel'])->name('ppkh.export-excel')->middleware('can.export');
    Route::get('/ppich', [EnvironmentController::class, 'inspeksiIndex'])->name('ppich.index');
    Route::get('/ppich/export-pdf', [EnvironmentController::class, 'inspeksiExportPdf'])->name('ppich.export-pdf')->middleware('can.export');
    Route::get('/ppich/export-excel', [EnvironmentController::class, 'inspeksiExportExcel'])->name('ppich.export-excel')->middleware('can.export');

    // 5. Energi (BBM & Listrik)
    Route::get('/energi', [EnvironmentController::class, 'energyIndex'])->name('energi.index');
    Route::get('/energi/export-pdf', [EnvironmentController::class, 'energyExportPdf'])->name('energi.export-pdf')->middleware('can.export');
    Route::get('/energi/export-excel', [EnvironmentController::class, 'energyExportExcel'])->name('energi.export-excel')->middleware('can.export');

    // 6. EWS (Early Warning System)
    Route::get('/ews', [EnvironmentController::class, 'ewsIndex'])->name('ews.index');
    Route::get('/ews/export-pdf', [EnvironmentController::class, 'ewsExportPdf'])->name('ews.export-pdf')->middleware('can.export');
    Route::get('/ews/export-excel', [EnvironmentController::class, 'ewsExportExcel'])->name('ews.export-excel')->middleware('can.export');

    // 7. Cuaca (Pencatatan Parameter Cuaca Terpisah)
    Route::get('/cuaca', [EnvironmentController::class, 'cuacaIndex'])->name('cuaca.index');
    Route::get('/cuaca/export-pdf', [EnvironmentController::class, 'cuacaExportPdf'])->name('cuaca.export-pdf')->middleware('can.export');
    Route::get('/cuaca/export-excel', [EnvironmentController::class, 'cuacaExportExcel'])->name('cuaca.export-excel')->middleware('can.export');

    // Write actions (Hanya Admin Management)
    Route::middleware(['admin'])->group(function () {
        // LB3 CRUD
        Route::post('/lb3', [EnvironmentController::class, 'lb3Store'])->name('lb3.store');
        Route::put('/lb3/{record}', [EnvironmentController::class, 'lb3Update'])->name('lb3.update');
        Route::delete('/lb3/{record}', [EnvironmentController::class, 'lb3Destroy'])->name('lb3.destroy');

        // Non-LB3 CRUD
        Route::post('/non-lb3', [EnvironmentController::class, 'nonLb3Store'])->name('non-lb3.store');
        Route::put('/non-lb3/{record}', [EnvironmentController::class, 'nonLb3Update'])->name('non-lb3.update');
        Route::delete('/non-lb3/{record}', [EnvironmentController::class, 'nonLb3Destroy'])->name('non-lb3.destroy');

        // RKL-RPL CRUD
        Route::post('/rkl-rpl', [EnvironmentController::class, 'rklRplStore'])->name('rkl-rpl.store');
        Route::put('/rkl-rpl/{record}', [EnvironmentController::class, 'rklRplUpdate'])->name('rkl-rpl.update');
        Route::delete('/rkl-rpl/{record}', [EnvironmentController::class, 'rklRplDestroy'])->name('rkl-rpl.destroy');

        // Inspeksi CRUD
        Route::post('/inspeksi', [EnvironmentController::class, 'inspeksiStore'])->name('inspeksi.store');
        Route::put('/inspeksi/{record}', [EnvironmentController::class, 'inspeksiUpdate'])->name('inspeksi.update');
        Route::delete('/inspeksi/{record}', [EnvironmentController::class, 'inspeksiDestroy'])->name('inspeksi.destroy');

        // Legacy PPKH & PPICH CRUD alias
        Route::post('/ppkh', [EnvironmentController::class, 'ppkhStore'])->name('ppkh.store');
        Route::put('/ppkh/{record}', [EnvironmentController::class, 'ppkhUpdate'])->name('ppkh.update');
        Route::delete('/ppkh/{record}', [EnvironmentController::class, 'ppkhDestroy'])->name('ppkh.destroy');
        Route::post('/ppich', [EnvironmentController::class, 'ppkhStore'])->name('ppich.store');
        Route::put('/ppich/{record}', [EnvironmentController::class, 'ppkhUpdate'])->name('ppich.update');
        Route::delete('/ppich/{record}', [EnvironmentController::class, 'ppkhDestroy'])->name('ppich.destroy');

        // Energi CRUD
        Route::post('/energi', [EnvironmentController::class, 'energyStore'])->name('energi.store');
        Route::put('/energi/{record}', [EnvironmentController::class, 'energyUpdate'])->name('energi.update');
        Route::delete('/energi/{record}', [EnvironmentController::class, 'energyDestroy'])->name('energi.destroy');

        // EWS CRUD
        Route::post('/ews', [EnvironmentController::class, 'ewsStore'])->name('ews.store');
        Route::put('/ews/{record}', [EnvironmentController::class, 'ewsUpdate'])->name('ews.update');
        Route::delete('/ews/{record}', [EnvironmentController::class, 'ewsDestroy'])->name('ews.destroy');

        // Cuaca CRUD
        Route::post('/cuaca', [EnvironmentController::class, 'cuacaStore'])->name('cuaca.store');
        Route::put('/cuaca/{record}', [EnvironmentController::class, 'cuacaUpdate'])->name('cuaca.update');
        Route::delete('/cuaca/{record}', [EnvironmentController::class, 'cuacaDestroy'])->name('cuaca.destroy');

        // Master Data CRUD
        Route::get('/master', [EnvironmentController::class, 'masterIndex'])->name('master.index');
        Route::post('/master', [EnvironmentController::class, 'masterStore'])->name('master.store');
        Route::put('/master/{master}', [EnvironmentController::class, 'masterUpdate'])->name('master.update');
        Route::post('/master/{master}/toggle', [EnvironmentController::class, 'masterToggle'])->name('master.toggle');
        Route::delete('/master/{master}', [EnvironmentController::class, 'masterDestroy'])->name('master.destroy');
    });
});


