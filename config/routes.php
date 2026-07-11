<?php
declare(strict_types=1);

use App\Controllers\AdminAttendanceController;
use App\Controllers\AdminAttendanceGalleryController;
use App\Controllers\AdminEventsController;
use App\Controllers\AdminImportController;
use App\Controllers\AdminLogsController;
use App\Controllers\AdminRegistrantsController;
use App\Controllers\AdminSignatureController;
use App\Controllers\AdvancedExportController;
use App\Controllers\AttendanceController;
use App\Controllers\AuthController;
use App\Controllers\ParticipantController;
use App\Controllers\RegisterController;
use App\Controllers\ReportController;
use App\Controllers\SampleCsvController;
use App\Controllers\ScanController;
use App\Controllers\SettingsController;
use App\Controllers\AdminExportPageController;
use App\Controllers\ExportController;

return [
    'register' => [
        'GET' => [RegisterController::class, 'show'],
    ],
    'register_submit' => [
        'POST' => [RegisterController::class, 'submit'],
        '_fallback' => static function (): void {
            header('Location: ?r=register');
            exit;
        },
    ],
    'register_success' => [
        'GET' => [RegisterController::class, 'success'],
    ],
    'scan' => [
        'GET' => [ScanController::class, 'show'],
    ],
    'api_participant' => [
        'GET' => [ParticipantController::class, 'getByUuidJson'],
    ],
    'attendance_submit' => [
        'POST' => [AttendanceController::class, 'submit'],
        '_guards' => ['staff'],
    ],
    'admin_login' => [
        'GET' => [AuthController::class, 'loginForm'],
    ],
    'admin_login_post' => [
        'POST' => [AuthController::class, 'login'],
    ],
    'admin_logout' => [
        'GET' => [AuthController::class, 'logout'],
    ],
    'admin_registrants' => [
        'GET' => [AdminRegistrantsController::class, 'list'],
        '_guards' => ['admin'],
    ],
    'admin_generate_qr' => [
        'POST' => [AdminRegistrantsController::class, 'generateQrBatch'],
        '_guards' => ['admin'],
    ],
    'admin_registrant_email' => [
        'POST' => [AdminRegistrantsController::class, 'sendQrEmail'],
        '_guards' => ['admin'],
    ],
    'admin_qr' => [
        'GET' => [AdminRegistrantsController::class, 'qrPreview'],
        '_guards' => ['admin'],
    ],
    'admin_attendance' => [
        'GET' => [AdminAttendanceController::class, 'list'],
        '_guards' => ['admin'],
    ],
    'admin_attendance_kpi' => [
        'GET' => [AdminAttendanceController::class, 'kpiJson'],
        '_guards' => ['admin'],
    ],
    'admin_attendance_kpi_stream' => [
        'GET' => [AdminAttendanceController::class, 'kpiStream'],
        '_guards' => ['admin'],
    ],
    'admin_attendance_search' => [
        'GET' => [AdminAttendanceController::class, 'searchParticipants'],
        '_guards' => ['admin'],
    ],
    'admin_attendance_manual' => [
        'POST' => [AdminAttendanceController::class, 'manualAttendance'],
        '_guards' => ['admin'],
    ],
    'admin_attendance_gallery' => [
        'GET' => [AdminAttendanceGalleryController::class, 'list'],
        '_guards' => ['admin'],
    ],
    'admin_settings' => [
        'GET' => [SettingsController::class, 'form'],
        '_guards' => ['admin'],
    ],
    'admin_settings_save' => [
        'POST' => [SettingsController::class, 'save'],
        '_guards' => ['admin'],
    ],
    'admin_import' => [
        'GET' => [AdminImportController::class, 'form'],
        '_guards' => ['admin'],
    ],
    'admin_import_preview' => [
        'POST' => [AdminImportController::class, 'preview'],
        '_guards' => ['admin'],
    ],
    'admin_import_execute' => [
        'POST' => [AdminImportController::class, 'execute'],
        '_guards' => ['admin'],
    ],
    'admin_import_history' => [
        'GET' => [AdminImportController::class, 'history'],
        '_guards' => ['admin'],
    ],
    'admin_export' => [
        'GET' => [AdminExportPageController::class, 'index'],
        '_guards' => ['admin'],
    ],
    'admin_report' => [
        'GET' => [ReportController::class, 'form'],
        '_guards' => ['admin'],
    ],
    'admin_report_generate' => [
        'POST' => [ReportController::class, 'generate'],
        '_guards' => ['admin'],
    ],
    'admin_report_save' => [
        'POST' => [ReportController::class, 'saveTemplate'],
        '_guards' => ['admin'],
    ],
    'admin_report_load' => [
        'GET' => [ReportController::class, 'loadTemplate'],
        '_guards' => ['admin'],
    ],
    'admin_signature_replace' => [
        'POST' => [AdminSignatureController::class, 'replace'],
        '_guards' => ['admin'],
    ],
    'admin_signature_new' => [
        'POST' => [AdminSignatureController::class, 'addNew'],
        '_guards' => ['admin'],
    ],
    'admin_logs' => [
        'GET' => [AdminLogsController::class, 'list'],
        '_guards' => ['admin'],
    ],
    'admin_events' => [
        'GET' => [AdminEventsController::class, 'list'],
        '_guards' => ['admin'],
    ],
    'admin_events_create' => [
        'POST' => [AdminEventsController::class, 'create'],
        '_guards' => ['admin'],
    ],
    'admin_events_set_active' => [
        'POST' => [AdminEventsController::class, 'setActive'],
        '_guards' => ['admin'],
    ],
    'export_registrants_csv' => [
        'GET' => [ExportController::class, 'registrantsCsv'],
        '_guards' => ['admin'],
    ],
    'export_attendance_csv' => [
        'GET' => [ExportController::class, 'attendanceCsv'],
        '_guards' => ['admin'],
    ],
    'export_registrants_xlsx' => [
        'GET' => [AdvancedExportController::class, 'registrantsXlsx'],
        '_guards' => ['admin'],
    ],
    'export_attendance_xlsx' => [
        'GET' => [AdvancedExportController::class, 'attendanceXlsx'],
        '_guards' => ['admin'],
    ],
    'export_attendance_pdf' => [
        'GET' => [AdvancedExportController::class, 'attendancePdf'],
        '_guards' => ['admin'],
    ],
    'sample_csv' => [
        'GET' => [SampleCsvController::class, 'download'],
        '_guards' => ['admin'],
    ],
];

