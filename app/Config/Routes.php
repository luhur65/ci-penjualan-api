<?php

use CodeIgniter\Router\RouteCollection;
use App\Controllers\Api\LoginController;
use App\Controllers\Api\UserController;
use App\Controllers\Api\MenuController;
use App\Controllers\Api\RoleController;
use App\Controllers\Api\AcosController;
use App\Controllers\Api\ParameterController;

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
});

$routes->group("api", ['filter' => ['jwtFilter', 'aclFilter']], function ($routes) {
    
    // user routes
    $routes->get('users/fieldlength', [UserController::class, 'fieldLength']);
    $routes->get('users/export', [UserController::class, 'export']);
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

});