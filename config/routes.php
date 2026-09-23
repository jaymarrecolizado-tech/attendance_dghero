<?php
declare(strict_types=1);

use App\Controllers\AdminAttendanceController;
use App\Controllers\AdminAttendanceGalleryController;
use App\Controllers\AdminEventsController;
use App\Controllers\AdminImportController;
use App\Controllers\AdminLogsController;
use App\Controllers\AdminRegistrantsController;
use App\Controllers\AdminSignatureController;
use App\Controllers\AdminSeoController;
use App\Controllers\AdminUsersController;
use App\Controllers\AdvancedExportController;
use App\Controllers\AdminCoaMonitorController;
use App\Controllers\AttendanceController;
use App\Controllers\AuthController;
use App\Controllers\EventBrandingController;
use App\Controllers\ParticipantController;
use App\Controllers\RegisterController;
use App\Controllers\ReportController;
use App\Controllers\SampleCsvController;
use App\Controllers\ScanController;
use App\Controllers\SettingsController;
use App\Controllers\AdminExportPageController;
use App\Controllers\ExportController;

$rolesAdmin = 'role:admin';
$rolesOps = 'role:admin|checker';
$rolesSeoRead = 'role:admin|seo_viewer';

// Event-scoped guards (All Father bypasses; staff need an assignment for the current event).
$eventOps = 'event:event_admin|checker';
$eventManage = 'event:event_admin';
$eventSeo = 'event:event_admin|seo_viewer';
$eventKpi = 'event:event_admin|checker|seo_viewer';

return [
    'register' => [
        'GET' => [RegisterController::class, 'show'],
    ],
    'register_submit' => [
        'POST' => [RegisterController::class, 'submit'],
        '_fallback' => static function (): void {
            $e = isset($_GET['e']) ? '&e=' . urlencode((string)$_GET['e']) : '';
            header('Location: ?r=register' . $e);
            exit;
        },
    ],
    'register_success' => [
        'GET' => [RegisterController::class, 'success'],
    ],
    'register_email_check' => [
        'GET' => [RegisterController::class, 'emailCheck'],
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
        '_guards' => [$eventOps],
    ],
    'admin_generate_qr' => [
        'POST' => [AdminRegistrantsController::class, 'generateQrBatch'],
        '_guards' => [$eventOps],
    ],
    'admin_registrant_email' => [
        'POST' => [AdminRegistrantsController::class, 'sendQrEmail'],
        '_guards' => [$eventOps],
    ],
    'admin_qr' => [
        'GET' => [AdminRegistrantsController::class, 'qrPreview'],
        '_guards' => [$eventOps],
    ],
    'event_branding' => [
        'GET' => [EventBrandingController::class, 'image'],
    ],
    'admin_event_theme' => [
        'POST' => [AdminEventsController::class, 'theme'],
        '_guards' => [$rolesAdmin],
    ],
    'admin_event_coa' => [
        'POST' => [AdminEventsController::class, 'coa'],
        '_guards' => [$rolesAdmin],
    ],
    'admin_coa_send' => [
        'POST' => [AdminRegistrantsController::class, 'resendCoa'],
        '_guards' => [$eventOps],
    ],
    'admin_coa_monitor' => [
        'GET' => [AdminCoaMonitorController::class, 'monitor'],
        '_guards' => [$rolesAdmin],
    ],
    'admin_coa_send_new' => [
        'POST' => [AdminCoaMonitorController::class, 'sendNew'],
        '_guards' => [$rolesAdmin],
    ],
    'admin_coa_queue_failed' => [
        'POST' => [AdminCoaMonitorController::class, 'queueFailed'],
        '_guards' => [$rolesAdmin],
    ],
    'admin_coa_resend_queued' => [
        'POST' => [AdminCoaMonitorController::class, 'resendQueued'],
        '_guards' => [$rolesAdmin],
    ],
    'admin_coa_preview' => [
        'GET' => [AdminCoaMonitorController::class, 'preview'],
        '_guards' => [$rolesAdmin],
    ],
    'admin_coa_preview_template' => [
        'GET' => [AdminCoaMonitorController::class, 'previewTemplate'],
        '_guards' => [$rolesAdmin],
    ],
    'admin_coa_templates' => [
        'GET' => [AdminCoaMonitorController::class, 'templates'],
        '_guards' => [$rolesAdmin],
    ],
    'admin_coa_template_save' => [
        'POST' => [AdminCoaMonitorController::class, 'templateSave'],
        '_guards' => [$rolesAdmin],
    ],
    'admin_coa_template_apply' => [
        'POST' => [AdminCoaMonitorController::class, 'templateApply'],
        '_guards' => [$rolesAdmin],
    ],
    'admin_coa_signatories' => [
        'GET' => [AdminCoaMonitorController::class, 'signatories'],
        '_guards' => [$rolesAdmin],
    ],
    'admin_coa_signatory_save' => [
        'POST' => [AdminCoaMonitorController::class, 'signatorySave'],
        '_guards' => [$rolesAdmin],
    ],
    'admin_coa_signatory_delete' => [
        'POST' => [AdminCoaMonitorController::class, 'signatoryDelete'],
        '_guards' => [$rolesAdmin],
    ],
    'admin_coa_signatory_image' => [
        'GET' => [AdminCoaMonitorController::class, 'signatoryImage'],
        '_guards' => [$rolesAdmin],
    ],
    'admin_coa_send_selected' => [
        'POST' => [AdminCoaMonitorController::class, 'sendSelected'],
        '_guards' => [$rolesAdmin],
    ],
    'admin_coa_cancel' => [
        'POST' => [AdminCoaMonitorController::class, 'cancelBatch'],
        '_guards' => [$rolesAdmin],
    ],
    'admin_coa_cancel_selected' => [
        'POST' => [AdminCoaMonitorController::class, 'cancelSelected'],
        '_guards' => [$rolesAdmin],
    ],
    'admin_coa_attendees' => [
        'GET' => [AdminCoaMonitorController::class, 'attendees'],
        '_guards' => [$rolesAdmin],
    ],
    'admin_registrant_vip' => [
        'POST' => [AdminRegistrantsController::class, 'toggleVip'],
        '_guards' => [$eventManage],
    ],
    'admin_attendance' => [
        'GET' => [AdminAttendanceController::class, 'list'],
        '_guards' => [$eventOps],
    ],
    'admin_attendance_kpi' => [
        'GET' => [AdminAttendanceController::class, 'kpiJson'],
        '_guards' => [$eventKpi],
    ],
    'admin_attendance_kpi_stream' => [
        'GET' => [AdminAttendanceController::class, 'kpiStream'],
        '_guards' => [$eventKpi],
    ],
    'admin_attendance_search' => [
        'GET' => [AdminAttendanceController::class, 'searchParticipants'],
        '_guards' => [$eventOps],
    ],
    'admin_attendance_manual' => [
        'POST' => [AdminAttendanceController::class, 'manualAttendance'],
        '_guards' => [$eventOps],
    ],
    'admin_attendance_mark_absent' => [
        'POST' => [AdminAttendanceController::class, 'markAbsent'],
        '_guards' => [$eventOps],
    ],
    'admin_attendance_clear_absent' => [
        'POST' => [AdminAttendanceController::class, 'clearAbsent'],
        '_guards' => [$eventOps],
    ],
    'admin_attendance_gallery' => [
        'GET' => [AdminAttendanceGalleryController::class, 'list'],
        '_guards' => [$eventManage],
    ],
    'admin_seo_dashboard' => [
        'GET' => [AdminSeoController::class, 'dashboard'],
        '_guards' => [$eventSeo],
    ],
    'admin_seo_summary' => [
        'GET' => [AdminSeoController::class, 'summaryJson'],
        '_guards' => [$eventSeo],
    ],
    'admin_seo_search' => [
        'GET' => [AdminSeoController::class, 'searchJson'],
        '_guards' => [$eventSeo],
    ],
    'admin_users' => [
        'GET' => [AdminUsersController::class, 'list'],
        '_guards' => [$rolesAdmin],
    ],
    'admin_users_create' => [
        'POST' => [AdminUsersController::class, 'create'],
        '_guards' => [$rolesAdmin],
    ],
    'admin_users_update' => [
        'POST' => [AdminUsersController::class, 'update'],
        '_guards' => [$rolesAdmin],
    ],
    'admin_settings' => [
        'GET' => [SettingsController::class, 'form'],
        '_guards' => [$rolesAdmin],
    ],
    'admin_settings_save' => [
        'POST' => [SettingsController::class, 'save'],
        '_guards' => [$rolesAdmin],
    ],
    'admin_import' => [
        'GET' => [AdminImportController::class, 'form'],
        '_guards' => [$eventManage],
    ],
    'admin_import_preview' => [
        'POST' => [AdminImportController::class, 'preview'],
        '_guards' => [$eventManage],
    ],
    'admin_import_execute' => [
        'POST' => [AdminImportController::class, 'execute'],
        '_guards' => [$eventManage],
    ],
    'admin_import_history' => [
        'GET' => [AdminImportController::class, 'history'],
        '_guards' => [$eventManage],
    ],
    'admin_export' => [
        'GET' => [AdminExportPageController::class, 'index'],
        '_guards' => [$eventManage],
    ],
    'admin_report' => [
        'GET' => [ReportController::class, 'form'],
        '_guards' => [$eventManage],
    ],
    'admin_report_generate' => [
        'POST' => [ReportController::class, 'generate'],
        '_guards' => [$eventManage],
    ],
    'admin_report_save' => [
        'POST' => [ReportController::class, 'saveTemplate'],
        '_guards' => [$eventManage],
    ],
    'admin_report_load' => [
        'GET' => [ReportController::class, 'loadTemplate'],
        '_guards' => [$eventManage],
    ],
    'admin_signature_replace' => [
        'POST' => [AdminSignatureController::class, 'replace'],
        '_guards' => [$eventOps],
    ],
    'admin_signature_new' => [
        'POST' => [AdminSignatureController::class, 'addNew'],
        '_guards' => [$eventOps],
    ],
    'admin_logs' => [
        'GET' => [AdminLogsController::class, 'list'],
        '_guards' => [$rolesAdmin],
    ],
    'admin_events' => [
        'GET' => [AdminEventsController::class, 'list'],
        '_guards' => [$rolesAdmin],
    ],
    'admin_event_links' => [
        'GET' => [AdminEventsController::class, 'links'],
        '_guards' => [$eventManage],
    ],
    'admin_events_create' => [
        'POST' => [AdminEventsController::class, 'create'],
        '_guards' => [$rolesAdmin],
    ],
    'admin_events_update' => [
        'POST' => [AdminEventsController::class, 'update'],
        '_guards' => [$rolesAdmin],
    ],
    'admin_events_switch' => [
        'POST' => [AdminEventsController::class, 'switch'],
        '_guards' => ['auth'],
    ],
    'admin_events_assign' => [
        'POST' => [AdminEventsController::class, 'assign'],
        '_guards' => [$rolesAdmin],
    ],
    'admin_events_unassign' => [
        'POST' => [AdminEventsController::class, 'unassign'],
        '_guards' => [$rolesAdmin],
    ],
    'export_registrants_csv' => [
        'GET' => [ExportController::class, 'registrantsCsv'],
        '_guards' => [$eventManage],
    ],
    'export_attendance_csv' => [
        'GET' => [ExportController::class, 'attendanceCsv'],
        '_guards' => [$eventManage],
    ],
    'export_registrants_xlsx' => [
        'GET' => [AdvancedExportController::class, 'registrantsXlsx'],
        '_guards' => [$eventManage],
    ],
    'export_attendance_xlsx' => [
        'GET' => [AdvancedExportController::class, 'attendanceXlsx'],
        '_guards' => [$eventManage],
    ],
    'export_attendance_pdf' => [
        'GET' => [AdvancedExportController::class, 'attendancePdf'],
        '_guards' => [$eventManage],
    ],
    'sample_csv' => [
        'GET' => [SampleCsvController::class, 'download'],
        '_guards' => [$eventManage],
    ],
];
