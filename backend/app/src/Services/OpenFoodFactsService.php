<?php

namespace App\Services;

use App\Config\Database;

/**
 * OpenFoodFactsService — fetches nutrition data from Open Food Facts API
 *
 * Calls the public API server-side and caches responses in the nutrition_cache table
 * to avoid repeated external requests. No API key required.
 *
 * API endpoint: https://world.openfoodfacts.org/cgi/search.pl?search_terms=X&json=1
 */
class OpenFoodFactsService
{
    private const BASE_URL = 'https://world.openfoodfacts.org/cgi/search.pl';

    /**
     * Mapping of ingredient names to better English search terms.
     * Open Food Facts has mostly English product names, so Japanese ramen
     * terms need translation for accurate results.
     */
    private const SEARCH_TERMS = [
        'Tonkotsu'           => 'pork bone broth ramen',
        'Shoyu'              => 'soy sauce ramen broth',
        'Miso'               => 'miso ramen broth',
        'Shio'               => 'salt ramen broth',
        'Tantan'             => 'spicy sesame ramen broth',
        'Ebi'                => 'shrimp broth',
        'Tori Paitan'        => 'chicken bone broth',
        'Veggie'             => 'vegetable broth',
        'Thin Straight'      => 'ramen noodles thin',
        'Thick Straight'     => 'ramen noodles thick',
        'Thick Wavy'         => 'ramen noodles wavy',
        'Chili Oil'          => 'chili oil',
        'Burnt Garlic Oil'   => 'garlic oil',
        'Garlic Oil'         => 'garlic oil',
        'Chicken Oil'        => 'chicken fat schmaltz',
        'Back Fat'           => 'pork back fat lard',
        'Pork Chashu'        => 'pork belly chashu',
        'Chicken Chashu'     => 'chicken breast sliced',
        'Ajitama'            => 'marinated soft boiled egg',
        'Seitan Katsu'       => 'seitan cutlet breaded',
        'Karaage'            => 'japanese fried chicken',
        'Cauliflower Tempura' => 'cauliflower tempura',
        'Corn'               => 'sweet corn kernels',
        'Bean Sprouts'       => 'bean sprouts mung',
        'Spinach'            => 'spinach blanched',
        'Nori'               => 'nori seaweed sheet',
        'Menma'              => 'bamboo shoots fermented',
        'Negi'               => 'green onion scallion',
        'Narutomaki'         => 'narutomaki fish cake',
    ];

    /**
     * Get nutrition data for an ingredient by name.
     * Checks the DB cache first; if not found, calls the external API.
     *
     * @param string $ingredientName  e.g. "tonkotsu pork bone broth"
     * @return array  Nutrition data: calories, protein_g, fat_g, carbs_g
     */
    public function getNutrition(string $ingredientName): array
    {
        // Use the English search term mapping if available
        $searchQuery = self::SEARCH_TERMS[$ingredientName] ?? $ingredientName;
        $hash = hash('sha256', strtolower(trim($searchQuery)));

        // 1. Check cache
        $cached = $this->getFromCache($hash);
        if ($cached !== null) {
            return $this->extractNutrients($cached);
        }

        // 2. Call external API
        $response = $this->callApi($searchQuery);

        // 3. Only cache if we got actual products (don't cache empty/failed responses)
        if (!empty($response['products'])) {
            $this->saveToCache($hash, $response);
        }

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
     * Call the Open Food Facts search API.
     *
     * @param string $query  Search terms (e.g. "miso ramen broth")
     * @return array  The decoded JSON response
     */
    private function callApi(string $query): array
    {
        $url = self::BASE_URL . '?' . http_build_query([
            'search_terms' => $query,
            'json'         => 1,
            'page_size'    => 5,
        ]);

        $context = stream_context_create([
            'http' => [
                'method'  => 'GET',
                'timeout' => 10,
                'header'  => "User-Agent: RamenCraft/1.0 (student project)\r\n",
            ],
        ]);

        // Try file_get_contents first, fall back to curl
        $body = @file_get_contents($url, false, $context);

        if ($body === false) {
            // Fallback: try curl if available
            if (function_exists('curl_init')) {
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'User-Agent: RamenCraft/1.0 (student-project; contact@example.com)',
                ]);
                $body = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

                if ($body === false || $httpCode !== 200) {
                    return ['products' => []];
                }
            } else {
                return ['products' => []];
            }
        }

        $decoded = json_decode($body, true);
        return $decoded ?: ['products' => []];
    }

    /**
     * Extract per-serving nutrition from the API response.
     * Takes the first product that has nutriment data.
     *
     * @param array $response  The full API response
     * @return array  [calories, protein_g, fat_g, carbs_g]
     */
    private function extractNutrients(array $response): array
    {
        $products = $response['products'] ?? [];

        foreach ($products as $product) {
            $n = $product['nutriments'] ?? [];

            // Only use products that have at least calorie data
            if (!empty($n['energy-kcal_100g']) || !empty($n['energy-kcal'])) {
                return [
                    'calories'  => round((float) ($n['energy-kcal_100g'] ?? $n['energy-kcal'] ?? 0), 1),
                    'protein_g' => round((float) ($n['proteins_100g'] ?? $n['proteins'] ?? 0), 1),
                    'fat_g'     => round((float) ($n['fat_100g'] ?? $n['fat'] ?? 0), 1),
                    'carbs_g'   => round((float) ($n['carbohydrates_100g'] ?? $n['carbohydrates'] ?? 0), 1),
                    'source'    => 'openfoodfacts',
                    'product_name' => $product['product_name'] ?? 'Unknown',
                ];
            }
        }

        // No results — return zeros
        return [
            'calories'  => 0,
            'protein_g' => 0,
            'fat_g'     => 0,
            'carbs_g'   => 0,
            'source'    => 'none',
            'product_name' => null,
        ];
    }
}
