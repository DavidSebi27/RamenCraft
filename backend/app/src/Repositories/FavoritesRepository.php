<?php

namespace App\Repositories;

use App\Config\Database;
use App\Models\Favorite;
use App\Models\FavoriteIngredient;

/**
 * FavoritesRepository — owns ALL SQL for favorites and favorite_ingredients.
 *
 * Returns typed Favorite objects with FavoriteIngredient[] arrays.
 */
class FavoritesRepository
{
    private \PDO $db;

    public function __construct(?\PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    /**
     * Find all favorites for a user with optional search and pagination.
     *
     * @return Favorite[]
     */
    public function findByUser(int $userId, ?string $search = null, int $page = 1, int $limit = 20): array
    {
        $where = 'WHERE user_id = :uid';
        $params = [':uid' => $userId];

        if ($search) {
            $where .= ' AND name LIKE :search';
            $params[':search'] = '%' . $search . '%';
        }

        $offset = ($page - 1) * $limit;

        $sql = "SELECT id, user_id, name, created_at FROM favorites {$where}
                ORDER BY created_at DESC LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();

        /** @var Favorite[] $favorites */
        $favorites = $stmt->fetchAll(\PDO::FETCH_CLASS, Favorite::class);

        foreach ($favorites as $fav) {
            $fav->ingredients = $this->loadIngredients((int) $fav->id);
        }

        return $favorites;
    }

    /**
     * Count total favorites for a user.
     *
     * @return int
     */
    public function countByUser(int $userId, ?string $search = null): int
    {
        $where = 'WHERE user_id = :uid';
        $params = [':uid' => $userId];

        if ($search) {
            $where .= ' AND name LIKE :search';
            $params[':search'] = '%' . $search . '%';
        }

        $stmt = $this->db->prepare("SELECT COUNT(*) FROM favorites {$where}");
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Find a single favorite by ID (owned by user).
     *
     * @return Favorite|null
     */
    public function findByIdForUser(int $id, int $userId): ?Favorite
    {
        $stmt = $this->db->prepare(
            "SELECT id, user_id, name, created_at FROM favorites WHERE id = :id AND user_id = :uid"
        );
        $stmt->bindValue(':id', $id, \PDO::PARAM_INT);
        $stmt->bindValue(':uid', $userId, \PDO::PARAM_INT);
        $stmt->execute();
        $stmt->setFetchMode(\PDO::FETCH_CLASS, Favorite::class);
        $result = $stmt->fetch();

        if (!$result) return null;

        $result->ingredients = $this->loadIngredients((int) $result->id);
        return $result;
    }

    /**
     * Insert a new favorite with ingredients.
     *
     * @return int  The new favorite ID
     */
    public function insert(int $userId, string $name, array $ingredientIds): int
    {
        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare("INSERT INTO favorites (user_id, name) VALUES (:uid, :name)");
            $stmt->bindValue(':uid', $userId, \PDO::PARAM_INT);
            $stmt->execute([':uid' => $userId, ':name' => $name]);
            $favId = (int) $this->db->lastInsertId();

            $this->insertIngredients($favId, $ingredientIds);

            $this->db->commit();
            return $favId;
        } catch (\Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Update a favorite's name and/or ingredients.
     */
    public function update(int $id, int $userId, array $data): void
    {
        // Verify ownership
        $check = $this->db->prepare("SELECT id FROM favorites WHERE id = :id AND user_id = :uid");
        $check->bindValue(':id', $id, \PDO::PARAM_INT);
        $check->bindValue(':uid', $userId, \PDO::PARAM_INT);
        $check->execute();
        if (!$check->fetch()) {
            throw new \RuntimeException('Favorite not found');
        }

        $this->db->beginTransaction();

        try {
            if (!empty($data['name'])) {
                $this->db->prepare("UPDATE favorites SET name = :name WHERE id = :id")
                         ->execute([':name' => trim($data['name']), ':id' => $id]);
            }

            if (!empty($data['ingredient_ids']) && is_array($data['ingredient_ids'])) {
                $this->db->prepare("DELETE FROM favorite_ingredients WHERE favorite_id = :fid")
                         ->execute([':fid' => $id]);
                $this->insertIngredients($id, $data['ingredient_ids']);
            }

            $this->db->commit();
        } catch (\Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Delete a favorite (owned by user).
     *
     * @return bool
     */
    public function deleteForUser(int $id, int $userId): bool
    {
        $stmt = $this->db->prepare("DELETE FROM favorites WHERE id = :id AND user_id = :uid");
        $stmt->bindValue(':id', $id, \PDO::PARAM_INT);
        $stmt->bindValue(':uid', $userId, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    /**
     * Load typed FavoriteIngredient objects for a favorite.
     *
     * @return FavoriteIngredient[]
     */
    private function loadIngredients(int $favoriteId): array
    {
        $stmt = $this->db->prepare(
            "SELECT fi.ingredient_id, i.name, i.name_jp, c.name AS category_name
             FROM favorite_ingredients fi
             JOIN ingredients i ON fi.ingredient_id = i.id
             JOIN categories c ON i.category_id = c.id
             WHERE fi.favorite_id = :fid
             ORDER BY c.sort_order, i.name"
        );
        $stmt->bindValue(':fid', $favoriteId, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_CLASS, FavoriteIngredient::class);
    }

    /**
     * Insert ingredient rows for a favorite.
     *
     * @param int[] $ingredientIds
     */
    private function insertIngredients(int $favoriteId, array $ingredientIds): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO favorite_ingredients (favorite_id, ingredient_id) VALUES (:fid, :iid)"
        );
        foreach ($ingredientIds as $iid) {
            $stmt->bindValue(':fid', $favoriteId, \PDO::PARAM_INT);
            $stmt->bindValue(':iid', (int) $iid, \PDO::PARAM_INT);
            $stmt->execute();
        }
    }
}
