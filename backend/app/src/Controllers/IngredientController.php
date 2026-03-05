<?php

namespace App\Controllers;

use App\Config\Database;
use App\Framework\Controller;

/**
 * IngredientController — handles API requests for ingredients
 *
 * Endpoints:
 *   GET /api/ingredients      — list all (supports filtering + pagination)
 *   GET /api/ingredients/{id} — get a single ingredient by ID
 *
 * Query parameters for getAll():
 *   ?category=broth   — filter by category name
 *   ?page=1           — page number (default: 1)
 *   ?limit=10         — items per page (default: 10, max: 50)
 */
class IngredientController extends Controller
{
    /**
     * GET /api/ingredients
     *
     * Returns a paginated list of ingredients with optional filtering.
     * Response format: { data: [...], page: 1, limit: 10, total: 29 }
     */
    public function getAll(): void
    {
        try {
            $db = Database::getConnection();

            // Read query parameters for filtering and pagination
            $categoryFilter = $_GET['category'] ?? null;
            $page = max(1, (int) ($_GET['page'] ?? 1));
            $limit = min(50, max(1, (int) ($_GET['limit'] ?? 10)));
            $offset = ($page - 1) * $limit;

            // Build the SQL query with optional category filter
            // Using a JOIN to filter by category name (e.g., ?category=broth)
            $where = '';
            $params = [];

            if ($categoryFilter) {
                $where = 'WHERE c.name = :category';
                $params[':category'] = $categoryFilter;
            }

            // Count total matching rows (for pagination metadata)
            $countSql = "SELECT COUNT(*) FROM ingredients i
                         JOIN categories c ON i.category_id = c.id
                         {$where}";
            $countStmt = $db->prepare($countSql);
            $countStmt->execute($params);
            $total = (int) $countStmt->fetchColumn();

            // Fetch the paginated results
            $sql = "SELECT i.*, c.name AS category_name
                    FROM ingredients i
                    JOIN categories c ON i.category_id = c.id
                    {$where}
                    ORDER BY i.category_id ASC, i.id ASC
                    LIMIT :limit OFFSET :offset";

            $stmt = $db->prepare($sql);
            // Bind filter params
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            // Bind pagination params as integers
            $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
            $stmt->execute();

            $rows = $stmt->fetchAll();

            // Convert snake_case DB columns to camelCase for the frontend
            $ingredients = array_map([$this, 'formatIngredient'], $rows);

            // Return paginated response with metadata
            $this->sendSuccessResponse([
                'data' => $ingredients,
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
            ]);
        } catch (\Exception $e) {
            $this->sendErrorResponse('Failed to fetch ingredients: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/ingredients/{id}
     *
     * Returns a single ingredient by its ID, or 404 if not found.
     */
    public function get(array $vars = []): void
    {
        try {
            $id = (int) ($vars['id'] ?? 0);

            if ($id <= 0) {
                $this->sendErrorResponse('Invalid ingredient ID', 400);
                return;
            }

            $db = Database::getConnection();

            $sql = "SELECT i.*, c.name AS category_name
                    FROM ingredients i
                    JOIN categories c ON i.category_id = c.id
                    WHERE i.id = :id";

            $stmt = $db->prepare($sql);
            $stmt->bindValue(':id', $id, \PDO::PARAM_INT);
            $stmt->execute();

            $row = $stmt->fetch();

            if (!$row) {
                $this->sendErrorResponse('Ingredient not found', 404);
                return;
            }

            $this->sendSuccessResponse($this->formatIngredient($row));
        } catch (\Exception $e) {
            $this->sendErrorResponse('Failed to fetch ingredient: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Convert a database row (snake_case) to a frontend-friendly format (camelCase)
     */
    private function formatIngredient(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'categoryId' => (int) $row['category_id'],
            'categoryName' => $row['category_name'] ?? null,
            'name' => $row['name'],
            'nameJp' => $row['name_jp'],
            'description' => $row['description'],
            'spriteIcon' => $row['sprite_icon'],
            'spriteBowl' => $row['sprite_bowl'],
            'caloriesPerServing' => $row['calories_per_serving'] ? (float) $row['calories_per_serving'] : null,
            'proteinG' => $row['protein_g'] ? (float) $row['protein_g'] : null,
            'fatG' => $row['fat_g'] ? (float) $row['fat_g'] : null,
            'carbsG' => $row['carbs_g'] ? (float) $row['carbs_g'] : null,
            'isAvailable' => (bool) $row['is_available'],
        ];
    }
}
