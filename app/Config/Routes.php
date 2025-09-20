<?php
namespace Config;

use Config\Services;

$routes = Services::routes();

// 1. Load the system's routing file first
if (file_exists(SYSTEMPATH . 'Config/Routes.php')) {
    require SYSTEMPATH . 'Config/Routes.php';
}

// --------------------------------------------------------------------
// Router Setup
// --------------------------------------------------------------------
$routes->setDefaultNamespace('App\Controllers');
$routes->setDefaultController('AuthController');
$routes->setDefaultMethod('index');
$routes->setTranslateURIDashes(false);
$routes->set404Override();
// $routes->setAutoRoute(false); // disable legacy auto routing

// --------------------------------------------------------------------
// Public / Auth Routes
// --------------------------------------------------------------------
$routes->get('/',                    'AuthController::loginForm');
$routes->post('auth/login',          'AuthController::login');
$routes->post('external/receive',    'AuthController::externalReceive');
$routes->get('auth/logout',          'AuthController::logout');
$routes->post('dashboard_url',    'AuthController::dashboardUrl');
// Two‐factor endpoints if needed
$routes->get('auth/2fa',             'AuthController::show2faForm');
$routes->post('auth/2fa/verify',     'AuthController::verify2fa');

$routes->get('adfs/sync_all_users',  'Adfs::syncUsers');
$routes->get('test',  'AuthController::test');

// --------------------------------------------------------------------
// Admin Routes (login required + permission guarded)
// --------------------------------------------------------------------
$routes->group('admin', ['filter' => ['auth']], static function($routes) {
    // Dashboard
    $routes->get('dashboard',                 'Admin\Dashboard::index',       ['filter' => 'perm:admin.dashboard']);

    // ---------------- Meal Management ----------------
    // Subscriptions
    $routes->get('subscription',              'Admin\Subscription::history',          ['filter' => 'perm:admin.subscriptions.history']);
    $routes->get('subscription/new',                  'Admin\Subscription::new',              ['filter' => 'perm:admin.subscriptions.new']);
    $routes->post('subscription/store',               'Admin\Subscription::store',            ['filter' => 'perm:admin.subscriptions.store']);
    $routes->get('employees/active-list',             'Admin\Subscription::activeList');
    //$routes->get('subscription/history/(:num)/view',  'Admin\Subscription::view/$1',          ['filter' => 'perm:admin.subscriptions.view']);
    $routes->get('subscription/all-subscriptions',    'Admin\Subscription::allSubscriptions', ['filter' => 'perm:admin.subscriptions.all-subscriptions']);
    //$routes->post('subscription/unsubscribe/(:num)',  'Admin\Subscription::unsubscribe/$1',   ['filter' => 'perm:admin.subscriptions.unsubscribe']);
    $routes->post('subscription/unsubscribe_single/(:num)', 'Admin\Subscription::unsubscribeSingle/$1', ['filter' => 'perm:admin.subscriptions.unsubscribe_single']);
    $routes->post('subscription/unsubscribe_bulk',    'Admin\Subscription::unsubscribe_bulk', ['filter' => 'perm:admin.subscriptions.unsubscribe_bulk']);

    // Ramadan - Iftar
    $routes->get('ifter-subscription',                    'Admin\IfterSubscription::index/me',             ['filter' => 'perm:admin.ramadan.ifter-subscription.history']);
    $routes->get('ifter-subscription/all-ifter-list',     'Admin\IfterSubscription::index/all',        ['filter' => 'perm:admin.ramadan.ifter-subscription.all-ifter-list']);
    $routes->get('ifter-subscription/new',                'Admin\IfterSubscription::new',                 ['filter' => 'perm:admin.ramadan.ifter-subscription.new']);
    $routes->post('ifter-subscription/store',             'Admin\IfterSubscription::store',               ['filter' => 'perm:admin.ifter-subscription.store']);
    $routes->post('ifter-subscription/unsubscribe/(:num)','Admin\IfterSubscription::unsubscribeSingle/$1',['filter' => 'perm:admin.ifter-subscription.unsubscribe']);
    $routes->post('ifter-subscription/unsubscribe_bulk',  'Admin\IfterSubscription::unsubscribe_bulk',    ['filter' => 'perm:admin.ifter-subscription.unsubscribe_bulk']);

    // Ramadan - Sehri
    $routes->get('sehri-subscription',                 'Admin\SehriSubscription::browse/me',       ['filter' => 'perm:admin.ramadan.sehri-subscription.history']);
    $routes->get('sehri-subscription/all-sehri-list',  'Admin\SehriSubscription::browse/all',  ['filter' => 'perm:admin.ramadan.sehri-subscription.all-sehri-list']);
    $routes->get('sehri-subscription/new',             'Admin\SehriSubscription::new',           ['filter' => 'perm:admin.ramadan.sehri-subscription.new']);
    $routes->post('sehri-subscription/store',                  'Admin\SehriSubscription::store',         ['filter' => 'perm:admin.sehri-subscription.store']);
    $routes->post('sehri-subscription/unsubscribe/(:num)',     'Admin\SehriSubscription::unsubscribeSingle/$1', ['filter' => 'perm:admin.sehri-subscription.unsubscribe']);
    $routes->post('sehri-subscription/unsubscribe_bulk',       'Admin\SehriSubscription::unsubscribe_bulk',    ['filter' => 'perm:admin.sehri-subscription.unsubscribe_bulk']);

    // Eid Subscription
    $routes->get('eid-subscription',                           'Admin\EidSubscription::history',         ['filter' => 'perm:admin.eid-subscription.history']);
    $routes->get('eid-subscription/all-eid-subscription-list', 'Admin\EidSubscription::allEidSubsList',  ['filter' => 'perm:admin.eid-subscription.all-eid-subscription-list']);
    $routes->get('eid-subscription/new',                       'Admin\EidSubscription::new',             ['filter' => 'perm:admin.eid-subscription.new']);
    $routes->post('eid-subscription/store',                    'Admin\EidSubscription::store',           ['filter' => 'perm:admin.eid-subscription.store']);
    $routes->post('eid-subscription/unsubscribe/(:num)',       'Admin\EidSubscription::unsubscribeSingle/$1', ['filter' => 'perm:admin.eid-subscription.unsubscribe']);
    $routes->post('eid-subscription/unsubscribe_bulk',         'Admin\EidSubscription::unsubscribe_bulk',  ['filter' => 'perm:admin.eid-subscription.unsubscribe_bulk']);

    // Guest Subscription
    $routes->get('guest-subscriptions',                        'Admin\GuestSubscription::index',         ['filter' => 'perm:admin.guest-subscriptions.history']);
    $routes->get('guest-subscriptions/all-guest-list',         'Admin\GuestSubscription::index/all',  ['filter' => 'perm:admin.guest-subscriptions.all-guest-list']);
    $routes->get('guest-subscriptions/new',                    'Admin\GuestSubscription::new',           ['filter' => 'perm:admin.guest-subscriptions.new']);
    $routes->post('guest-subscriptions/store',                 'Admin\GuestSubscription::store',         ['filter' => 'perm:admin.guest-subscriptions.store']);
    $routes->post('guest-subscriptions/unsubscribe/(:num)',    'Admin\GuestSubscription::unsubscribe/$1',['filter' => 'perm:admin.guest-subscriptions.unsubscribe']);
    $routes->post('guest-subscriptions/unsubscribe_bulk',      'Admin\GuestSubscription::unsubscribe_bulk', ['filter' => 'perm:admin.guest-subscriptions.unsubscribe_bulk']);

    // Guest Bulk upload
    $routes->get('guest-subscriptions/bulk-upload',            'Admin\GuestSubscription::uploadForm',    ['filter' => 'perm:admin.guest-subscriptions.bulk-upload']);
    $routes->post('guest-subscriptions/process-upload',        'Admin\GuestSubscription::processUpload');
    $routes->get('guest-subscriptions/bulk-list',              'Admin\GuestSubscription::index/bulk',  ['filter' => 'perm:admin.guest-subscriptions.bulk-list']);

    // Intern Requisitions (bulk)
    $routes->get('intern-requisitions',                        'Admin\InternRequisition::index',         ['filter' => 'perm:admin.intern-requisitions.index']);
    $routes->get('intern-requisitions/new',                    'Admin\InternRequisition::new',           ['filter' => 'perm:admin.intern-requisitions.new']);
    $routes->post('intern-requisitions/process-upload',        'Admin\InternRequisition::processUpload');
    $routes->post('intern-subscriptions/unsubscribe/(:num)',   'Admin\InternRequisition::unsubscribeSingle/$1', ['filter' => 'perm:admin.intern-subscriptions.unsubscribe_single']);
    $routes->get('intern-subscriptions/cutoffinfo/(:num)',     'Admin\InternRequisition::getCutOffInfo/$1');
    // $routes->get('intern-requisitions/template',               'Admin\InternRequisition::downloadTemplate', ['filter' => 'perm:admin.intern-requisitions.template']);
    $routes->post('intern-subscription/unsubscribe_bulk',      'Admin\InternRequisition::unsubscribe_bulk', ['filter' => 'perm:admin.intern-subscriptions.unsubscribe_bulk']);

    // Cafeteria CRUD
    $routes->get(   'cafeterias',               'Admin\crud\Cafeterias::index',    ['filter' => 'perm:admin.cafeterias.index']);
    $routes->get(   'cafeterias/new',           'Admin\crud\Cafeterias::new',      ['filter' => 'perm:admin.cafeterias.new']);
    $routes->post(  'cafeterias',               'Admin\crud\Cafeterias::create');
    $routes->get(   'cafeterias/(:num)/edit',   'Admin\crud\Cafeterias::edit/$1',  ['filter' => 'perm:admin.cafeterias.edit']);
    $routes->post(  'cafeterias/(:num)',        'Admin\crud\Cafeterias::update/$1');
    $routes->delete('cafeterias/(:num)',        'Admin\crud\Cafeterias::delete/$1',['filter' => 'perm:admin.cafeterias.delete']);

    // Meal Type
    $routes->get(   'meal-types',               'Admin\crud\MealTypes::index',    ['filter' => 'perm:admin.meal-types.index']);
    $routes->get(   'meal-types/new',           'Admin\crud\MealTypes::new',      ['filter' => 'perm:admin.meal-types.new']);
    $routes->post(  'meal-types',               'Admin\crud\MealTypes::create');
    $routes->get(   'meal-types/(:num)/edit',   'Admin\crud\MealTypes::edit/$1',  ['filter' => 'perm:admin.meal-types.edit']);
    $routes->post(  'meal-types/(:num)',        'Admin\crud\MealTypes::update/$1');
    $routes->post(  'meal-types/(:num)/delete', 'Admin\crud\MealTypes::delete/$1',['filter' => 'perm:admin.meal-types.delete']);

    // Meal Cost Settings
    $routes->get(   'meal-costs',               'Admin\crud\MealCosts::index',    ['filter' => 'perm:admin.meal-costs.index']);
    $routes->get(   'meal-costs/new',           'Admin\crud\MealCosts::new',      ['filter' => 'perm:admin.meal-costs.new']);
    $routes->post(  'meal-costs',               'Admin\crud\MealCosts::create');
    $routes->get(   'meal-costs/(:num)/edit',   'Admin\crud\MealCosts::edit/$1',  ['filter' => 'perm:admin.meal-costs.edit']);
    $routes->post(  'meal-costs/(:num)',        'Admin\crud\MealCosts::update/$1');
    //$routes->delete('meal-costs/(:num)',        'Admin\crud\MealCosts::delete/$1',['filter' => 'perm:admin.meal-costs.delete']);
    $routes->post('meal-costs/(:num)/toggle',   'Admin\crud\MealCosts::toggle/$1');
    $routes->get('meal-costs/horizon/(:num)',   'Admin\crud\MealCosts::horizon/$1');


    // Meal Contribution Rules
    $routes->get(   'contributions',                  'Admin\crud\Contributions::index',   ['filter' => 'perm:admin.contributions.index']);
    $routes->get(   'contributions/new',              'Admin\crud\Contributions::new',     ['filter' => 'perm:admin.contributions.new']);
    $routes->post(  'contributions',                  'Admin\crud\Contributions::create');
    $routes->get(   'contributions/(:num)/edit',      'Admin\crud\Contributions::edit/$1', ['filter' => 'perm:admin.contributions.edit']);
    $routes->post(  'contributions/(:num)',           'Admin\crud\Contributions::update/$1');
    $routes->delete('contributions/(:num)',           'Admin\crud\Contributions::delete/$1',['filter' => 'perm:admin.contributions.delete']);
    $routes->get(   'contributions/get-base-price/(:num)', 'Admin\crud\Contributions::getBasePrice/$1');
    $routes->post(  'contributions/(:num)/toggle',   'Admin\crud\Contributions::toggle/$1');

    // Occasions
    $routes->get(   'occasions',               'Admin\crud\Occasions::index',   ['filter' => 'perm:admin.occasions.index']);
    $routes->get(   'occasions/new',           'Admin\crud\Occasions::new',     ['filter' => 'perm:admin.occasions.new']);
    $routes->post(  'occasions',               'Admin\crud\Occasions::create');
    $routes->get(   'occasions/(:num)/edit',   'Admin\crud\Occasions::edit/$1', ['filter' => 'perm:admin.occasions.edit']);
    $routes->post(  'occasions/(:num)',        'Admin\crud\Occasions::update/$1');
    $routes->delete('occasions/(:num)',        'Admin\crud\Occasions::delete/$1',['filter' => 'perm:admin.occasions.delete']);

    // Cut‐Off Times
    $routes->get(   'cutoff-times',               'Admin\crud\CutoffTimes::index',   ['filter' => 'perm:admin.cutoff-times.index']);
    $routes->get(   'cutoff-times/new',           'Admin\crud\CutoffTimes::create',  ['filter' => 'perm:admin.cutoff-times.new']);
    $routes->post(  'cutoff-times',               'Admin\crud\CutoffTimes::store');
    $routes->get(   'cutoff-times/(:num)/edit',   'Admin\crud\CutoffTimes::edit/$1', ['filter' => 'perm:admin.cutoff-times.edit']);
    $routes->put(   'cutoff-times/(:num)',        'Admin\crud\CutoffTimes::update/$1');
    $routes->delete('cutoff-times/(:num)',        'Admin\crud\CutoffTimes::delete/$1',['filter' => 'perm:admin.cutoff-times.delete']);

    // Ramadan Periods
    $routes->get(   'ramadan-periods',               'Admin\crud\RamadanConfigs::index',   ['filter' => 'perm:admin.ramadan-periods.index']);
    $routes->get(   'ramadan-periods/new',           'Admin\crud\RamadanConfigs::new',     ['filter' => 'perm:admin.ramadan-periods.create']);
    $routes->post(  'ramadan-periods',               'Admin\crud\RamadanConfigs::create');
    $routes->get(   'ramadan-periods/(:num)/edit',   'Admin\crud\RamadanConfigs::edit/$1', ['filter' => 'perm:admin.ramadan-periods.edit']);
    $routes->post(  'ramadan-periods/(:num)',        'Admin\crud\RamadanConfigs::update/$1');
    $routes->delete('ramadan-periods/(:num)',        'Admin\crud\RamadanConfigs::delete/$1');

    // Public Holiday Calendar
    $routes->get(   'public-holidays',               'Admin\crud\Holidays::index',   ['filter' => 'perm:admin.public-holidays.index']);
    $routes->get(   'public-holidays/new',           'Admin\crud\Holidays::new',     ['filter' => 'perm:admin.public-holidays.new']);
    $routes->post(  'public-holidays',               'Admin\crud\Holidays::create');
    $routes->get(   'public-holidays/(:num)/edit',   'Admin\crud\Holidays::edit/$1', ['filter' => 'perm:admin.public-holidays.edit']);
    $routes->post(  'public-holidays/(:num)',        'Admin\crud\Holidays::update/$1');
    $routes->delete('public-holidays/(:num)',        'Admin\crud\Holidays::delete/$1',['filter' => 'perm:admin.public-holidays.delete']);

    // Employment Types
    $routes->get('employment-types',                'Admin\crud\EmploymentTypes::index',      ['filter' => 'perm:admin.employment-types.index']);
    $routes->get('employment-types/create',         'Admin\crud\EmploymentTypes::create',     ['filter' => 'perm:admin.employment-types.new']);
    $routes->post('employment-types/store',         'Admin\crud\EmploymentTypes::store');
    $routes->get('employment-types/(:num)/edit',    'Admin\crud\EmploymentTypes::edit/$1',    ['filter' => 'perm:admin.employment-types.edit']);
    $routes->get('employment-types/(:num)/toggle',  'Admin\crud\EmploymentTypes::toggle/$1');
    $routes->get('employment-types/(:num)/delete',  'Admin\crud\EmploymentTypes::delete/$1');

    // Approval Flows
    $routes->get(   'approval-flows',               'Admin\crud\ApprovalFlows::index',   ['filter' => 'perm:admin.approval-flows.index']);
    $routes->get(   'approval-flows/new',           'Admin\crud\ApprovalFlows::new');
    $routes->post(  'approval-flows',               'Admin\crud\ApprovalFlows::create');
    $routes->get(   'approval-flows/(:num)/edit',   'Admin\crud\ApprovalFlows::edit/$1');
    $routes->put(   'approval-flows/(:num)',        'Admin\crud\ApprovalFlows::update/$1');
    $routes->delete('approval-flows/(:num)',        'Admin\crud\ApprovalFlows::delete/$1');
    
    // Approval Steps
    $routes->get(   'approval-flows/(:num)/steps',                'Admin\crud\ApprovalSteps::index/$1');
    $routes->get(   'approval-flows/(:num)/steps/new',            'Admin\crud\ApprovalSteps::create/$1');
    $routes->post(  'approval-flows/(:num)/steps',                'Admin\crud\ApprovalSteps::store/$1');
    $routes->get(   'approval-flows/(:num)/steps/(:num)/edit',    'Admin\crud\ApprovalSteps::edit/$1/$2');
    $routes->put(   'approval-flows/(:num)/steps/(:num)',         'Admin\crud\ApprovalSteps::update/$1/$2');
    $routes->delete('approval-flows/(:num)/steps/(:num)',         'Admin\crud\ApprovalSteps::delete/$1/$2');

    // Meal Cards
    $routes->get ('meal-cards',              'Admin\MealCards::index',  ['filter' => 'perm:admin.meal-cards']);
    $routes->get ('meal-cards/new',          'Admin\MealCards::new',    ['filter' => 'perm:admin.meal-cards.form']);
    $routes->post('meal-cards',              'Admin\MealCards::store');
    $routes->get ('meal-cards/(:num)/edit',  'Admin\MealCards::edit/$1',['filter' => 'perm:admin.meal-cards.edit']);
    $routes->post('meal-cards/(:num)/update','Admin\MealCards::update/$1');
    $routes->post('meal-cards/(:num)/delete','Admin\MealCards::delete/$1');

    // Approval Queue
    $routes->group('approvals', ['filter' => 'perm:admin.approvals'], static function($routes) {
        $routes->get('',                           'Admin\MealApprovals::index');
        $routes->post('bulk-approve',              'Admin\MealApprovals::bulkApprove');
        $routes->post('bulk-reject',               'Admin\MealApprovals::bulkReject');
        $routes->post('approve/(:segment)/(:num)', 'Admin\MealApprovals::approveSingle/$1/$2');
        $routes->post('reject/(:segment)/(:num)',  'Admin\MealApprovals::rejectSingle/$1/$2');

    });

    // Global Settings
    $routes->get('settings',                  'Admin\Settings::index', ['filter' => 'perm:settings.manage']);
    $routes->post('settings/save',            'Admin\Settings::save',  ['filter' => 'perm:settings.manage']);

    // Reports
    $routes->get('reports/subscriptions',     'Admin\Reports::subscriptions', ['filter' => 'perm:reports.subscriptions']);
    $routes->get('reports/consumption',       'Admin\Reports::consumption',   ['filter' => 'perm:reports.consumption']);
    $routes->get('reports/financial',         'Admin\Reports::financial',     ['filter' => 'perm:reports.financial']);


    // ---------------- User & Role Management ----------------
    $routes->get('users',                     'Admin\Users::index',           ['filter' => 'perm:admin.users']);
    $routes->get('users/create',              'Admin\Users::create',          ['filter' => 'perm:admin.users.new']);
    $routes->post('users/store',              'Admin\Users::store');
    $routes->get('users/edit/(:num)',         'Admin\Users::edit/$1',         ['filter' => 'perm:admin.users.edit']);
    $routes->post('users/update/(:num)',      'Admin\Users::update/$1');
    $routes->get('users/active/(:num)',       'Admin\Users::active/$1',       ['filter' => 'perm:admin.users.active']);
    $routes->get('users/inactive/(:num)',     'Admin\Users::inactive/$1',     ['filter' => 'perm:admin.users.inactive']);
    $routes->get('users/(:num)/line-manager', 'Admin\Users::lineManagerSet/$1', ['filter' => 'perm:admin.users.line-manager-set']);
    $routes->post('users/(:num)/line-manager','Admin\Users::lineManagerSave/$1');

    $routes->get('roles',                     'Admin\Roles::index',           ['filter' => 'perm:admin.roles.index']);
    $routes->get('roles/create',              'Admin\Roles::create',          ['filter' => 'perm:admin.roles.new']);
    $routes->post('roles/store',              'Admin\Roles::store',           ['filter' => 'perm:admin.roles.create']);
    $routes->get('roles/edit/(:num)',         'Admin\Roles::edit/$1',         ['filter' => 'perm:admin.roles.edit']);
    $routes->post('roles/update/(:num)',      'Admin\Roles::update/$1',       ['filter' => 'perm:admin.roles.update']);
    $routes->post('roles/delete/(:num)',      'Admin\Roles::delete/$1',       ['filter' => 'perm:admin.roles.delete']);
    
    $routes->get('dba', 'Admin\DbaController::index', ['as' => 'dba']);
	$routes->post('dba', 'Admin\DbaController::execute', ['as' => 'dba_exec']);
});

// Admin > Report
$routes->group('admin/report', ['namespace' => 'App\Controllers\Admin','filter' => 'auth'], static function($routes) {
    $routes->get('/', 'ReportController::index', ['as' => 'admin.report']);

    $routes->get('meal-charge-list-for-payroll', 'ReportController::mealChargeListForPayroll', ['filter' => 'perm:admin.report.meal-charge-list-for-payroll']);

    $routes->get('meal-report-for-billing', 'ReportController::mealReportForBilling', ['filter' => 'perm:admin.report.meal-report-for-billing']);

    $routes->get('meal-detail-report', 'ReportController::mealDetailReport', ['filter' => 'perm:admin.report.meal-detail-report']);

    $routes->get('daily-meal-report', 'ReportController::dailyMealReport', ['filter' => 'perm:admin.report.daily-mealreport']);

    $routes->get('food-consumption-report', 'ReportController::foodConsumptionReport', ['filter' => 'perm:admin.report.food-consumption-report']);
});

// RBAC Admin (Super Admin / rbac.manage only)
$routes->group('admin', ['filter' => ['auth','perm:rbac.manage']], static function($routes) {
    // Permissions CRUD
    $routes->get('permissions',                 'Admin\Rbac\Permissions::index',   ['filter'=>'perm:rbac.permissions.read']);
    $routes->post('permissions',                'Admin\Rbac\Permissions::store',   ['filter'=>'perm:rbac.permissions.create']);
    $routes->get('permissions/(:num)/edit',     'Admin\Rbac\Permissions::edit/$1', ['filter'=>'perm:rbac.permissions.update']);
    $routes->post('permissions/(:num)',         'Admin\Rbac\Permissions::update/$1',['filter'=>'perm:rbac.permissions.update']);
    $routes->post('permissions/(:num)/delete',  'Admin\Rbac\Permissions::delete/$1',['filter'=>'perm:rbac.permissions.delete']);

    // Assignments
    // Role -> Permissions
    $routes->get('roles/(:num)/permissions',    'Admin\Rbac\Assign::role/$1',      ['filter'=>'perm:rbac.assign']);
    $routes->post('roles/(:num)/permissions',   'Admin\Rbac\Assign::saveRole/$1',  ['filter'=>'perm:rbac.assign']);

    // User -> Roles
    $routes->get('users/(:num)/roles',          'Admin\Rbac\Assign::user/$1',      ['filter'=>'perm:admin.user.set-rule']);
    $routes->post('users/(:num)/roles',         'Admin\Rbac\Assign::saveUser/$1');
});

// --------------------------------------------------------------------
// Employee Routes
// --------------------------------------------------------------------
$routes->group('employee', ['filter' => ['auth']], static function($routes) {
    $routes->get('dashboard',                       'Employee\Dashboard::index',              ['filter' => 'perm:employee.dashboard']);

    // Approval Queue
    // $routes->group('approvals', ['filter' => 'perm:employee.approvals'], static function($routes) {
    $routes->group('approvals', static function($routes) {
        $routes->get('',                             'Employee\MealApprovals::index');
        $routes->post('bulk-approve',          'Employee\MealApprovals::bulkApprove');
        $routes->post('bulk-reject',           'Employee\MealApprovals::bulkReject');
        $routes->post('approve/(:segment)/(:num)', 'Employee\MealApprovals::approveSingle/$1/$2');
        $routes->post('reject/(:segment)/(:num)',  'Employee\MealApprovals::rejectSingle/$1/$2');

    });

    // Meal Subscription
    $routes->get('subscription/new',                'Employee\Subscription::new',             ['filter' => 'perm:employee.subscriptions.new']);
    $routes->post('subscription/store',             'Employee\Subscription::store');
    $routes->get('subscription',            'Employee\Subscription::history',         ['filter' => 'perm:employee.subscriptions.history']);
    //$routes->get('subscription/history/(:num)/view','Employee\Subscription::view/$1',         ['filter' => 'perm:employee.subscriptions.view']);
    // $routes->post('subscription/unsubscribe/(:num)','Employee\Subscription::unsubscribe/$1',  ['filter' => 'perm:employee.subscriptions.unsubscribe_single']);
    $routes->post('subscription/unsubscribe_single/(:num)', 'Employee\Subscription::unsubscribeSingle/$1', ['filter' => 'perm:employee.subscriptions.unsubscribe']);

    // Guest Subscription
    $routes->get('guest-subscriptions',             'Employee\GuestSubscription::index',      ['filter' => 'perm:employee.guests.index']);
    $routes->get('guest-subscriptions/new',         'Employee\GuestSubscription::new',        ['filter' => 'perm:employee.guests.new']);
    $routes->post('guest-subscriptions/store',      'Employee\GuestSubscription::store');
    $routes->post('guest-subscriptions/unsubscribe/(:num)', 'Employee\GuestSubscription::unsubscribe/$1', ['filter' => 'perm:employee.guests.unsubscribe']);
    
    // Ifter Subscription
    $routes->get('ifter-subscription',      'Employee\IfterSubscription::history',    ['filter' => 'perm:employee.ifter.history']);
    $routes->get('ifter-subscription/new',  'Employee\IfterSubscription::new',        ['filter' => 'perm:employee.ifter.new']);
    $routes->post('ifter-subscription/store',       'Employee\IfterSubscription::store');
    $routes->post('ifter-subscription/unsubscribe/(:num)', 'Employee\IfterSubscription::unsubscribeSingle/$1', ['filter' => 'perm:employee.ifter.unsubscribe']);

    // Seheri Subscription
    $routes->get('sehri-subscription',      'Employee\SehriSubscription::history',    ['filter' => 'perm:employee.sehri.history']);
    $routes->get('sehri-subscription/new',  'Employee\SehriSubscription::new',        ['filter' => 'perm:employee.sehri.new']);
    $routes->post('sehri-subscription/store',       'Employee\SehriSubscription::store');
    $routes->post('sehri-subscription/unsubscribe/(:num)', 'Employee\SehriSubscription::unsubscribeSingle/$1', ['filter' => 'perm:employee.sehri.unsubscribe']);

    // Eid Subscription
    $routes->get('eid-subscription',                'Employee\EidSubscription::history',      ['filter' => 'perm:employee.eid.history']);
    $routes->get('eid-subscription/new',            'Employee\EidSubscription::new',          ['filter' => 'perm:employee.eid.new']);
    $routes->post('eid-subscription/store',         'Employee\EidSubscription::store');
    $routes->post('eid-subscription/unsubscribe/(:num)', 'Employee\EidSubscription::unsubscribeSingle/$1', ['filter' => 'perm:employee.eid.unsubscribe']);

});

// --------------------------------------------------------------------
// Vendor Routes
// --------------------------------------------------------------------
$routes->group('vendor', ['filter' => ['auth']], static function($routes) {
    $routes->get('dashboard',                 'Vendor\Dashboard::index',         ['filter' => 'perm:vendor.dashboard']);

    $routes->get('registrations/daily',       'Vendor\Registrations::daily',     ['filter' => 'perm:vendor.registrations.view']);
    $routes->get('registrations/monthly',     'Vendor\Registrations::monthly',   ['filter' => 'perm:vendor.registrations.monthly']);

    $routes->get('meals',                     'Vendor\Meals::index',             ['filter' => 'perm:vendor.meals.view']);

    $routes->get('reports',                   'Vendor\Reports::index',           ['filter' => 'perm:vendor.reports.view']);
    $routes->post('reports/download',         'Vendor\Reports::download',        ['filter' => 'perm:vendor.reports.export']);

    // Order History
    $routes->get('history',                   'Vendor\OrderHistory::index',      ['filter' => 'perm:vendor.history.view']);
    $routes->post('history/export',           'Vendor\OrderHistory::export',     ['filter' => 'perm:vendor.history.export']);

    // My Profile
    $routes->get('profile',                   'Vendor\Profile::index',           ['filter' => 'perm:vendor.profile.view']);
    $routes->post('profile/update',           'Vendor\Profile::update',          ['filter' => 'perm:vendor.profile.update']);
});


// --------------------------------------------------------------------
// API Routes (for kiosks, mobile apps, background jobs)
// --------------------------------------------------------------------
$routes->group('api_auth', ['filter' => 'auth_api'], static function($routes) {
    // Token Management
    $routes->post('tokens/generate',          'Api\TokenController::generate');
    $routes->post('tokens/validate',          'Api\TokenController::validate');
    $routes->get('tokens',                    'Api\TokenController::index');

    // Azure AD Sync
    $routes->get('ad/sync-users',             'Api\AdSyncController::syncUsers');

    // Reports (JSON)
    $routes->get('reports/subscriptions/daily',   'Api\ReportController::dailySubs');
    $routes->get('reports/subscriptions/weekly',  'Api\ReportController::weeklySubs');
    $routes->get('reports/subscriptions/monthly', 'Api\ReportController::monthlySubs');
    $routes->get('reports/guests',                'Api\ReportController::guestMeals');
    $routes->get('reports/utilization',           'Api\ReportController::utilization');
    $routes->get('reports/financial',             'Api\ReportController::financial');

    // Global Settings
    $routes->get('settings',                   'Api\SettingsController::index');
    $routes->post('settings/update',           'Api\SettingsController::update');
});

// API JSON endpoints for subscription form (require login if sensitive)
$routes->group('api', ['namespace' => 'App\Controllers\Api'], static function($r) {
    $r->get('subscription-settings',          'SubscriptionAjax::settings');
    $r->get('public-holidays',                'SubscriptionAjax::holidays');
    $r->post('subscriptions/check-overlap',   'SubscriptionAjax::checkOverlap');

    $r->post('save-new-card',     'LmsApi::saveNewCard');   // 1) SaveNewCard
    $r->post('get-emp-meal',      'LmsApi::getEmpMeal');    // 2) GetEmpMeal
    $r->get('cafeterias',         'LmsApi::cafeterias');    // 3) Cafeteria List
    $r->get('dashboard-info',     'LmsApi::dashboardInfo');    // 4) Dashboard Info
    $r->get('get-ramadan-period', 'LmsApi::ramadanInfo');
    $r->get('today-meal-types',   'LmsApi::getTodayMealTypes');
});


// Shared AJAX (require login)
$routes->get('eid-subscription/get-occasion-date/(:any)', 'Admin\EidSubscription::getOccasionDate/$1', ['filter' => 'auth']);
$routes->get('admin/user/getEmpId/(:num)', 'Admin\Users::getEmpId/$1');

// --------------------------------------------------------------------
// Environment-specific routes
// --------------------------------------------------------------------
if (file_exists(APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php')) {
    require APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php';
}
