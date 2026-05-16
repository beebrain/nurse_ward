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
$routes->get('debug/auth', '\App\Controllers\DebugController::auth');
$routes->get('debug/test-login', '\App\Controllers\DebugLoginController::test');
$routes->get('debug/check-user', '\App\Controllers\DebugLoginController::checkUser');

// Account settings
$routes->get('account/change-password', '\App\Controllers\AccountController::changePasswordView', ['filter' => 'session']);
$routes->post('account/change-password', '\App\Controllers\AccountController::changePasswordAction', ['filter' => 'session']);

// Static JS via PHP (avoids 404 when reverse proxy does not serve public/js/*)
$routes->get('app-asset/js/(:segment)', '\App\Controllers\AppAsset::javascript/$1');
// Same file when browser requests .../public/js/... (e.g. app.publicAssetsPrefix = public + all requests via index.php)
$routes->get('public/js/(:segment)', '\App\Controllers\AppAsset::javascript/$1');

$routes->group('census', ['filter' => 'permission:census.record'], static function ($routes) {
    $routes->get('/', '\App\Controllers\CensusController::index');
    $routes->get('new', '\App\Controllers\CensusController::create');
    $routes->get('history', '\App\Controllers\CensusController::history');
    $routes->get('history-data', '\App\Controllers\CensusController::historyData');
    $routes->get('productivity', '\App\Controllers\CensusController::productivity');
    $routes->get('productivity-data', '\App\Controllers\CensusController::productivityData');
    $routes->get('movement-context', '\App\Controllers\CensusController::movementContext');
    $routes->post('store', '\App\Controllers\CensusController::store');
    $routes->post('autosave', '\App\Controllers\CensusController::autosave');
});

$routes->group('reports', ['filter' => 'permission:reports.view'], static function ($routes) {
    $routes->get('monthly', '\App\Controllers\ReportController::monthly');
    $routes->get('user-wards', '\App\Controllers\ReportController::userWards');
    $routes->get('daily-summary', '\App\Controllers\ReportController::dailySummary');
    $routes->get('daily-summary-data', '\App\Controllers\ReportController::dailySummaryData');
    $routes->get('getData', '\App\Controllers\ReportController::getData');
    $routes->get('export', '\App\Controllers\ReportController::export');
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

    $routes->group('nurse-wards', static function ($routes) {
        $routes->get('/', '\App\Controllers\Admin\NurseWardController::index');
        $routes->get('edit/(:num)', '\App\Controllers\Admin\NurseWardController::edit/$1');
        $routes->post('update/(:num)', '\App\Controllers\Admin\NurseWardController::update/$1');
    });

    $routes->group('backup', static function ($routes) {
        $routes->get('/', '\App\Controllers\Admin\BackupController::index');
        $routes->post('create', '\App\Controllers\Admin\BackupController::create');
        $routes->get('download', '\App\Controllers\Admin\BackupController::download');
        $routes->get('download-now', '\App\Controllers\Admin\BackupController::downloadNow');
        $routes->post('delete', '\App\Controllers\Admin\BackupController::delete');
        $routes->post('import', '\App\Controllers\Admin\BackupController::importSql');
    });
});
