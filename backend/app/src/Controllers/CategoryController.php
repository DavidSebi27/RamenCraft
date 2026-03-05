<?php

namespace App\Controllers;

use App\Config\Database;
use App\Framework\Controller;

/**
 * CategoryController — handles API requests for ingredient categories
 *
 * Endpoints:
 *   GET /api/categories — returns all categories sorted by sort_order
 */
class CategoryController extends Controller
{
    /**
     * GET /api/categories
     *
     * Returns all categories sorted by their display order.
     * No pagination needed since there are only 5 categories.
     */
    public function getAll(): void
    {
        try {
            $db = Database::getConnection();

            $stmt = $db->query("SELECT * FROM categories ORDER BY sort_order ASC");
            $rows = $stmt->fetchAll();

            // Convert snake_case DB rows to camelCase for the frontend
            $categories = array_map(function ($row) {
                return [
                    'id' => (int) $row['id'],
                    'name' => $row['name'],
                    'displayName' => $row['display_name'],
                    'sortOrder' => (int) $row['sort_order'],
                ];
            }, $rows);

            $this->sendSuccessResponse($categories);
        } catch (\Exception $e) {
            $this->sendErrorResponse('Failed to fetch categories: ' . $e->getMessage(), 500);
        }
    }
}
