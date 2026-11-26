<?php

use App\Http\Controllers\AgentController;
use App\Http\Controllers\AgentOffRequestController;
use App\Http\Controllers\AgentScheduleController;
use App\Http\Controllers\AgentShiftSwapController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OffRequestController;
use App\Http\Controllers\OperationalTemplateController;
use App\Http\Controllers\PicScheduleGridController;
use App\Http\Controllers\PicShiftSwapController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SchedulePeriodController;
use App\Http\Controllers\ShiftController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])
        ->name('profile.edit');

    Route::patch('/profile', [ProfileController::class, 'update'])
        ->name('profile.update');

    Route::delete('/profile', [ProfileController::class, 'destroy'])
        ->name('profile.destroy');

    // Notifikasi (tersedia untuk semua role: PIC & Agent)
    Route::get('/notifications', [NotificationController::class, 'index'])
        ->name('notifications.index');
    // POST (bukan GET) karena menandai dibaca adalah aksi yang mengubah state;
    // link GET bisa ikut ter-trigger oleh prefetch browser tanpa disengaja.
    // Path markRead dipisah dari markOneRead supaya tidak dobel POST di path
    // yang sama (markRead = buka tujuan + tandai; markOneRead = tandai saja).
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markRead'])
        ->name('notifications.read');
    Route::post('/notifications/{id}/mark-read', [NotificationController::class, 'markOneRead'])
        ->name('notifications.read-one');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])
        ->name('notifications.read-all');
});

// ==================== Khusus PIC (Supervisor) ====================
// Semua fitur manajemen yang sudah ada sejak awal: Agent, Template
// Operasional, Off Request (sisi PIC), Generate Jadwal, History.
Route::middleware(['auth', 'pic'])->group(function () {
    Route::resource('agents', AgentController::class)
        ->except(['create', 'edit', 'show']); // pakai modal, bukan halaman terpisah

    Route::patch('/agents/{agent}/toggle-status', [AgentController::class, 'toggleStatus'])
        ->name('agents.toggle-status');

    Route::resource('shifts', ShiftController::class)
        ->except(['create', 'edit', 'show']); // pakai modal, bukan halaman terpisah

    Route::resource('operational-templates', OperationalTemplateController::class)
        ->except(['show']);

    Route::post('/operational-templates/{operationalTemplate}/duplicate', [OperationalTemplateController::class, 'duplicate'])
        ->name('operational-templates.duplicate');

    Route::get('/off-requests', [OffRequestController::class, 'index'])
        ->name('off-requests.index');

    Route::patch('/off-requests/{offRequest}/approval', [OffRequestController::class, 'updateApproval'])
        ->name('off-requests.approval');

    Route::post('/off-requests/pic-manual', [OffRequestController::class, 'storePicManual'])
        ->name('off-requests.pic-manual.store');

    Route::post('/off-requests/form-request', [OffRequestController::class, 'storeFormRequestPic'])
        ->name('off-requests.form-request.store');

    Route::delete('/off-requests/form-request/{offRequest}', [OffRequestController::class, 'destroyFormRequest'])
        ->name('off-requests.form-request.destroy');

    Route::delete('/off-requests/bulk', [OffRequestController::class, 'bulkDestroy'])
        ->name('off-requests.bulk-destroy');

    Route::delete('/off-requests/{offRequest}', [OffRequestController::class, 'destroy'])
        ->name('off-requests.destroy');

    Route::get('/schedules/generate', [SchedulePeriodController::class, 'wizard'])
        ->name('schedules.wizard');

    Route::post('/schedules/periods', [SchedulePeriodController::class, 'store'])
        ->name('schedules.periods.store');

    Route::post('/schedules/periods/{period}/requirements', [SchedulePeriodController::class, 'saveRequirement'])
        ->name('schedules.requirements.save');

    Route::get('/schedules/periods/{period}/check-complete', [SchedulePeriodController::class, 'checkComplete'])
        ->name('schedules.check-complete');

    Route::get('/schedules/periods/{period}/inbound-data', [SchedulePeriodController::class, 'getInboundData'])
        ->name('schedules.inbound.data');

    Route::post('/schedules/periods/{period}/inbound-assignments', [SchedulePeriodController::class, 'saveInboundAssignments'])
        ->name('schedules.inbound.save');

    Route::get('/schedules/periods/{period}/inbound-estimate', [SchedulePeriodController::class, 'getInboundEstimate'])
        ->name('schedules.inbound.estimate');

    Route::get('/schedules/periods/{period}/run', [SchedulePeriodController::class, 'showRunPage'])
        ->name('schedules.generate.run');

    Route::post('/schedules/periods/{period}/generate', [SchedulePeriodController::class, 'generate'])
        ->name('schedules.generate.process');

    Route::get('/schedules/periods/{period}/ga-status', [SchedulePeriodController::class, 'pollGaStatus'])
        ->name('schedules.ga-status');

    Route::post('/schedules/periods/{period}/finalize', [SchedulePeriodController::class, 'finalize'])
        ->name('schedules.finalize');

    Route::get('/schedules/periods/{period}/form-request-window', [SchedulePeriodController::class, 'showFormRequestWindowPage'])
        ->name('schedules.form-request-window.create');

    Route::post('/periods/{period}/form-request-window', [SchedulePeriodController::class, 'storeFormRequestWindow'])
        ->name('schedules.form-request-window.store');

    // Tukar Jadwal (Sisi PIC)
    Route::get('/shift-swaps', [PicShiftSwapController::class, 'index'])
        ->name('pic.shift-swaps.index');
    Route::post('/shift-swaps/{shiftSwap}/approve', [PicShiftSwapController::class, 'updateApproval'])
        ->name('pic.shift-swaps.update-approval');

    Route::get('/schedules/history', [SchedulePeriodController::class, 'history'])
        ->name('schedules.history');

    Route::get('/schedules/history/{period}', [SchedulePeriodController::class, 'historyShow'])
        ->name('schedules.history.show');

    Route::patch('/schedules/periods/{period}', [SchedulePeriodController::class, 'update'])
        ->name('schedules.periods.update');

    Route::delete('/schedules/periods/{period}', [SchedulePeriodController::class, 'destroy'])
        ->name('schedules.periods.destroy');

    Route::get('/schedules/periods/{period}/export-excel', [SchedulePeriodController::class, 'exportExcel'])
        ->name('schedules.export-excel');

    // Ubah Grid Jadwal (Sisi PIC) — drag & drop swap
    Route::get('/pic/schedules/{period}/grid', [PicScheduleGridController::class, 'grid'])
        ->name('pic.schedule-grid.edit');
    Route::get('/pic/schedules/{period}/grid/data', [PicScheduleGridController::class, 'gridData'])
        ->name('pic.schedule-grid.data');
    Route::post('/pic/schedules/{period}/grid/swap', [PicScheduleGridController::class, 'swap'])
        ->name('pic.schedule-grid.swap');
});

// ==================== Khusus Agent ====================
// Halaman self-service: lihat jadwal pribadi, ajukan/lihat off request
// pribadi (Form Request dan Prioritas Tinggi).
Route::middleware(['auth', 'agent'])->prefix('agent')->name('agent.')->group(function () {
    Route::get('/jadwal', [AgentScheduleController::class, 'index'])
        ->name('schedule.index');

    Route::get('/jadwal-tim', [AgentScheduleController::class, 'teamSchedule'])
        ->name('schedule.team');

    Route::get('/jadwal-tim/data', [AgentScheduleController::class, 'teamScheduleData'])
        ->name('schedule.team.data');

    // Off Requests (Sisi Agent)
    Route::get('/off-requests', [AgentOffRequestController::class, 'index'])
        ->name('off-requests.index');

    Route::post('/off-requests/form-request', [AgentOffRequestController::class, 'storeFormRequest'])
        ->name('off-requests.form-request.store');

    Route::delete('/off-requests/form-request/{offRequest}', [AgentOffRequestController::class, 'destroyFormRequest'])
        ->name('off-requests.form-request.destroy');

    Route::post('/off-requests/prioritas-tinggi', [AgentOffRequestController::class, 'storePrioritasTinggi'])
        ->name('off-requests.prioritas-tinggi.store');

    // Tukar Jadwal (Sisi Agent)
    Route::get('/shift-swaps', [AgentShiftSwapController::class, 'index'])
        ->name('shift-swaps.index');
    Route::get('/shift-swaps/create', [AgentShiftSwapController::class, 'create'])
        ->name('shift-swaps.create');
    Route::get('/shift-swaps/api/eligible-responders', [AgentShiftSwapController::class, 'getEligibleResponders'])
        ->name('shift-swaps.get-eligible-responders');
    Route::get('/shift-swaps/api/responder-schedules', [AgentShiftSwapController::class, 'getResponderSchedules'])
        ->name('shift-swaps.get-responder-schedules');
    Route::post('/shift-swaps', [AgentShiftSwapController::class, 'store'])
        ->name('shift-swaps.store');
    Route::post('/shift-swaps/{shiftSwap}/respond', [AgentShiftSwapController::class, 'respond'])
        ->name('shift-swaps.respond');
});

require __DIR__.'/auth.php';
