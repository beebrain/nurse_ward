<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');

service('auth')->routes($routes);

$routes->get('auth/pending', '\App\Controllers\AuthController::pending');
$routes->get('auth/deactivated', '\App\Controllers\AuthController::deactivated');

$routes->group('census', ['filter' => 'permission:census.record'], static function ($routes) {
    $routes->get('/', '\App\Controllers\CensusController::index');
    $routes->get('new', '\App\Controllers\CensusController::create');
    $routes->post('store', '\App\Controllers\CensusController::store');
    $routes->post('autosave', '\App\Controllers\CensusController::autosave');
});

$routes->group('reports', ['filter' => 'permission:reports.view'], static function ($routes) {
    $routes->get('monthly', '\App\Controllers\ReportController::monthly');
    $routes->get('get_data', '\App\Controllers\ReportController::getData');
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
        $routes->post('approve/(:num)', '\App\Controllers\Admin\UserController::approve/$1');
        $routes->post('deactivate/(:num)', '\App\Controllers\Admin\UserController::deactivate/$1');
        $routes->post('activate/(:num)', '\App\Controllers\Admin\UserController::activate/$1');
    });
});
