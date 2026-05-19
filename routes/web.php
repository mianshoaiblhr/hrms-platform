<?php
// ============================================================
// routes/web.php - Application Routes
// ============================================================

use App\Core\Router;
use App\Middleware\AuthMiddleware;
use App\Middleware\GuestMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Middleware\MaintenanceMiddleware;

$router = new Router();


// Status / health check — no auth required
$router->get('/status', function() {
    ob_start();
    require dirname(__DIR__) . '/public/status.php';
    ob_end_flush();
    exit;
});

$router->get('/ping', function() {
    header('Content-Type: text/plain');
    echo 'OK';
    exit;
});

// ============================================================
// PUBLIC ROUTES (Guest only)
// ============================================================
$router->group(['prefix' => '', 'middleware' => [GuestMiddleware::class]], function(Router $r) {
    $r->get('/login',           'AuthController@loginForm');
    $r->post('/login',          'AuthController@login');
    $r->get('/forgot-password', 'AuthController@forgotPasswordForm');
    $r->post('/forgot-password','AuthController@forgotPassword');
    $r->get('/reset-password',  'AuthController@resetPasswordForm');
    $r->post('/reset-password', 'AuthController@resetPassword');
});

// Maintenance page
$router->get('/maintenance', 'SystemController@maintenance');

// ============================================================
// AUTHENTICATED ROUTES
// ============================================================
$router->group(['middleware' => [AuthMiddleware::class, MaintenanceMiddleware::class]], function(Router $r) {

    // Auth
    $r->post('/logout',          'AuthController@logout',         [CsrfMiddleware::class]);
    $r->get('/change-password',  'AuthController@changePasswordForm');
    $r->post('/change-password', 'AuthController@changePassword', [CsrfMiddleware::class]);

    // Dashboard
    $r->get('/',          'DashboardController@index');
    $r->get('/dashboard', 'DashboardController@index');

    // --------------------------------------------------------
    // EMPLOYEE MANAGEMENT
    // --------------------------------------------------------
    $r->group(['prefix' => '/employees'], function(Router $r) {
        $r->get('/',                    'EmployeeController@index');
        $r->get('/create',              'EmployeeController@create');
        $r->post('/',                   'EmployeeController@store',    [CsrfMiddleware::class]);
        $r->get('/{id}',                'EmployeeController@show');
        $r->get('/{id}/edit',           'EmployeeController@edit');
        $r->post('/{id}/update',        'EmployeeController@update',   [CsrfMiddleware::class]);
        $r->post('/{id}/terminate',     'EmployeeController@terminate',[CsrfMiddleware::class]);
        $r->get('/export',              'EmployeeController@export');
        $r->get('/{id}/payslips',       'EmployeeController@payslips');
    });

    // --------------------------------------------------------
    // ATTENDANCE MANAGEMENT
    // --------------------------------------------------------
    $r->group(['prefix' => '/attendance'], function(Router $r) {
        $r->get('/',                    'AttendanceController@index');
        $r->get('/mark',                'AttendanceController@markForm');
        $r->post('/mark',               'AttendanceController@mark',      [CsrfMiddleware::class]);
        $r->post('/bulk-import',        'AttendanceController@bulkImport', [CsrfMiddleware::class]);
        $r->get('/monthly',             'AttendanceController@monthly');
        $r->get('/report',              'AttendanceController@report');
        $r->get('/export',              'AttendanceController@export');
    });

    // --------------------------------------------------------
    // LEAVE MANAGEMENT
    // --------------------------------------------------------
    $r->group(['prefix' => '/leaves'], function(Router $r) {
        $r->get('/',                    'LeaveController@index');
        $r->get('/apply',               'LeaveController@applyForm');
        $r->post('/apply',              'LeaveController@apply',      [CsrfMiddleware::class]);
        $r->get('/calendar',            'LeaveController@calendar');
        $r->get('/{id}',                'LeaveController@show');
        $r->post('/{id}/approve',       'LeaveController@approve',    [CsrfMiddleware::class]);
        $r->post('/{id}/reject',        'LeaveController@reject',     [CsrfMiddleware::class]);
        $r->post('/{id}/cancel',        'LeaveController@cancel',     [CsrfMiddleware::class]);
        $r->get('/balances',            'LeaveController@balances');
    });

    // --------------------------------------------------------
    // PAYROLL MANAGEMENT
    // --------------------------------------------------------
    $r->group(['prefix' => '/payroll'], function(Router $r) {
        $r->get('/',                                'PayrollController@index');
        $r->post('/period/create',                  'PayrollController@createPeriod', [CsrfMiddleware::class]);
        $r->get('/{id}',                            'PayrollController@show');
        $r->post('/{id}/process',                   'PayrollController@process',  [CsrfMiddleware::class]);
        $r->post('/{id}/approve',                   'PayrollController@approve',  [CsrfMiddleware::class]);
        $r->get('/{periodId}/payslip/{employeeId}', 'PayrollController@payslip');
        $r->get('/{id}/export',                     'PayrollController@export');
        $r->post('/{id}/disburse',                  'PayrollController@disburse', [CsrfMiddleware::class]);
    });

    // Salary Structure
    $r->group(['prefix' => '/salary'], function(Router $r) {
        $r->get('/structure',           'SalaryController@structure');
        $r->get('/structure/{empId}',   'SalaryController@employeeStructure');
        $r->post('/structure/{empId}',  'SalaryController@saveStructure', [CsrfMiddleware::class]);
        $r->get('/components',          'SalaryController@components');
        $r->post('/components',         'SalaryController@storeComponent',[CsrfMiddleware::class]);
    });

    // --------------------------------------------------------
    // DOCUMENTS
    // --------------------------------------------------------
    $r->group(['prefix' => '/documents'], function(Router $r) {
        $r->get('/',                'DocumentController@index');
        $r->get('/upload',          'DocumentController@uploadForm');
        $r->post('/upload',         'DocumentController@upload',   [CsrfMiddleware::class]);
        $r->get('/{id}/download',   'DocumentController@download');
        $r->get('/{id}',            'DocumentController@show');
        $r->post('/{id}/delete',    'DocumentController@delete',   [CsrfMiddleware::class]);
    });

    // --------------------------------------------------------
    // TASKS
    // --------------------------------------------------------
    $r->group(['prefix' => '/tasks'], function(Router $r) {
        $r->get('/',                'TaskController@index');
        $r->get('/create',          'TaskController@create');
        $r->post('/',               'TaskController@store',  [CsrfMiddleware::class]);
        $r->get('/{id}',            'TaskController@show');
        $r->post('/{id}/update',    'TaskController@update', [CsrfMiddleware::class]);
        $r->post('/{id}/comment',   'TaskController@comment',[CsrfMiddleware::class]);
        $r->post('/{id}/close',     'TaskController@close',  [CsrfMiddleware::class]);
    });

    // --------------------------------------------------------
    // REPORTS
    // --------------------------------------------------------
    $r->group(['prefix' => '/reports'], function(Router $r) {
        $r->get('/',                'ReportController@index');
        $r->get('/payroll',         'ReportController@payroll');
        $r->get('/attendance',      'ReportController@attendance');
        $r->get('/employees',       'ReportController@employees');
        $r->get('/tax',             'ReportController@tax');
        $r->get('/eobi',            'ReportController@eobi');
        $r->get('/leaves',          'ReportController@leaves');
        $r->post('/generate',       'ReportController@generate',  [CsrfMiddleware::class]);
    });

    // --------------------------------------------------------
    // AUDIT LOGS
    // --------------------------------------------------------
    $r->get('/audit',              'AuditController@index');
    $r->get('/audit/login-logs',   'AuditController@loginLogs');
    $r->get('/audit/export',       'AuditController@export');

    // --------------------------------------------------------
    // USER MANAGEMENT
    // --------------------------------------------------------
    $r->group(['prefix' => '/users'], function(Router $r) {
        $r->get('/',                'UserController@index');
        $r->get('/create',          'UserController@create');
        $r->post('/',               'UserController@store',   [CsrfMiddleware::class]);
        $r->get('/{id}/edit',       'UserController@edit');
        $r->post('/{id}/update',    'UserController@update',  [CsrfMiddleware::class]);
        $r->post('/{id}/toggle',    'UserController@toggle',  [CsrfMiddleware::class]);
        $r->post('/{id}/force-logout','UserController@forceLogout',[CsrfMiddleware::class]);
        $r->post('/{id}/reset-password','UserController@resetPassword',[CsrfMiddleware::class]);
    });

    // --------------------------------------------------------
    // ROLES & PERMISSIONS
    // --------------------------------------------------------
    $r->group(['prefix' => '/roles'], function(Router $r) {
        $r->get('/',                'RoleController@index');
        $r->get('/create',          'RoleController@create');
        $r->post('/',               'RoleController@store',        [CsrfMiddleware::class]);
        $r->get('/{id}/edit',       'RoleController@edit');
        $r->post('/{id}/update',    'RoleController@update',       [CsrfMiddleware::class]);
        $r->post('/{id}/delete',    'RoleController@delete',       [CsrfMiddleware::class]);
        $r->post('/{id}/permissions','RoleController@updatePermissions',[CsrfMiddleware::class]);
    });

    // --------------------------------------------------------
    // SETTINGS
    // --------------------------------------------------------
    $r->group(['prefix' => '/settings'], function(Router $r) {
        $r->get('/',                'SettingsController@index');
        $r->get('/company',         'SettingsController@company');
        $r->post('/company',        'SettingsController@updateCompany',  [CsrfMiddleware::class]);
        $r->get('/payroll',         'SettingsController@payroll');
        $r->post('/payroll',        'SettingsController@updatePayroll',  [CsrfMiddleware::class]);
        $r->get('/security',        'SettingsController@security');
        $r->post('/security',       'SettingsController@updateSecurity', [CsrfMiddleware::class]);
        $r->get('/departments',     'SettingsController@departments');
        $r->get('/designations',    'SettingsController@designations');
        $r->get('/shifts',          'SettingsController@shifts');
        $r->get('/holidays',        'SettingsController@holidays');
        $r->get('/leave-types',     'SettingsController@leaveTypes');
        $r->get('/backup',          'SettingsController@backup');
        $r->post('/backup/create',  'SettingsController@createBackup',   [CsrfMiddleware::class]);
    });

    // --------------------------------------------------------
    // PROFILE
    // --------------------------------------------------------
    $r->get('/profile',            'ProfileController@show');
    $r->post('/profile',           'ProfileController@update',          [CsrfMiddleware::class]);

    // --------------------------------------------------------
    // NOTIFICATIONS
    // --------------------------------------------------------
    $r->get('/notifications',          'NotificationController@index');
    $r->post('/notifications/read-all','NotificationController@readAll',    [CsrfMiddleware::class]);
    $r->get('/notifications/count',    'NotificationController@count');

    // --------------------------------------------------------
    // ADVANCES & LOANS
    // --------------------------------------------------------
    $r->group(['prefix' => '/advances'], function(Router $r) {
        $r->get('/',            'AdvanceController@index');
        $r->post('/apply',      'AdvanceController@apply',  [CsrfMiddleware::class]);
        $r->post('/{id}/approve','AdvanceController@approve',[CsrfMiddleware::class]);
        $r->post('/{id}/reject','AdvanceController@reject', [CsrfMiddleware::class]);
    });

    $r->group(['prefix' => '/loans'], function(Router $r) {
        $r->get('/',            'LoanController@index');
        $r->post('/apply',      'LoanController@apply',   [CsrfMiddleware::class]);
        $r->post('/{id}/approve','LoanController@approve',[CsrfMiddleware::class]);
    });

});

return $router;
