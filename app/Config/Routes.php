<?php

use CodeIgniter\Router\RouteCollection;
use App\Controllers\Api\LoginController;
use App\Controllers\Api\User;
use App\Controllers\Api\Menu;
use App\Controllers\Api\Role;

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
    
});

$routes->group("api", ['filter' => 'jwtFilter'], function ($routes) {
    
    // user routes
    $routes->get('users/fieldlength', [User::class, 'fieldLength']);
    $routes->resource("users", ['namespace' => '', 'controller' => User::class]);

    // role routes
    $routes->get('role/fieldlength', [Role::class, 'fieldLength']);
    $routes->resource('role', ['namespace' => '', 'controller' => Role::class]);
    
    // menu routes
    $routes->get('menu/fieldlength', [Menu::class, 'fieldLength']);
    $routes->get('menu/classes', [Menu::class, 'getAllClass']);
    $routes->resource('menu', ['namespace' => '', 'controller' => Menu::class]);

});