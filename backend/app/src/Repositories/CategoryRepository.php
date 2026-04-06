<?php

namespace App\Repositories;

use App\Config\Database;
use App\Models\Category;

/**
 * CategoryRepository — database queries for categories.
 *
 * Returns Category model objects, never raw arrays.
 */
class CategoryRepository
{
    private \PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Find all categories with optional search and pagination.
     *
     * @return Category[]
     */
    public function findAll(?string $search = null, int $page = 1, int $limit = 20): array
    {
        $where = '';
        $params = [];

        if ($search) {
            $where = "WHERE name LIKE :search OR display_name LIKE :search2";
            $params[':search'] = '%' . $search . '%';
            $params[':search2'] = '%' . $search . '%';
        }

        $offset = ($page - 1) * $limit;

        $sql = "SELECT * FROM categories {$where} ORDER BY sort_order ASC LIMIT :limit OFFSET :offset";
        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_CLASS, Category::class);
    }

    /**
     * Count total categories matching filters.
     */
    public function count(?string $search = null): int
    {
        $where = '';
        $params = [];

        if ($search) {
            $where = "WHERE name LIKE :search OR display_name LIKE :search2";
            $params[':search'] = '%' . $search . '%';
            $params[':search2'] = '%' . $search . '%';
        }

        $stmt = $this->db->prepare("SELECT COUNT(*) FROM categories {$where}");
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }
}
