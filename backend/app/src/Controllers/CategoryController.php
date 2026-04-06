<?php

namespace App\Controllers;

use App\Config\Database;
use App\Framework\Controller;
use App\Models\Category;

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

            $page = max(1, (int) ($_GET['page'] ?? 1));
            $limit = min(50, max(1, (int) ($_GET['limit'] ?? 20)));
            $offset = ($page - 1) * $limit;

            $where = '';
            $params = [];

            // Search by name or display_name
            if (!empty($_GET['search'])) {
                $where = "WHERE name LIKE :search OR display_name LIKE :search2";
                $params[':search'] = '%' . $_GET['search'] . '%';
                $params[':search2'] = '%' . $_GET['search'] . '%';
            }

            $countStmt = $db->prepare("SELECT COUNT(*) FROM categories {$where}");
            $countStmt->execute($params);
            $total = (int) $countStmt->fetchColumn();

            $sql = "SELECT * FROM categories {$where} ORDER BY sort_order ASC LIMIT :limit OFFSET :offset";
            $stmt = $db->prepare($sql);
            foreach ($params as $key => $val) {
                $stmt->bindValue($key, $val);
            }
            $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll();

            $categories = array_map(function ($row) {
                return (new Category($row))->toArray();
            }, $rows);

            $this->sendSuccessResponse([
                'data' => $categories,
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
            ]);
        } catch (\Exception $e) {
            $this->sendErrorResponse('Failed to fetch categories: ' . $e->getMessage(), 500);
        }
    }
}
