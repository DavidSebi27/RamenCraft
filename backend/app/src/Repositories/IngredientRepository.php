<?php

namespace App\Repositories;

use App\Config\Database;
use App\Models\Ingredient;

/**
 * IngredientRepository — database queries for ingredients.
 *
 * Returns Ingredient model objects, never raw arrays.
 * Controllers and services work with typed objects throughout.
 */
class IngredientRepository
{
    private \PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Find all ingredients with optional filtering and pagination.
     *
     * @return Ingredient[]
     */
    public function findAll(?string $category = null, ?string $search = null, int $page = 1, int $limit = 10): array
    {
        $where = '';
        $params = [];

        if ($category) {
            $where = 'WHERE c.name = :category';
            $params[':category'] = $category;
        }

        if ($search) {
            $where .= ($where ? ' AND' : 'WHERE') . ' (i.name LIKE :search OR i.description LIKE :search2)';
            $params[':search'] = '%' . $search . '%';
            $params[':search2'] = '%' . $search . '%';
        }

        $offset = ($page - 1) * $limit;

        $sql = "SELECT i.*, c.name AS category_name
                FROM ingredients i
                JOIN categories c ON i.category_id = c.id
                {$where}
                ORDER BY i.category_id ASC, i.id ASC
                LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_CLASS, Ingredient::class);
    }

    /**
     * Count total ingredients matching filters.
     */
    public function count(?string $category = null, ?string $search = null): int
    {
        $where = '';
        $params = [];

        if ($category) {
            $where = 'WHERE c.name = :category';
            $params[':category'] = $category;
        }

        if ($search) {
            $where .= ($where ? ' AND' : 'WHERE') . ' (i.name LIKE :search OR i.description LIKE :search2)';
            $params[':search'] = '%' . $search . '%';
            $params[':search2'] = '%' . $search . '%';
        }

        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM ingredients i
             JOIN categories c ON i.category_id = c.id
             {$where}"
        );
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Find a single ingredient by ID.
     */
    public function findById(int $id): ?Ingredient
    {
        $sql = "SELECT i.*, c.name AS category_name
                FROM ingredients i
                JOIN categories c ON i.category_id = c.id
                WHERE i.id = :id";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, \PDO::PARAM_INT);
        $stmt->execute();

        $stmt->setFetchMode(\PDO::FETCH_CLASS, Ingredient::class);
        $result = $stmt->fetch();

        return $result ?: null;
    }

    /**
     * Find multiple ingredients by IDs.
     *
     * @return Ingredient[]
     */
    public function findByIds(array $ids): array
    {
        if (empty($ids)) return [];

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->db->prepare(
            "SELECT i.*, c.name AS category_name
             FROM ingredients i
             JOIN categories c ON i.category_id = c.id
             WHERE i.id IN ({$placeholders})"
        );
        $stmt->execute(array_map('intval', $ids));

        return $stmt->fetchAll(\PDO::FETCH_CLASS, Ingredient::class);
    }
}
