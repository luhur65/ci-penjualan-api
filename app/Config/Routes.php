<?php

use CodeIgniter\Router\RouteCollection;
use App\Controllers\Api\LoginController;
use App\Controllers\Api\UserController;
use App\Controllers\Api\MenuController;
use App\Controllers\Api\RoleController;
use App\Controllers\Api\AcosController;
use App\Controllers\Api\ParameterController;
use App\Controllers\Api\PenjualanController;
use App\Controllers\Api\PelangganController;
use App\Controllers\Api\ErrorController;
use App\Controllers\Api\GridPreferencesController;
use App\Controllers\Api\TestingMasterDetailController;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');

// service('auth')->routes($routes);
$routes->get('/tes', function() {
    return 'tes route';
});

$routes->group("api", function ($routes) {
    // $routes->post("register", "Register::index");
    $routes->post("token", [LoginController::class, 'index']);
    $routes->post("refresh-token", [LoginController::class, 'refreshToken']);
    $routes->get("permissions", [LoginController::class, 'getPermissionUser']);

    $routes->post('forgot-password', [LoginController::class, 'forgotPassword']);
    $routes->post('reset-password', [LoginController::class, 'resetPassword']);
});

$routes->group("api", ['filter' => ['jwtFilter']], function ($routes) {
    $routes->get('parameter/combo', [ParameterController::class, 'getCombo']);
    $routes->get('parameter/lookup', [ParameterController::class, 'lookup']);
    $routes->get('users/(:num)/roles', [UserController::class, 'getUserRoles']);
    $routes->get('users/(:num)/acls', [UserController::class, 'getUserAcls']);
    $routes->get('grid-preferences', [GridPreferencesController::class, 'getGridPreferences']);
    $routes->post('grid-preferences', [GridPreferencesController::class, 'saveGridPreferences']);
    $routes->delete('grid-preferences/(:segment)', [GridPreferencesController::class, 'deleteGridPreferences/$1']);

    // Notifications Route
    $routes->get('notifications/unread', [\App\Controllers\Api\NotificationController::class, 'getUnread']);
    $routes->patch('notifications/read/(:num)', [\App\Controllers\Api\NotificationController::class, 'markAsRead/$1']);
    $routes->get('notifications/download/(:segment)', [\App\Controllers\Api\NotificationController::class, 'download/$1']);
});

$routes->group("api", ['filter' => ['jwtFilter', 'aclFilter']], function ($routes) {

    // user routes
    $routes->get('users/fieldlength', [UserController::class, 'fieldLength']);
    $routes->get('users/export', [UserController::class, 'export']);
    
    // pelanggan routes (dropdown)
    $routes->get('pelanggan', [PelangganController::class, 'index']);
    $routes->resource("users", ['namespace' => '', 'controller' => UserController::class]);

    // role routes
    $routes->get('roles/fieldlength', [RoleController::class, 'fieldLength']);
    $routes->resource('roles', ['namespace' => '', 'controller' => RoleController::class]);

    // acoss routes
    $routes->get('acos/fieldlength', [AcosController::class, 'fieldLength']);
    $routes->resource('acos', ['namespace' => '', 'controller' => AcosController::class]);

    // menu routes
    $routes->get('menu/fieldlength', [MenuController::class, 'fieldLength']);
    $routes->get('menu/controllers', [MenuController::class, 'getAllClass']);
    $routes->get('menu/parents', [MenuController::class, 'getMenuParent']);
    $routes->get('menu/export', [MenuController::class, 'export']);
    $routes->resource('menu', ['namespace' => '', 'controller' => MenuController::class]);

    // parameter routes
    $routes->get('parameter/fieldlength', [ParameterController::class, 'fieldLength']);
    $routes->resource('parameters', ['namespace' => '', 'controller' => ParameterController::class]);
    
    // penjualan routes
    $routes->get('penjualan/fieldlength', [PenjualanController::class, 'fieldLength']);
    $routes->resource('penjualan', ['namespace' => '', 'controller' => PenjualanController::class]);

    // error routes
    $routes->resource('error', ['namespace' => '', 'controller' => ErrorController::class]);

    // testingmasterdetail routes (master - penjualan)
    // !! PENTING: Route spesifik harus sebelum segment !!
    $routes->get('testingmasterdetail/fieldlength', [TestingMasterDetailController::class, 'fieldLength']);
    $routes->get('testingmasterdetail/export', [TestingMasterDetailController::class, 'export']);
    $routes->get('testingmasterdetail/nextnumber', [TestingMasterDetailController::class, 'nextnumber']);
    $routes->get('testingmasterdetail', [TestingMasterDetailController::class, 'index']);
    $routes->post('testingmasterdetail', [TestingMasterDetailController::class, 'create']);

    // detail sub-routes (HARUS sebelum testingmasterdetail/(:segment))
    $routes->post('testingmasterdetail/detail', [TestingMasterDetailController::class, 'createDetail']);
    $routes->get('testingmasterdetail/detail/(:segment)', [TestingMasterDetailController::class, 'showDetail/$1']);
    $routes->patch('testingmasterdetail/detail/(:segment)', [TestingMasterDetailController::class, 'updateDetail/$1']);
    $routes->delete('testingmasterdetail/detail/(:segment)', [TestingMasterDetailController::class, 'deleteDetail/$1']);

    // master segment routes
    $routes->get('testingmasterdetail/(:segment)/detail/export', [TestingMasterDetailController::class, 'exportDetail/$1']);
    $routes->get('testingmasterdetail/(:segment)/detail', [TestingMasterDetailController::class, 'indexDetail/$1']);
    $routes->get('testingmasterdetail/(:segment)', [TestingMasterDetailController::class, 'show/$1']);
    $routes->patch('testingmasterdetail/(:segment)', [TestingMasterDetailController::class, 'update/$1']);
    $routes->delete('testingmasterdetail/(:segment)', [TestingMasterDetailController::class, 'delete/$1']);

});