<?php

/**
 * RamenCraft API — Central Route Handler
 *
 * This is the entry point for all API requests.
 * FastRoute matches the URL to a controller method.
 * See: https://github.com/nikic/FastRoute
 */

// CORS headers — allow requests from the Vue frontend (localhost:5173)
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (preg_match('/^https?:\/\/(localhost|127\.0\.0\.1|::1)(:\d+)?$/', $origin)) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400');
}

// Handle preflight OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require __DIR__ . '/../vendor/autoload.php';

use FastRoute\RouteCollector;
use function FastRoute\simpleDispatcher;

/**
 * Define the API routes.
 * All routes are prefixed with /api/ for clarity.
 */
$dispatcher = simpleDispatcher(function (RouteCollector $r) {
    // Category routes (public)
    $r->addRoute('GET', '/api/categories', ['App\Controllers\CategoryController', 'getAll']);

    // Ingredient routes (public GET, admin CUD will be added in Phase 3)
    $r->addRoute('GET', '/api/ingredients', ['App\Controllers\IngredientController', 'getAll']);
    $r->addRoute('GET', '/api/ingredients/{id:\d+}', ['App\Controllers\IngredientController', 'get']);
});

/**
 * Dispatch the incoming request to the matched controller method.
 */
$httpMethod = $_SERVER['REQUEST_METHOD'];
$uri = strtok($_SERVER['REQUEST_URI'], '?');
$routeInfo = $dispatcher->dispatch($httpMethod, $uri);

switch ($routeInfo[0]) {
    case FastRoute\Dispatcher::NOT_FOUND:
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Not Found']);
        break;

    case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
        http_response_code(405);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Method Not Allowed']);
        break;

    case FastRoute\Dispatcher::FOUND:
        $class = $routeInfo[1][0];
        $method = $routeInfo[1][1];
        $controller = new $class();
        $vars = $routeInfo[2];
        $controller->$method($vars);
        break;
}
