<?php

namespace App\Controllers;

use App\Config\Database;
use App\Framework\Controller;
use App\Models\Ingredient;
use App\Repositories\IngredientRepository;

/**
 * IngredientController — handles API requests for ingredients
 *
 * Uses IngredientRepository for all database queries.
 * Controller only handles: input parsing, calling repository, sending response.
 */
class IngredientController extends Controller
{
    /**
     * GET /api/ingredients
     *
     * Returns a paginated list of ingredients with optional filtering.
     */
    public function getAll(): void
    {
        try {
            $category = $_GET['category'] ?? null;
            $search = $_GET['search'] ?? null;
            $page = max(1, (int) ($_GET['page'] ?? 1));
            $limit = min(50, max(1, (int) ($_GET['limit'] ?? 10)));

            $repo = new IngredientRepository();
            $ingredients = $repo->findAll($category, $search, $page, $limit);
            $total = $repo->count($category, $search);

            $this->sendSuccessResponse([
                'data' => array_map(fn(Ingredient $i) => $i->toArray(), $ingredients),
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

            $repo = new IngredientRepository();
            $ingredient = $repo->findById($id);

            if (!$ingredient) {
                $this->sendErrorResponse('Ingredient not found', 404);
                return;
            }

            $this->sendSuccessResponse($ingredient->toArray());
        } catch (\Exception $e) {
            $this->sendErrorResponse('Failed to fetch ingredient: ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/ingredients
     *
     * Creates a new ingredient. Requires: name, categoryId.
     */
    public function create(): void
    {
        $this->authenticate();

        try {
            $input = json_decode(file_get_contents('php://input'), true);

            if (!$input || empty($input['name']) || empty($input['categoryId'])) {
                $this->sendErrorResponse('Fields "name" and "categoryId" are required', 400);
                return;
            }

            $db = Database::getConnection();

            $sql = "INSERT INTO ingredients (category_id, name, name_jp, description, sprite_icon, sprite_bowl,
                        calories_per_serving, protein_g, fat_g, carbs_g, is_available)
                    VALUES (:category_id, :name, :name_jp, :description, :sprite_icon, :sprite_bowl,
                        :calories_per_serving, :protein_g, :fat_g, :carbs_g, :is_available)";

            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':category_id' => (int) $input['categoryId'],
                ':name' => $input['name'],
                ':name_jp' => $input['nameJp'] ?? null,
                ':description' => $input['description'] ?? null,
                ':sprite_icon' => $input['spriteIcon'] ?? null,
                ':sprite_bowl' => $input['spriteBowl'] ?? null,
                ':calories_per_serving' => $input['caloriesPerServing'] ?? null,
                ':protein_g' => $input['proteinG'] ?? null,
                ':fat_g' => $input['fatG'] ?? null,
                ':carbs_g' => $input['carbsG'] ?? null,
                ':is_available' => isset($input['isAvailable']) ? (int) $input['isAvailable'] : 1,
            ]);

            $newId = (int) $db->lastInsertId();

            $repo = new IngredientRepository();
            $ingredient = $repo->findById($newId);

            $this->sendSuccessResponse($ingredient->toArray(), 201);
        } catch (\Exception $e) {
            $this->sendErrorResponse('Failed to create ingredient: ' . $e->getMessage(), 500);
        }
    }

    /**
     * PUT /api/ingredients/{id}
     *
     * Updates an existing ingredient.
     */
    public function update(array $vars = []): void
    {
        $this->authenticate();

        try {
            $id = (int) ($vars['id'] ?? 0);
            if ($id <= 0) {
                $this->sendErrorResponse('Invalid ingredient ID', 400);
                return;
            }

            $db = Database::getConnection();

            // Check ingredient exists
            $check = $db->prepare("SELECT id FROM ingredients WHERE id = :id");
            $check->bindValue(':id', $id, \PDO::PARAM_INT);
            $check->execute();
            if (!$check->fetch()) {
                $this->sendErrorResponse('Ingredient not found', 404);
                return;
            }

            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) {
                $this->sendErrorResponse('Request body is required', 400);
                return;
            }

            $sql = "UPDATE ingredients SET
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
                    WHERE id = :id";

            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':category_id' => $input['categoryId'] ?? null,
                ':name' => $input['name'] ?? null,
                ':name_jp' => $input['nameJp'] ?? null,
                ':description' => $input['description'] ?? null,
                ':sprite_icon' => $input['spriteIcon'] ?? null,
                ':sprite_bowl' => $input['spriteBowl'] ?? null,
                ':calories_per_serving' => $input['caloriesPerServing'] ?? null,
                ':protein_g' => $input['proteinG'] ?? null,
                ':fat_g' => $input['fatG'] ?? null,
                ':carbs_g' => $input['carbsG'] ?? null,
                ':is_available' => isset($input['isAvailable']) ? (int) $input['isAvailable'] : null,
                ':id' => $id,
            ]);

            // Return the updated ingredient
            $repo = new IngredientRepository();
            $ingredient = $repo->findById($id);

            $this->sendSuccessResponse($ingredient->toArray());
        } catch (\Exception $e) {
            $this->sendErrorResponse('Failed to update ingredient: ' . $e->getMessage(), 500);
        }
    }

    /**
     * DELETE /api/ingredients/{id}
     *
     * Deletes an ingredient by ID.
     */
    public function delete(array $vars = []): void
    {
        $this->requireAdmin();

        try {
            $id = (int) ($vars['id'] ?? 0);
            if ($id <= 0) {
                $this->sendErrorResponse('Invalid ingredient ID', 400);
                return;
            }

            $db = Database::getConnection();

            // Delete related records first (pairings, bowl_ingredients, favorite_ingredients)
            $db->prepare("DELETE FROM pairings WHERE ingredient_1_id = :id1 OR ingredient_2_id = :id2")
               ->execute([':id1' => $id, ':id2' => $id]);
            $db->prepare("DELETE FROM bowl_ingredients WHERE ingredient_id = :id")
               ->execute([':id' => $id]);
            $db->prepare("DELETE FROM favorite_ingredients WHERE ingredient_id = :id")
               ->execute([':id' => $id]);

            $stmt = $db->prepare("DELETE FROM ingredients WHERE id = :id");
            $stmt->bindValue(':id', $id, \PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->rowCount() === 0) {
                $this->sendErrorResponse('Ingredient not found', 404);
                return;
            }

            $this->sendSuccessResponse(['message' => 'Ingredient deleted']);
        } catch (\Exception $e) {
            $this->sendErrorResponse('Failed to delete ingredient: ' . $e->getMessage(), 500);
        }
    }
}
