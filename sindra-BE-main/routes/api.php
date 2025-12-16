<?php

use App\Http\Controllers\Api\AssetController;
use App\Http\Controllers\Api\AssigneeController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\InstansiController;
use App\Http\Controllers\Api\KnowledgeBaseController;
use App\Http\Controllers\Api\TicketCategoryController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\FaqController;
use App\Http\Controllers\Api\GrafikController;
use App\Http\Controllers\Api\RequestChangeController;
use App\Http\Controllers\Api\TicketController;
use App\Http\Controllers\Api\TicketDiscussionController;
use App\Http\Controllers\Api\TicketEscalateController;
use App\Http\Controllers\Api\TicketExportController;
use App\Http\Controllers\Api\TicketLogController;
use App\Http\Controllers\Api\TicketReopenController;
use App\Http\Controllers\CalenderController;
use App\Http\Controllers\TicketFeedbackController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
Route::post('/resend-otp', [AuthController::class, 'resendOtp']);
Route::post('/resend-otp/forgot-password', [AuthController::class, 'sendForgotPassword']);
Route::post('/check-otp/forgot-password', [AuthController::class, 'checkOTP']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);



Route::get('/sso/redirect', [AuthController::class, 'redirectSSO'])->name('sso.callback');
Route::post('/request-changes/callback', [RequestChangeController::class, 'callback']);

Route::get('/knowledge-bases', [KnowledgeBaseController::class, 'index']);
Route::get('/knowledge-bases/{id}', [KnowledgeBaseController::class, 'show']);
Route::get('/assets', [AssetController::class, 'getAllAssets']);


Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/resend-verification', [AuthController::class, 'resendVerificationEmail']);

    Route::get('/tickets/reports/count', [TicketController::class, 'getReportTickets']);
    Route::get('/tickets/reports/opd', [TicketController::class, 'getPerOpdTicketReport']);
    Route::get('/ticket/{ticketId}/discussions', [TicketDiscussionController::class, 'index']);
    Route::post('/ticket/{ticketId}/discussions', [TicketDiscussionController::class, 'store']);
    Route::delete('/ticket/discussions/{id}', [TicketDiscussionController::class, 'destroy']);
    Route::get('ticket/counts', [TicketController::class, 'getTicketCountSummary']);

    Route::post('/request-changes/{id}/send', [RequestChangeController::class, 'sendToConfig']);

    Route::get('/calender', [CalenderController::class, 'index']);
    Route::get('/calender/reports/count', [CalenderController::class, 'deadlineSummaryCount']);
    Route::get('/calender/performance/count', [CalenderController::class, 'performanceCount']);
    Route::get('/calender/today', [CalenderController::class, 'todayTask']);
    Route::get('/calender/{date}', [CalenderController::class, 'showByDate']);


    Route::get('/teknisi/count',[AssigneeController::class,'countTeknisi']);
    Route::get('/ticket/export/excel', [TicketExportController::class, 'exportExcel']);
    Route::get('/ticket/export/pdf',   [TicketExportController::class, 'exportPdf']);

    Route::get('/grafik/sla-monitor',[GrafikController::class,'slaMonitor']);
    Route::get('/grafik/sla-compliance',[GrafikController::class,'slaCompliance']);
    Route::get('/grafik/sla-resolution',[GrafikController::class,'resollutionTrend']);




    Route::apiResource('/ticket/categories', TicketCategoryController::class)->names('ticket.categories');
    Route::apiResource('/ticket/reopen', TicketReopenController::class);
    
    Route::apiResource('ticket', TicketController::class);

    Route::apiResource('knowledge-bases', KnowledgeBaseController::class)
        ->except(['index', 'show']);
    Route::apiResource('categories', CategoryController::class);
    Route::apiResource('faqs', FaqController::class);
    Route::apiResource('/request-changes', RequestChangeController::class);
    Route::apiResource('/instansi', InstansiController::class);
    Route::apiResource('/ticket-escalates', TicketEscalateController::class);
    Route::apiResource('/ticket/feedback', TicketFeedbackController::class);

    Route::get('/ticket/logs/{ticketId}', [TicketLogController::class, 'index']);
    Route::post('/ticket/logs', [TicketLogController::class, 'store']);

    Route::get('/users', [UserController::class, 'index']);
});


Route::get('/get/teknisi', [AssigneeController::class, 'getTeknisi']);

Route::get('/get/admin-bidang', [AssigneeController::class, 'getAdminBidang']);
Route::get('/get/admin-seksi', [AssigneeController::class, 'getAdminSeksi']);



