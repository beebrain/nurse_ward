<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');

// Override Shield auth routes with custom username-enabled login
$routes->get('login', '\App\Controllers\LoginController::loginView');
$routes->post('login', '\App\Controllers\LoginController::loginAction');
$routes->get('logout', '\App\Controllers\LoginController::logoutAction');

// Public registration disabled — superadmin creates users via admin/users
$routes->get('register', '\App\Controllers\AuthController::registrationDisabled');
$routes->post('register', '\App\Controllers\AuthController::registrationDisabled');
$routes->get('login/magic-link', '\CodeIgniter\Shield\Controllers\MagicLinkController::loginView');
$routes->post('login/magic-link', '\CodeIgniter\Shield\Controllers\MagicLinkController::loginAction');
$routes->get('login/verify-magic-link', '\CodeIgniter\Shield\Controllers\MagicLinkController::verify');

$routes->get('auth/pending', '\App\Controllers\AuthController::pending');
$routes->get('auth/deactivated', '\App\Controllers\AuthController::deactivated');
// Account settings
$routes->get('account/change-password', '\App\Controllers\AccountController::changePasswordView', ['filter' => 'session']);
$routes->post('account/change-password', '\App\Controllers\AccountController::changePasswordAction', ['filter' => 'session']);

// Static assets via CI for deployments where the web server does not serve public/ directly.
$routes->get('app-asset/js/(:segment)', '\App\Controllers\AppAsset::javascript/$1');
$routes->get('app-asset/css/(:segment)', '\App\Controllers\AppAsset::stylesheet/$1');
$routes->get('index.php/app-asset/js/(:segment)', '\App\Controllers\AppAsset::javascript/$1');
$routes->get('index.php/app-asset/css/(:segment)', '\App\Controllers\AppAsset::stylesheet/$1');

$routes->group('census', ['filter' => 'session'], static function ($routes) {
    $routes->get('productivity', '\App\Controllers\CensusController::productivity');
    $routes->get('productivity-data', '\App\Controllers\CensusController::productivityData');
});

$routes->group('census', ['filter' => 'permission:census.record'], static function ($routes) {
    $routes->get('/', '\App\Controllers\CensusController::index');
    $routes->get('new', '\App\Controllers\CensusController::create');
    $routes->get('history', '\App\Controllers\CensusController::history');
    $routes->get('history-data', '\App\Controllers\CensusController::historyData');
    $routes->get('movement-context', '\App\Controllers\CensusController::movementContext');
    $routes->get('hourly-guidelines', '\App\Controllers\CensusController::hourlyGuidelines');
    $routes->post('productivity-preview', '\App\Controllers\CensusController::productivityPreview');
    $routes->post('store', '\App\Controllers\CensusController::store');
    $routes->post('confirm', '\App\Controllers\CensusController::confirmSave');
    $routes->get('behavior-dashboard', '\App\Controllers\ReportController::behaviorDashboard');
    $routes->get('behavior-dashboard-data', '\App\Controllers\ReportController::behaviorDashboardData');
});

$routes->group('reports', ['filter' => 'permission:reports.view'], static function ($routes) {
    $routes->get('user-wards', '\App\Controllers\ReportController::userWards');
    $routes->get('daily-summary', '\App\Controllers\ReportController::dailySummary');
    $routes->get('daily-summary-data', '\App\Controllers\ReportController::dailySummaryData');
    $routes->get('dashboard', '\App\Controllers\ReportController::dashboard');
    $routes->get('dashboardData', '\App\Controllers\ReportController::dashboardData');
});

$routes->group('admin', ['filter' => 'group:superadmin'], static function ($routes) {
    $routes->group('wards', static function ($routes) {
        $routes->get('/', '\App\Controllers\Admin\WardController::index');
        $routes->get('create', '\App\Controllers\Admin\WardController::create');
        $routes->post('store', '\App\Controllers\Admin\WardController::store');
        $routes->get('edit/(:num)', '\App\Controllers\Admin\WardController::edit/$1');
        $routes->post('update/(:num)', '\App\Controllers\Admin\WardController::update/$1');
        $routes->post('api-mapping/(:num)', '\App\Controllers\Admin\WardController::updateApiMapping/$1');
        $routes->post('delete/(:num)', '\App\Controllers\Admin\WardController::delete/$1');
    });

    $routes->group('users', static function ($routes) {
        $routes->get('/', '\App\Controllers\Admin\UserController::index');
        $routes->get('create', '\App\Controllers\Admin\UserController::create');
        $routes->post('store', '\App\Controllers\Admin\UserController::store');
        $routes->get('edit/(:num)', '\App\Controllers\Admin\UserController::edit/$1');
        $routes->post('update/(:num)', '\App\Controllers\Admin\UserController::update/$1');
        $routes->post('access/(:num)', '\App\Controllers\Admin\UserController::updateAccess/$1');
        $routes->post('delete/(:num)', '\App\Controllers\Admin\UserController::delete/$1');
        $routes->post('approve/(:num)', '\App\Controllers\Admin\UserController::approve/$1');
        $routes->post('deactivate/(:num)', '\App\Controllers\Admin\UserController::deactivate/$1');
        $routes->post('activate/(:num)', '\App\Controllers\Admin\UserController::activate/$1');
    });

    $routes->group('import-export', static function ($routes) {
        $routes->get('/', '\App\Controllers\Admin\ImportExportController::index');
        $routes->get('export', '\App\Controllers\Admin\ImportExportController::exportCensus');
        $routes->get('template', '\App\Controllers\Admin\ImportExportController::downloadTemplate');
        $routes->post('import', '\App\Controllers\Admin\ImportExportController::importCensus');
        $routes->get('export-csv', '\App\Controllers\Admin\ImportExportController::exportCsv');
        $routes->post('import-csv', '\App\Controllers\Admin\ImportExportController::importCsv');
    });

    $routes->group('backup', static function ($routes) {
        $routes->get('/', '\App\Controllers\Admin\BackupController::index');
        $routes->post('create', '\App\Controllers\Admin\BackupController::create');
        $routes->get('download', '\App\Controllers\Admin\BackupController::download');
        $routes->get('download-now', '\App\Controllers\Admin\BackupController::downloadNow');
        $routes->post('delete', '\App\Controllers\Admin\BackupController::delete');
        $routes->post('import', '\App\Controllers\Admin\BackupController::importSql');
    });

    $routes->group('hosxp-logs', static function ($routes) {
        $routes->get('/', '\App\Controllers\Admin\HosxpLogController::index');
        $routes->get('data', '\App\Controllers\Admin\HosxpLogController::data');
        $routes->get('detail/(:num)', '\App\Controllers\Admin\HosxpLogController::detail/$1');
    });
});
