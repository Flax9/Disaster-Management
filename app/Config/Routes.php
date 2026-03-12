<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');

// API Routes untuk IDCamp MVP
$routes->get('/api/warning', 'Emergency::getWarning');
$routes->post('/api/report', 'Emergency::submitReport');
