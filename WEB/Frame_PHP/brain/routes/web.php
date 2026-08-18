<?php

use FastRoute\RouteCollector;

/** @var RouteCollector $r */

// Home
add_app_route($r, 'GET', '/', 'Public\HomeController@index');

// Auth
add_app_route($r, ['GET', 'POST'], '/login', 'Auth\AuthController@login');
add_app_route($r, 'GET', '/logout', 'Auth\AuthController@logout');

// Admin
add_app_route($r, ['GET', 'POST'], '/admin', 'Admin\DashboardController@index');
add_app_route($r, ['GET', 'POST'], '/admin/login', 'Admin\LoginController@index');

// API
add_app_route($r, ['GET', 'POST'], '/api/health', 'Api\ApiController@health');

// Storage / Assets
add_app_route($r, ['GET'], '/storage/{path:.+}', 'Public\AssetsController@serve');
