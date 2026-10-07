<?php

require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../vendor/autoload.php';

// Core
require_once __DIR__ . '/../src/Core/Database.php';
require_once __DIR__ . '/../src/Core/Controller.php';
require_once __DIR__ . '/../src/Core/Router.php';

// Models
require_once __DIR__ . '/../src/Models/BaseModel.php';
require_once __DIR__ . '/../src/Models/UserModel.php';
require_once __DIR__ . '/../src/Models/ProductModel.php';
require_once __DIR__ . '/../src/Models/CustomerModel.php';
require_once __DIR__ . '/../src/Models/LicenseModel.php';
require_once __DIR__ . '/../src/Models/EstimateModel.php';
require_once __DIR__ . '/../src/Models/ExportModel.php';
require_once __DIR__ . '/../src/Models/LeadModel.php';

// Controllers
require_once __DIR__ . '/../src/Controllers/AuthController.php';
require_once __DIR__ . '/../src/Controllers/DashboardController.php';
require_once __DIR__ . '/../src/Controllers/CustomerController.php';
require_once __DIR__ . '/../src/Controllers/LicenseController.php';
require_once __DIR__ . '/../src/Controllers/ProductController.php';
require_once __DIR__ . '/../src/Controllers/UserController.php';
require_once __DIR__ . '/../src/Controllers/EstimateController.php';
require_once __DIR__ . '/../src/Controllers/ExportController.php';
require_once __DIR__ . '/../src/Controllers/ImportController.php';
require_once __DIR__ . '/../src/Controllers/LeadController.php';

// Routes
$router = new Router();

// Auth
$router->add('GET',  '/login',   'AuthController', 'showLogin');
$router->add('POST', '/login',   'AuthController', 'processLogin');
$router->add('ANY',  '/logout',  'AuthController', 'logout');

// Dashboard
$router->add('GET',  '/dashboard', 'DashboardController', 'index');
$router->add('GET',  '/',          'DashboardController', 'index');

// Customers
$router->add('GET',  '/customers',             'CustomerController', 'index');
$router->add('GET',  '/customers/add',         'CustomerController', 'create');
$router->add('POST', '/customers/add',         'CustomerController', 'store');
$router->add('GET',  '/customers/edit/{id}',   'CustomerController', 'edit');
$router->add('POST', '/customers/edit/{id}',   'CustomerController', 'update');
$router->add('GET',  '/customers/view/{id}',   'CustomerController', 'view');
$router->add('POST', '/customers/delete/{id}', 'CustomerController', 'delete');
$router->add('POST', '/customers/bulk-delete',  'CustomerController', 'bulkDelete');

// Licenses
$router->add('GET',  '/licenses',             'LicenseController', 'index');
$router->add('GET',  '/licenses/add',         'LicenseController', 'create');
$router->add('POST', '/licenses/add',         'LicenseController', 'store');
$router->add('GET',  '/licenses/edit/{id}',   'LicenseController', 'edit');
$router->add('POST', '/licenses/edit/{id}',   'LicenseController', 'update');
$router->add('GET',  '/licenses/view/{id}',   'LicenseController', 'view');
$router->add('POST', '/licenses/delete/{id}', 'LicenseController', 'delete');
$router->add('POST', '/licenses/bulk-delete',  'LicenseController', 'bulkDelete');

// Products
$router->add('GET',  '/products',             'ProductController', 'index');
$router->add('GET',  '/products/add',         'ProductController', 'create');
$router->add('POST', '/products/add',         'ProductController', 'store');
$router->add('GET',  '/products/edit/{id}',   'ProductController', 'edit');
$router->add('POST', '/products/edit/{id}',   'ProductController', 'update');
$router->add('POST', '/products/delete/{id}', 'ProductController', 'delete');

// Users
$router->add('GET',  '/users',             'UserController', 'index');
$router->add('GET',  '/users/add',         'UserController', 'create');
$router->add('POST', '/users/add',         'UserController', 'store');
$router->add('GET',  '/users/edit/{id}',   'UserController', 'edit');
$router->add('POST', '/users/edit/{id}',   'UserController', 'update');
$router->add('POST', '/users/toggle/{id}', 'UserController', 'toggleActive');

// Estimates
$router->add('GET',  '/estimates',             'EstimateController', 'index');
$router->add('GET',  '/estimates/add',         'EstimateController', 'create');
$router->add('POST', '/estimates/add',         'EstimateController', 'store');
$router->add('GET',  '/estimates/edit/{id}',   'EstimateController', 'edit');
$router->add('POST', '/estimates/edit/{id}',   'EstimateController', 'update');
$router->add('GET',  '/estimates/view/{id}',   'EstimateController', 'view');
$router->add('GET',  '/estimates/pdf/{id}',    'EstimateController', 'generatePdf');
$router->add('POST', '/estimates/delete/{id}', 'EstimateController', 'delete');

// Leads / Enquiries
$router->add('GET',  '/leads',                                    'LeadController', 'index');
$router->add('GET',  '/leads/add',                                'LeadController', 'create');
$router->add('POST', '/leads/add',                                'LeadController', 'store');
$router->add('GET',  '/leads/edit/{id}',                          'LeadController', 'edit');
$router->add('POST', '/leads/edit/{id}',                          'LeadController', 'update');
$router->add('GET',  '/leads/view/{id}',                          'LeadController', 'view');
$router->add('POST', '/leads/delete/{id}',                        'LeadController', 'delete');
$router->add('POST', '/leads/{id}/activity',                      'LeadController', 'addActivity');
$router->add('POST', '/leads/{id}/activity/delete/{activity_id}', 'LeadController', 'deleteActivity');

// Export
$router->add('GET', '/export',         'ExportController', 'index');
$router->add('GET', '/export/download', 'ExportController', 'download');

// Import
$router->add('GET',  '/import',         'ImportController', 'index');
$router->add('POST', '/import/process', 'ImportController', 'process');

$router->dispatch();
