<?php

namespace App\Services;

use App\Config\Database;

/**
 * NutritionApiService — fetches nutrition data from API Ninjas (api-ninjas.com)
 *
 * Calls the external API server-side and caches responses in the nutrition_cache
 * table to avoid repeated requests. Free tier: 10,000 calls/month.
 *
 * API endpoint: GET https://api.api-ninjas.com/v1/nutrition?query=X
 * Auth: X-Api-Key header
 */
class NutritionApiService
{
    private const BASE_URL = 'https://api.api-ninjas.com/v1/nutrition';

    /**
     * Mapping of ingredient names to English search terms for the API.
     * Japanese ramen terms need translation for accurate nutrition results.
     */
    private const SEARCH_TERMS = [
        'Tonkotsu'            => 'pork bone broth 1 cup',
        'Shoyu'               => 'soy sauce broth 1 cup',
        'Miso'                => 'miso soup 1 cup',
        'Shio'                => 'chicken broth 1 cup',
        'Tantan'              => 'sesame paste soup 1 cup',
        'Ebi'                 => 'shrimp soup 1 cup',
        'Tori Paitan'         => 'chicken broth 1 cup',
        'Veggie'              => 'vegetable broth 1 cup',
        'Thin Straight'       => 'ramen noodles 1 serving',
        'Thick Straight'      => 'ramen noodles 1 serving',
        'Thick Wavy'          => 'ramen noodles 1 serving',
        'Chili Oil'           => 'chili oil 1 tablespoon',
        'Burnt Garlic Oil'    => 'garlic oil 1 tablespoon',
        'Garlic Oil'          => 'garlic oil 1 tablespoon',
        'Chicken Oil'         => 'chicken fat 1 tablespoon',
        'Back Fat'            => 'pork fat lard 1 tablespoon',
        'Pork Chashu'         => 'pork belly sliced 2 oz',
        'Chicken Chashu'      => 'chicken breast sliced 2 oz',
        'Ajitama'             => 'soft boiled egg 1',
        'Seitan Katsu'        => 'seitan 3 oz',
        'Karaage'             => 'fried chicken 3 oz',
        'Cauliflower Tempura' => 'cauliflower tempura 3 oz',
        'Corn'                => 'sweet corn 2 tablespoons',
        'Bean Sprouts'        => 'bean sprouts 1/4 cup',
        'Spinach'             => 'spinach cooked 2 tablespoons',
        'Nori'                => 'nori seaweed 1 sheet',
        'Menma'               => 'bamboo shoots 2 tablespoons',
        'Negi'                => 'green onion 1 tablespoon',
        'Narutomaki'          => 'fish cake 1 slice',
    ];

    /**
     * Get nutrition data for an ingredient by name.
     * Checks the DB cache first; if miss, calls the external API.
     *
     * @param string $ingredientName  The ingredient name (e.g. "Tonkotsu")
     * @return array  Nutrition data with fat_g, carbs_g, sodium_mg, etc.
     */
    public function getNutrition(string $ingredientName): array
    {
        $searchQuery = self::SEARCH_TERMS[$ingredientName] ?? $ingredientName;
        $hash = hash('sha256', strtolower(trim($searchQuery)));

        // 1. Check cache
        $cached = $this->getFromCache($hash);
        if ($cached !== null) {
            return $this->extractNutrients($cached);
        }

        // 2. Call external API
        $response = $this->callApi($searchQuery);

        // 3. Cache the raw response
        $this->saveToCache($hash, $response);

        return $this->extractNutrients($response);
    }

    /**
     * Look up cached API response by ingredient hash.
     */
    private function getFromCache(string $hash): ?array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            "SELECT response_json FROM nutrition_cache WHERE ingredient_hash = :hash"
        );
        $stmt->execute([':hash' => $hash]);
        $row = $stmt->fetch();

        if ($row) {
            return json_decode($row['response_json'], true);
        }
        return null;
    }

    /**
     * Store an API response in the cache.
     */
    private function saveToCache(string $hash, array $response): void
    {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            "INSERT INTO nutrition_cache (ingredient_hash, response_json)
             VALUES (:hash, :json)
             ON DUPLICATE KEY UPDATE response_json = :json2"
        );
        $json = json_encode($response);
        $stmt->execute([
            ':hash'  => $hash,
            ':json'  => $json,
            ':json2' => $json,
        ]);
    }

    /**
     * Call the API Ninjas nutrition endpoint.
     *
     * @param string $query  Natural language query (e.g. "pork belly sliced 2 oz")
     * @return array  The raw API response (array of food items)
     */
    private function callApi(string $query): array
    {
        $apiKey = $_ENV['NUTRITION_API_KEY'] ?? getenv('NUTRITION_API_KEY') ?? '';

        $url = self::BASE_URL . '?' . http_build_query(['query' => $query]);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'X-Api-Key: ' . $apiKey,
        ]);
        $body = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        unset($ch);

        if ($body === false || $httpCode !== 200) {
            return [];
        }

        $decoded = json_decode($body, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Extract and sum nutrition from the API response.
     * API Ninjas returns an array of items (one per food in the query).
     * We sum them all to get the total for the ingredient.
     *
     * Free tier fields: fat_total_g, fat_saturated_g, carbohydrates_total_g,
     * fiber_g, sugar_g, sodium_mg, potassium_mg, cholesterol_mg, serving_size_g.
     * Premium-only (returned as string): calories, protein_g.
     */
    private function extractNutrients(array $items): array
    {
        if (empty($items)) {
            return [
                'fat_g'     => 0,
                'carbs_g'   => 0,
                'fiber_g'   => 0,
                'sugar_g'   => 0,
                'sodium_mg' => 0,
                'serving_size_g' => 0,
                'source'    => 'none',
            ];
        }

        $totals = [
            'fat_g'     => 0,
            'carbs_g'   => 0,
            'fiber_g'   => 0,
            'sugar_g'   => 0,
            'sodium_mg' => 0,
            'serving_size_g' => 0,
        ];

        $names = [];

        foreach ($items as $item) {
            $totals['fat_g']          += (float) ($item['fat_total_g'] ?? 0);
            $totals['carbs_g']        += (float) ($item['carbohydrates_total_g'] ?? 0);
            $totals['fiber_g']        += (float) ($item['fiber_g'] ?? 0);
            $totals['sugar_g']        += (float) ($item['sugar_g'] ?? 0);
            $totals['sodium_mg']      += (int) ($item['sodium_mg'] ?? 0);
            $totals['serving_size_g'] += (float) ($item['serving_size_g'] ?? 0);
            $names[] = $item['name'] ?? 'Unknown';
        }

        // Round values
        foreach (['fat_g', 'carbs_g', 'fiber_g', 'sugar_g'] as $key) {
            $totals[$key] = round($totals[$key], 1);
        }

        $totals['source'] = 'api-ninjas';
        $totals['items'] = $names;

        return $totals;
    }
}
