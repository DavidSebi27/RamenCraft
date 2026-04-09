<?php

namespace App\Controllers;

use App\Framework\Controller;
use App\Models\Ingredient;
use App\Repositories\IngredientRepository;
use App\Services\OpenFoodFactsService;

/**
 * NutritionController — HTTP layer for external nutrition API.
 *
 * No SQL here. Ingredient data via IngredientRepository.
 * External API calls via OpenFoodFactsService.
 */
class NutritionController extends Controller
{
    /**
     * GET /api/nutrition/ingredient/{id}
     */
    public function getByIngredient(array $vars = []): void
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

            $service = new OpenFoodFactsService();
            $nutrition = $service->getNutrition($ingredient->name);

            $this->sendSuccessResponse([
                'ingredientId'   => (int) $ingredient->id,
                'ingredientName' => $ingredient->name,
                'nutrition'      => $nutrition,
            ]);
        } catch (\Exception $e) {
            error_log($e->getMessage());
            $this->sendErrorResponse('Failed to fetch nutrition', 500);
        }
    }

    /**
     * POST /api/nutrition/seed
     *
     * Admin-only. Fetches nutrition from Open Food Facts for all ingredients
     * and updates the database with real data.
     */
    public function seedAll(): void
    {
        $this->requireAdmin();

        try {
            $repo = new IngredientRepository();
            $ingredients = $repo->findAllForSeeding();
            $service = new OpenFoodFactsService();
            $results = [];

            foreach ($ingredients as $ingredient) {
                $nutrition = $service->getNutrition($ingredient->name);

                if ($nutrition['source'] === 'openfoodfacts') {
                    $repo->updateNutrition(
                        (int) $ingredient->id,
                        (float) $nutrition['calories'],
                        (float) $nutrition['protein_g'],
                        (float) $nutrition['fat_g'],
                        (float) $nutrition['carbs_g']
                    );
                }

                $results[] = [
                    'id'        => (int) $ingredient->id,
                    'name'      => $ingredient->name,
                    'nutrition'  => $nutrition,
                    'updated'   => $nutrition['source'] === 'openfoodfacts',
                ];

                usleep(500000);
            }

            $this->sendSuccessResponse([
                'message' => 'Nutrition data seeded for ' . count($results) . ' ingredients',
                'results' => $results,
            ]);
        } catch (\Exception $e) {
            error_log($e->getMessage());
            $this->sendErrorResponse('Failed to seed nutrition', 500);
        }
    }
}
