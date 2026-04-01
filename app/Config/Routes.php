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

// Shield registration and other routes
$routes->get('register', '\CodeIgniter\Shield\Controllers\RegisterController::registerView');
$routes->post('register', '\CodeIgniter\Shield\Controllers\RegisterController::registerAction');
$routes->get('login/magic-link', '\CodeIgniter\Shield\Controllers\MagicLinkController::loginView');
$routes->post('login/magic-link', '\CodeIgniter\Shield\Controllers\MagicLinkController::loginAction');
$routes->get('login/verify-magic-link', '\CodeIgniter\Shield\Controllers\MagicLinkController::verify');

$routes->get('auth/pending', '\App\Controllers\AuthController::pending');
$routes->get('auth/deactivated', '\App\Controllers\AuthController::deactivated');
$routes->get('debug/auth', '\App\Controllers\DebugController::auth');
$routes->get('debug/test-login', '\App\Controllers\DebugLoginController::test');
$routes->get('debug/check-user', '\App\Controllers\DebugLoginController::checkUser');

$routes->group('census', ['filter' => 'permission:census.record'], static function ($routes) {
    $routes->get('/', '\App\Controllers\CensusController::index');
    $routes->get('new', '\App\Controllers\CensusController::create');
    $routes->post('store', '\App\Controllers\CensusController::store');
    $routes->post('autosave', '\App\Controllers\CensusController::autosave');
});

$routes->group('reports', ['filter' => 'permission:reports.view'], static function ($routes) {
    $routes->get('monthly', '\App\Controllers\ReportController::monthly');
    $routes->get('daily-summary', '\App\Controllers\ReportController::dailySummary');
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
