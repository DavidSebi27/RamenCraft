<?php

namespace App\Controllers;

use App\Config\Database;
use App\Framework\Controller;
use App\Services\NutritionApiService;

/**
 * NutritionController — fetches nutrition data from Open Food Facts
 *
 * Endpoints:
 *   GET  /api/nutrition/ingredient/{id} — get nutrition for one ingredient (cached)
 *   POST /api/nutrition/seed            — admin: batch-fetch nutrition for all ingredients
 */
class NutritionController extends Controller
{
    /**
     * GET /api/nutrition/ingredient/{id}
     *
     * Returns nutrition data for a single ingredient.
     * Calls the external API (server-side) and caches the result.
     */
    public function getByIngredient(array $vars = []): void
    {
        try {
            $id = (int) ($vars['id'] ?? 0);
            if ($id <= 0) {
                $this->sendErrorResponse('Invalid ingredient ID', 400);
                return;
            }

            $db = Database::getConnection();

            $stmt = $db->prepare("SELECT id, name, description FROM ingredients WHERE id = :id");
            $stmt->bindValue(':id', $id, \PDO::PARAM_INT);
            $stmt->execute();
            $ingredient = $stmt->fetch();

            if (!$ingredient) {
                $this->sendErrorResponse('Ingredient not found', 404);
                return;
            }

            // Pass ingredient name — the service maps it to English search terms
            $service = new NutritionApiService();
            $nutrition = $service->getNutrition($ingredient['name']);

            $this->sendSuccessResponse([
                'ingredientId'   => (int) $ingredient['id'],
                'ingredientName' => $ingredient['name'],
                'nutrition'      => $nutrition,
            ]);
        } catch (\Exception $e) {
            $this->sendErrorResponse('Failed to fetch nutrition: ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/nutrition/seed
     *
     * Admin-only. Loops through all ingredients, fetches nutrition from
     * Open Food Facts, and updates the ingredients table with real data.
     * Respects rate limits with a small delay between API calls.
     */
    public function seedAll(): void
    {
        $this->requireAdmin();

        try {
            $db = Database::getConnection();

            $stmt = $db->query("SELECT id, name, description FROM ingredients ORDER BY id");
            $ingredients = $stmt->fetchAll();

            $service = new NutritionApiService();
            $results = [];

            foreach ($ingredients as $ingredient) {
                $nutrition = $service->getNutrition($ingredient['name']);

                // Update the ingredient row with API nutrition data (fat + carbs from free tier)
                if ($nutrition['source'] === 'api-ninjas') {
                    $update = $db->prepare(
                        "UPDATE ingredients SET
                            fat_g = :fat,
                            carbs_g = :carbs
                         WHERE id = :id"
                    );
                    $update->execute([
                        ':fat'   => $nutrition['fat_g'],
                        ':carbs' => $nutrition['carbs_g'],
                        ':id'    => $ingredient['id'],
                    ]);
                }

                $results[] = [
                    'id'        => (int) $ingredient['id'],
                    'name'      => $ingredient['name'],
                    'nutrition'  => $nutrition,
                    'updated'   => $nutrition['source'] === 'openfoodfacts',
                ];

                // Small delay to be polite to the API (no rate limit, but good practice)
                usleep(500000); // 0.5 seconds
            }

            $this->sendSuccessResponse([
                'message' => 'Nutrition data seeded for ' . count($results) . ' ingredients',
                'results' => $results,
            ]);
        } catch (\Exception $e) {
            $this->sendErrorResponse('Failed to seed nutrition: ' . $e->getMessage(), 500);
        }
    }
}
