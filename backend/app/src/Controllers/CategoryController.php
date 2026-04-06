<?php

namespace App\Controllers;

use App\Framework\Controller;
use App\Models\Category;
use App\Repositories\CategoryRepository;

/**
 * CategoryController — handles API requests for ingredient categories.
 *
 * Uses CategoryRepository for database queries.
 */
class CategoryController extends Controller
{
    /**
     * GET /api/categories
     */
    public function getAll(): void
    {
        try {
            $search = $_GET['search'] ?? null;
            $page = max(1, (int) ($_GET['page'] ?? 1));
            $limit = min(50, max(1, (int) ($_GET['limit'] ?? 20)));

            $repo = new CategoryRepository();
            $categories = $repo->findAll($search, $page, $limit);
            $total = $repo->count($search);

            $this->sendSuccessResponse([
                'data' => array_map(fn(Category $c) => $c->toArray(), $categories),
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
            ]);
        } catch (\Exception $e) {
            $this->sendErrorResponse('Failed to fetch categories: ' . $e->getMessage(), 500);
        }
    }
}
