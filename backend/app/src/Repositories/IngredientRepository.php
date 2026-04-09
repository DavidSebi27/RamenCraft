<?php

namespace App\Repositories;

use App\Config\Database;
use App\Models\Ingredient;

/**
 * IngredientRepository — owns ALL SQL for ingredients.
 *
 * Returns Ingredient model objects via PDO::FETCH_CLASS.
 * Controllers and services never write SQL for ingredients.
 */
class IngredientRepository
{
    private \PDO $db;

    /** Explicit column list — no SELECT * */
    private const COLUMNS = 'i.id, i.category_id, i.name, i.name_jp, i.description,
        i.sprite_icon, i.sprite_bowl, i.calories_per_serving, i.protein_g,
        i.fat_g, i.carbs_g, i.is_available, i.created_at,
        c.name AS category_name';

    public function __construct(?\PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
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

        $sql = "SELECT " . self::COLUMNS . "
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
     *
     * @return int
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
     *
     * @return Ingredient|null
     */
    public function findById(int $id): ?Ingredient
    {
        $sql = "SELECT " . self::COLUMNS . "
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
     * @param int[] $ids
     * @return Ingredient[]
     */
    public function findByIds(array $ids): array
    {
        if (empty($ids)) return [];

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->db->prepare(
            "SELECT " . self::COLUMNS . "
             FROM ingredients i
             JOIN categories c ON i.category_id = c.id
             WHERE i.id IN ({$placeholders})"
        );
        $stmt->execute(array_map('intval', $ids));

        return $stmt->fetchAll(\PDO::FETCH_CLASS, Ingredient::class);
    }

    /**
     * Insert a new ingredient and return the hydrated model.
     *
     * @param array $data  camelCase input from the controller
     * @return Ingredient
     */
    public function insert(array $data): Ingredient
    {
        $stmt = $this->db->prepare(
            "INSERT INTO ingredients (category_id, name, name_jp, description,
                sprite_icon, sprite_bowl, calories_per_serving, protein_g,
                fat_g, carbs_g, is_available)
             VALUES (:category_id, :name, :name_jp, :description, :sprite_icon,
                :sprite_bowl, :calories_per_serving, :protein_g, :fat_g,
                :carbs_g, :is_available)"
        );
        $stmt->execute([
            ':category_id'          => (int) $data['categoryId'],
            ':name'                 => $data['name'],
            ':name_jp'              => $data['nameJp'] ?? null,
            ':description'          => $data['description'] ?? null,
            ':sprite_icon'          => $data['spriteIcon'] ?? null,
            ':sprite_bowl'          => $data['spriteBowl'] ?? null,
            ':calories_per_serving' => $data['caloriesPerServing'] ?? null,
            ':protein_g'            => $data['proteinG'] ?? null,
            ':fat_g'                => $data['fatG'] ?? null,
            ':carbs_g'              => $data['carbsG'] ?? null,
            ':is_available'         => isset($data['isAvailable']) ? (int) $data['isAvailable'] : 1,
        ]);

        return $this->findById((int) $this->db->lastInsertId());
    }

    /**
     * Update an existing ingredient and return the hydrated model.
     *
     * @param int $id
     * @param array $data  camelCase input — only provided fields are updated
     * @return Ingredient|null
     */
    public function update(int $id, array $data): ?Ingredient
    {
        $stmt = $this->db->prepare(
            "UPDATE ingredients SET
                category_id = COALESCE(:category_id, category_id),
                name = COALESCE(:name, name),
                name_jp = COALESCE(:name_jp, name_jp),
                description = COALESCE(:description, description),
                sprite_icon = COALESCE(:sprite_icon, sprite_icon),
                sprite_bowl = COALESCE(:sprite_bowl, sprite_bowl),
                calories_per_serving = COALESCE(:calories_per_serving, calories_per_serving),
                protein_g = COALESCE(:protein_g, protein_g),
                fat_g = COALESCE(:fat_g, fat_g),
                carbs_g = COALESCE(:carbs_g, carbs_g),
                is_available = COALESCE(:is_available, is_available)
             WHERE id = :id"
        );
        $stmt->execute([
            ':category_id'          => $data['categoryId'] ?? null,
            ':name'                 => $data['name'] ?? null,
            ':name_jp'              => $data['nameJp'] ?? null,
            ':description'          => $data['description'] ?? null,
            ':sprite_icon'          => $data['spriteIcon'] ?? null,
            ':sprite_bowl'          => $data['spriteBowl'] ?? null,
            ':calories_per_serving' => $data['caloriesPerServing'] ?? null,
            ':protein_g'            => $data['proteinG'] ?? null,
            ':fat_g'                => $data['fatG'] ?? null,
            ':carbs_g'              => $data['carbsG'] ?? null,
            ':is_available'         => isset($data['isAvailable']) ? (int) $data['isAvailable'] : null,
            ':id'                   => $id,
        ]);

        return $this->findById($id);
    }

    /**
     * Delete an ingredient and its related records (cascade).
     *
     * @param int $id
     * @return bool  True if a row was deleted
     */
    public function delete(int $id): bool
    {
        // Cascade: remove references first
        $this->db->prepare("DELETE FROM pairings WHERE ingredient_1_id = :id1 OR ingredient_2_id = :id2")
                 ->execute([':id1' => $id, ':id2' => $id]);
        $this->db->prepare("DELETE FROM bowl_ingredients WHERE ingredient_id = :id")
                 ->execute([':id' => $id]);
        $this->db->prepare("DELETE FROM favorite_ingredients WHERE ingredient_id = :id")
                 ->execute([':id' => $id]);

        $stmt = $this->db->prepare("DELETE FROM ingredients WHERE id = :id");
        $stmt->bindValue(':id', $id, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    /**
     * Check if an ingredient exists.
     *
     * @return bool
     */
    public function exists(int $id): bool
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM ingredients WHERE id = :id");
        $stmt->bindValue(':id', $id, \PDO::PARAM_INT);
        $stmt->execute();
        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Find all ingredients for nutrition seeding.
     *
     * @return Ingredient[]
     */
    public function findAllForSeeding(): array
    {
        $stmt = $this->db->prepare(
            "SELECT " . self::COLUMNS . "
             FROM ingredients i
             JOIN categories c ON i.category_id = c.id
             ORDER BY i.id ASC"
        );
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_CLASS, Ingredient::class);
    }

    /**
     * Update nutrition data from external API.
     */
    public function updateNutrition(int $id, float $calories, float $protein, float $fat, float $carbs): void
    {
        $stmt = $this->db->prepare(
            "UPDATE ingredients SET
                calories_per_serving = :cal,
                protein_g = :protein,
                fat_g = :fat,
                carbs_g = :carbs
             WHERE id = :id"
        );
        $stmt->execute([
            ':cal'     => $calories,
            ':protein' => $protein,
            ':fat'     => $fat,
            ':carbs'   => $carbs,
            ':id'      => $id,
        ]);
    }
}
