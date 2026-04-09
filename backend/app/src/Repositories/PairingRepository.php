<?php

namespace App\Repositories;

use App\Config\Database;
use App\Models\Pairing;
use App\Models\PairingGroup;

/**
 * PairingRepository — owns ALL SQL for pairings.
 *
 * Returns typed Pairing or PairingGroup objects.
 */
class PairingRepository
{
    private \PDO $db;

    public function __construct(?\PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    /**
     * Find matching pairings for a set of ingredient IDs, grouped by combo name.
     *
     * @param int[] $ingredientIds
     * @return PairingGroup[]
     */
    public function findMatchingForIngredients(array $ingredientIds): array
    {
        if (count($ingredientIds) < 2) return [];

        $placeholders = implode(',', array_fill(0, count($ingredientIds), '?'));

        $stmt = $this->db->prepare(
            "SELECT p.combo_name, p.score_modifier,
                    i1.name AS ingredient_1_name, i2.name AS ingredient_2_name
             FROM pairings p
             JOIN ingredients i1 ON p.ingredient_1_id = i1.id
             JOIN ingredients i2 ON p.ingredient_2_id = i2.id
             WHERE p.ingredient_1_id IN ({$placeholders})
               AND p.ingredient_2_id IN ({$placeholders})"
        );

        $params = array_merge(
            array_map('intval', $ingredientIds),
            array_map('intval', $ingredientIds)
        );
        $stmt->execute($params);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Group into PairingGroup objects
        $groups = [];
        foreach ($rows as $row) {
            $name = $row['combo_name'];
            if (!isset($groups[$name])) {
                $groups[$name] = new PairingGroup($name);
            }
            $groups[$name]->addPair(
                (int) $row['score_modifier'],
                $row['ingredient_1_name'],
                $row['ingredient_2_name']
            );
        }

        return array_values($groups);
    }

    /**
     * Find all pairings with optional search and pagination.
     *
     * @return Pairing[]
     */
    public function findAll(?string $search = null, ?int $ingredientId = null, int $page = 1, int $limit = 20): array
    {
        $where = '';
        $params = [];

        if ($search) {
            $where = "WHERE p.combo_name LIKE :search OR p.description LIKE :search2";
            $params[':search'] = '%' . $search . '%';
            $params[':search2'] = '%' . $search . '%';
        }

        if ($ingredientId) {
            $where .= ($where ? ' AND' : 'WHERE') . ' (p.ingredient_1_id = :iid OR p.ingredient_2_id = :iid2)';
            $params[':iid'] = $ingredientId;
            $params[':iid2'] = $ingredientId;
        }

        $offset = ($page - 1) * $limit;

        $sql = "SELECT p.id, p.ingredient_1_id, p.ingredient_2_id, p.score_modifier,
                       p.combo_name, p.description,
                       i1.name AS ingredient_1_name, i2.name AS ingredient_2_name
                FROM pairings p
                JOIN ingredients i1 ON p.ingredient_1_id = i1.id
                JOIN ingredients i2 ON p.ingredient_2_id = i2.id
                {$where}
                ORDER BY p.id ASC
                LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_CLASS, Pairing::class);
    }

    /**
     * Count total pairings matching filters.
     *
     * @return int
     */
    public function count(?string $search = null, ?int $ingredientId = null): int
    {
        $where = '';
        $params = [];

        if ($search) {
            $where = "WHERE p.combo_name LIKE :search OR p.description LIKE :search2";
            $params[':search'] = '%' . $search . '%';
            $params[':search2'] = '%' . $search . '%';
        }

        if ($ingredientId) {
            $where .= ($where ? ' AND' : 'WHERE') . ' (p.ingredient_1_id = :iid OR p.ingredient_2_id = :iid2)';
            $params[':iid'] = $ingredientId;
            $params[':iid2'] = $ingredientId;
        }

        $stmt = $this->db->prepare("SELECT COUNT(*) FROM pairings p {$where}");
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Find a single pairing by ID.
     *
     * @return Pairing|null
     */
    public function findById(int $id): ?Pairing
    {
        $stmt = $this->db->prepare(
            "SELECT p.id, p.ingredient_1_id, p.ingredient_2_id, p.score_modifier,
                    p.combo_name, p.description,
                    i1.name AS ingredient_1_name, i2.name AS ingredient_2_name
             FROM pairings p
             JOIN ingredients i1 ON p.ingredient_1_id = i1.id
             JOIN ingredients i2 ON p.ingredient_2_id = i2.id
             WHERE p.id = :id"
        );
        $stmt->bindValue(':id', $id, \PDO::PARAM_INT);
        $stmt->execute();
        $stmt->setFetchMode(\PDO::FETCH_CLASS, Pairing::class);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Insert a new pairing.
     *
     * @return Pairing
     */
    public function insert(array $data): Pairing
    {
        $stmt = $this->db->prepare(
            "INSERT INTO pairings (ingredient_1_id, ingredient_2_id, score_modifier, combo_name, description)
             VALUES (:id1, :id2, :mod, :combo, :desc)"
        );
        $stmt->execute([
            ':id1'   => (int) $data['ingredient1Id'],
            ':id2'   => (int) $data['ingredient2Id'],
            ':mod'   => (int) ($data['scoreModifier'] ?? 0),
            ':combo' => $data['comboName'] ?? null,
            ':desc'  => $data['description'] ?? null,
        ]);
        return $this->findById((int) $this->db->lastInsertId());
    }

    /**
     * Update an existing pairing.
     *
     * @return Pairing|null
     */
    public function update(int $id, array $data): ?Pairing
    {
        $stmt = $this->db->prepare(
            "UPDATE pairings SET
                ingredient_1_id = COALESCE(:id1, ingredient_1_id),
                ingredient_2_id = COALESCE(:id2, ingredient_2_id),
                score_modifier = COALESCE(:mod, score_modifier),
                combo_name = COALESCE(:combo, combo_name),
                description = COALESCE(:desc, description)
             WHERE id = :id"
        );
        $stmt->execute([
            ':id1'   => $data['ingredient1Id'] ?? null,
            ':id2'   => $data['ingredient2Id'] ?? null,
            ':mod'   => $data['scoreModifier'] ?? null,
            ':combo' => $data['comboName'] ?? null,
            ':desc'  => $data['description'] ?? null,
            ':id'    => $id,
        ]);
        return $this->findById($id);
    }

    /**
     * Delete a pairing.
     *
     * @return bool
     */
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM pairings WHERE id = :id");
        $stmt->bindValue(':id', $id, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    /**
     * Check if a pairing exists.
     *
     * @return bool
     */
    public function exists(int $id): bool
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM pairings WHERE id = :id");
        $stmt->bindValue(':id', $id, \PDO::PARAM_INT);
        $stmt->execute();
        return (int) $stmt->fetchColumn() > 0;
    }
}
