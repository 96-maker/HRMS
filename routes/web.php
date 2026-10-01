<?php
// routes/web.php

return [
    // Auth Routes
    'GET /login'            => 'AuthController@showLogin',
    'POST /login'           => 'AuthController@login',
    'POST /logout'          => 'AuthController@logout',
    'GET /profile'          => 'AuthController@profile',
    'POST /change-password' => 'AuthController@changePassword',

    // Dashboard
    'GET /'                 => 'DashboardController@index',
    'GET /dashboard'        => 'DashboardController@index',
    'GET /more'             => 'MoreController@index',

    // Employees
    'GET /employees'            => 'EmployeeController@index',
    'GET /employees/create'     => 'EmployeeController@create',
    'POST /employees'           => 'EmployeeController@store',
    'GET /employees/view'       => 'EmployeeController@show',
    'GET /employees/export-pdf' => 'EmployeeController@exportPdf',
    'GET /employees/edit'       => 'EmployeeController@edit',
    'POST /employees/update'    => 'EmployeeController@update',
    'POST /employees/delete'    => 'EmployeeController@destroy',

    // Departments & Positions
    'GET /departments'          => 'DepartmentController@index',
    'GET /departments/create'   => 'DepartmentController@create',
    'POST /departments'         => 'DepartmentController@store',
    'POST /departments/delete'  => 'DepartmentController@destroy',
    'GET /positions'            => 'PositionController@index',
    'GET /positions/create'     => 'PositionController@create',
    'POST /positions'           => 'PositionController@store',

    // Attendance
    'GET /attendance'           => 'AttendanceController@index',
    'POST /attendance/clock-in'  => 'AttendanceController@clockIn',
    'POST /attendance/clock-out' => 'AttendanceController@clockOut',
    'POST /attendance/override'  => 'AttendanceController@override',

    // Leave Engine
    'GET /leave'                => 'LeaveController@index',
    'GET /leave/apply'          => 'LeaveController@applyForm',
    'GET /leave/print-form'     => 'LeaveController@printForm',
    'POST /leave/apply'         => 'LeaveController@apply',
    'POST /leave/approve'       => 'LeaveController@approve',
    'POST /leave/reject'        => 'LeaveController@reject',
    'POST /leave/return-to-work'=> 'LeaveController@returnToWork',

    // Payroll & Payslips
    'GET /payroll'              => 'PayrollController@index',
    'GET /payroll/add-item'     => 'PayrollController@createItem',
    'POST /payroll/add-item'    => 'PayrollController@addItem',
    'GET /payroll/edit-item'    => 'PayrollController@editItem',
    'POST /payroll/edit-item'   => 'PayrollController@updateItem',
    'POST /payroll/update-item' => 'PayrollController@updateItem',
    'POST /payroll/reset-item'  => 'PayrollController@resetItem',
    'POST /payroll/process'     => 'PayrollController@process',
    'POST /payroll/unlock'      => 'PayrollController@unlock',
    'POST /payroll/populate-all'=> 'PayrollController@populateAll',
    'POST /payroll/remove-item' => 'PayrollController@removeItem',
    'GET /payroll/export-excel' => 'PayrollController@exportExcel',
    'GET /payroll/export-pdf'   => 'PayrollController@exportPdf',
    'GET /payroll/export-bank'  => 'PayrollController@exportBankFile',
    'GET /payroll/reports/statutory' => 'PayrollController@statutoryReports',
    'GET /payroll/reports/statutory-excel' => 'PayrollController@exportStatutoryExcel',
    'GET /payroll/view'         => 'PayrollController@show',
    'GET /payslips'             => 'PayrollController@payslips',

    // Document Repository
    'GET /documents'            => 'DocumentController@index',
    'GET /documents/upload'     => 'DocumentController@create',
    'POST /documents/upload'    => 'DocumentController@upload',
    'GET /documents/download'   => 'DocumentController@download',
    'POST /documents/delete'    => 'DocumentController@destroy',

    // Reports & Analytics
    'GET /reports'              => 'ReportController@index',
    'GET /reports/export-csv'   => 'ReportController@exportCsv',
    'GET /reports/export-excel' => 'ReportController@exportExcel',
    'GET /reports/export-pdf'   => 'ReportController@exportPdf',

    // Notifications
    'POST /notifications/mark-all-read' => 'AdminController@markAllNotificationsRead',

    // Admin & Roles Matrix
    'GET /users'                => 'AdminController@users',
    'GET /users/create'          => 'AdminController@createUser',
    'POST /users/invite'        => 'AdminController@inviteUser',
    'POST /users/reset-password' => 'AdminController@resetUserPassword',
    'POST /users/update-role'    => 'AdminController@updateUserRole',
    'POST /users/activate'       => 'AdminController@activateUser',
    'POST /users/delete'        => 'AdminController@deleteUser',
    'GET /roles'                => 'AdminController@roles',
    'POST /roles/permissions'   => 'AdminController@updatePermissions',

    // Internal Broadcasts & Circulars (SMS Alerts & Birthday Wishes)
    'GET /broadcasts'               => 'BroadcastController@index',
    'POST /broadcasts/send'          => 'BroadcastController@send',
    'POST /broadcasts/birthday-wishes' => 'BroadcastController@birthdayWishes',

    // System Settings & Tools Integrations
    'GET /settings'             => 'SettingsController@index',
    'POST /settings/update'     => 'SettingsController@update',
    'GET /tools'                => 'SettingsController@tools',
    'POST /tools/update'        => 'SettingsController@updateTools',
    'POST /tools/unlock-dev'    => 'SettingsController@unlockDev',
    'GET /tools/lock-dev'       => 'SettingsController@lockDev',
];
