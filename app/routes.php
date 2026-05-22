<?php

/**
 * SIMRS Routing Definitions
 * Maps URL paths to ModuleControllers@method
 */

// Rate limit group for login/auth request endpoints
$router->group(['middleware' => 'rateLimit'], function($router) {
    // Auth Guest Routes
    $router->get('/auth/login', 'Auth@login');
    $router->post('/auth/do-login', 'Auth@doLogin');
    $router->get('/auth/forgot-password', 'Auth@forgotPassword');
    $router->post('/auth/do-forgot-password', 'Auth@doForgotPassword');
    $router->get('/auth/reset-password', 'Auth@resetPassword');
    $router->post('/auth/do-reset-password', 'Auth@doResetPassword');
});

// Authenticated Routes (Requires active session)
$router->group(['middleware' => 'auth'], function($router) {
    // Dashboard Routes
    $router->get('/', 'Dashboard@index');
    $router->get('/dashboard', 'Dashboard@index');
    $router->get('/dashboard/recent-activities', 'Dashboard@recentActivities');
    $router->get('/profile', 'Dashboard@profile');
    $router->post('/profile/update', 'Dashboard@updateProfile');

    // Auth Actions (Logged In)
    $router->get('/auth/logout', 'Auth@logout');
    $router->post('/auth/logout', 'Auth@logout');
    $router->get('/auth/change-password', 'Auth@changePassword');
    $router->post('/auth/do-change-password', 'Auth@doChangePassword');

    // Patient CRUD Routes
    $router->get('/patient', 'Patient@index');
    $router->get('/patient/create', 'Patient@create');
    $router->post('/patient/store', 'Patient@store');
    $router->get('/patient/detail/{id}', 'Patient@detail');
    $router->get('/patient/edit/{id}', 'Patient@edit');
    $router->post('/patient/update/{id}', 'Patient@update');
    $router->post('/patient/delete/{id}', 'Patient@delete');
    $router->get('/patient/search', 'Patient@search');

    // Master Data Routes
    $router->get('/master/hospital', 'Master@hospital');
    $router->post('/master/hospital/update', 'Master@updateHospital');
    $router->get('/master/department', 'Master@department');
    $router->post('/master/department/store', 'Master@storeDepartment');
    $router->post('/master/department/update/{id}', 'Master@updateDepartment');
    $router->post('/master/department/delete/{id}', 'Master@deleteDepartment');
    $router->get('/master/doctor', 'Master@doctor');
    $router->post('/master/doctor/store', 'Master@storeDoctor');
    $router->post('/master/doctor/update/{id}', 'Master@updateDoctor');
    $router->post('/master/doctor/delete/{id}', 'Master@deleteDoctor');
    $router->get('/master/polyclinic', 'Master@polyclinic');
    $router->post('/master/polyclinic/store', 'Master@storePolyclinic');
    $router->post('/master/polyclinic/update/{id}', 'Master@updatePolyclinic');
    $router->post('/master/polyclinic/delete/{id}', 'Master@deletePolyclinic');

    // Appointment & Queue Routes
    $router->get('/appointment', 'Appointment@index');
    $router->get('/appointment/create', 'Appointment@create');
    $router->post('/appointment/store', 'Appointment@store');
    $router->post('/appointment/update-status/{id}', 'Appointment@updateStatus');
    $router->get('/queue', 'Appointment@queue');
    $router->post('/queue/call/{id}', 'Appointment@callQueue');
    $router->get('/schedule', 'Appointment@schedule');
    $router->post('/schedule/store', 'Appointment@storeSchedule');

    // Medical Records Routes
    $router->get('/medical-record', 'MedicalRecord@index');
    $router->get('/medical-record/detail/{id}', 'MedicalRecord@detail');
    $router->get('/medical-record/create', 'MedicalRecord@create');
    $router->post('/medical-record/store', 'MedicalRecord@store');

    // Laboratory Routes
    $router->get('/laboratory/orders', 'Laboratory@orders');
    $router->get('/laboratory/results', 'Laboratory@results');
    $router->post('/laboratory/results/input/{id}', 'Laboratory@inputResults');
    $router->get('/laboratory/templates', 'Laboratory@templates');

    // Pharmacy Routes
    $router->get('/pharmacy/prescriptions', 'Pharmacy@prescriptions');
    $router->post('/pharmacy/prescriptions/dispense/{id}', 'Pharmacy@dispense');
    $router->get('/pharmacy/medicines', 'Pharmacy@medicines');
    $router->get('/pharmacy/stock', 'Pharmacy@stock');

    // Billing Routes
    $router->get('/billing/invoices', 'Billing@invoices');
    $router->get('/billing/payments', 'Billing@payments');
    $router->post('/billing/payments/store', 'Billing@storePayment');
    $router->get('/billing/outstanding', 'Billing@outstanding');

    // Inventory Routes
    $router->get('/inventory/items', 'Inventory@items');
    $router->get('/inventory/purchase-orders', 'Inventory@purchaseOrders');
    $router->get('/inventory/suppliers', 'Inventory@suppliers');
    $router->get('/inventory/stock-opname', 'Inventory@stockOpname');

    // HR Routes
    $router->get('/hr/employees', 'Hr@employees');
    $router->get('/hr/attendance', 'Hr@attendance');
    $router->get('/hr/shifts', 'Hr@shifts');
    $router->get('/hr/leaves', 'Hr@leaves');

    // Report Routes
    $router->get('/report/daily', 'Report@daily');
    $router->get('/report/monthly', 'Report@monthly');
    $router->get('/report/financial', 'Report@financial');
    $router->get('/report/custom', 'Report@custom');

    // Settings Routes
    $router->get('/settings/general', 'Settings@general');
    $router->post('/settings/general/update', 'Settings@updateGeneral');
    $router->get('/settings/users', 'Settings@users');
    $router->post('/settings/users/store', 'Settings@storeUser');
    $router->get('/settings/roles', 'Settings@roles');
    $router->get('/settings/backup', 'Settings@backup');
    $router->post('/settings/backup/run', 'Settings@runBackup');
    $router->get('/settings/audit', 'Settings@audit');
});
