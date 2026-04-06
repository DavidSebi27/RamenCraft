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
    // Auth routes
    $r->addRoute('POST', '/api/auth/register', ['App\Controllers\AuthController', 'register']);
    $r->addRoute('POST', '/api/auth/login', ['App\Controllers\AuthController', 'login']);
    $r->addRoute('GET', '/api/auth/me', ['App\Controllers\AuthController', 'me']);

    // Category routes
    $r->addRoute('GET', '/api/categories', ['App\Controllers\CategoryController', 'getAll']);

    // Ingredient routes (full CRUD)
    $r->addRoute('GET', '/api/ingredients', ['App\Controllers\IngredientController', 'getAll']);
    $r->addRoute('GET', '/api/ingredients/{id:\d+}', ['App\Controllers\IngredientController', 'get']);
    $r->addRoute('POST', '/api/ingredients', ['App\Controllers\IngredientController', 'create']);
    $r->addRoute('PUT', '/api/ingredients/{id:\d+}', ['App\Controllers\IngredientController', 'update']);
    $r->addRoute('DELETE', '/api/ingredients/{id:\d+}', ['App\Controllers\IngredientController', 'delete']);

    // Pairing routes (full CRUD)
    $r->addRoute('GET', '/api/pairings', ['App\Controllers\PairingController', 'getAll']);
    $r->addRoute('GET', '/api/pairings/{id:\d+}', ['App\Controllers\PairingController', 'get']);
    $r->addRoute('POST', '/api/pairings', ['App\Controllers\PairingController', 'create']);
    $r->addRoute('PUT', '/api/pairings/{id:\d+}', ['App\Controllers\PairingController', 'update']);
    $r->addRoute('DELETE', '/api/pairings/{id:\d+}', ['App\Controllers\PairingController', 'delete']);

    // Achievement routes — user-specific routes BEFORE {id} routes
    $r->addRoute('GET', '/api/achievements/mine', ['App\Controllers\AchievementController', 'getMyAchievements']);
    $r->addRoute('POST', '/api/achievements/check', ['App\Controllers\AchievementController', 'checkAchievements']);
    // Achievement CRUD
    $r->addRoute('GET', '/api/achievements', ['App\Controllers\AchievementController', 'getAll']);
    $r->addRoute('GET', '/api/achievements/{id:\d+}', ['App\Controllers\AchievementController', 'get']);
    $r->addRoute('POST', '/api/achievements', ['App\Controllers\AchievementController', 'create']);
    $r->addRoute('PUT', '/api/achievements/{id:\d+}', ['App\Controllers\AchievementController', 'update']);
    $r->addRoute('DELETE', '/api/achievements/{id:\d+}', ['App\Controllers\AchievementController', 'delete']);

    // Nutrition routes (external API — Open Food Facts, server-side with caching)
    $r->addRoute('GET', '/api/nutrition/ingredient/{id:\d+}', ['App\Controllers\NutritionController', 'getByIngredient']);
    $r->addRoute('POST', '/api/nutrition/seed', ['App\Controllers\NutritionController', 'seedAll']);

    // User routes (no create — that's auth/register in Phase 4)
    $r->addRoute('GET', '/api/users', ['App\Controllers\UserController', 'getAll']);
    $r->addRoute('GET', '/api/users/{id:\d+}', ['App\Controllers\UserController', 'get']);
    $r->addRoute('PUT', '/api/users/{id:\d+}', ['App\Controllers\UserController', 'update']);
    $r->addRoute('DELETE', '/api/users/{id:\d+}', ['App\Controllers\UserController', 'delete']);

    // Favorites routes (authenticated — save/load bowl configs)
    $r->addRoute('GET', '/api/favorites', ['App\Controllers\FavoritesController', 'getAll']);
    $r->addRoute('GET', '/api/favorites/{id:\d+}', ['App\Controllers\FavoritesController', 'get']);
    $r->addRoute('POST', '/api/favorites', ['App\Controllers\FavoritesController', 'create']);
    $r->addRoute('PUT', '/api/favorites/{id:\d+}', ['App\Controllers\FavoritesController', 'update']);
    $r->addRoute('DELETE', '/api/favorites/{id:\d+}', ['App\Controllers\FavoritesController', 'delete']);

    // Bowl routes (authenticated — serve a bowl, view history)
    $r->addRoute('POST', '/api/bowls/serve', ['App\Controllers\BowlController', 'serve']);
    $r->addRoute('GET', '/api/bowls/history', ['App\Controllers\BowlController', 'history']);

    // Leaderboard route
    $r->addRoute('GET', '/api/leaderboard', ['App\Controllers\LeaderboardController', 'getTopPlayers']);
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
