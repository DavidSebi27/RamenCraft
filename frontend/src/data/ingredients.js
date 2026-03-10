/**
 * Ingredient placeholder colors for the bowl builder.
 *
 * Until real pixel art sprites are ready, the bowl builder uses
 * these colors to render ingredient placeholders. Keyed by ingredient name.
 *
 * This map is also used to enrich API data with a `color` property
 * so components can render colored fallbacks.
 */
export const ingredientColors = {
  // Broths
  'Tonkotsu': '#f5e6d3',
  'Shoyu': '#8B6914',
  'Miso': '#D2691E',
  'Shio': '#F0E68C',
  'Tantan': '#CD5C5C',
  'Ebi': '#E8A0BF',
  'Tori Paitan': '#F5DEB3',
  'Veggie': '#C5E1A5',
  // Noodles
  'Thin Straight': '#F5DEB3',
  'Thick Straight': '#FAEBD7',
  'Thick Wavy': '#FFE4B5',
  // Flavor Oils
  'Chili Oil': '#DC143C',
  'Burnt Garlic Oil': '#2F1B14',
  'Garlic Oil': '#FFFDD0',
  'Chicken Oil': '#FFD700',
  'Back Fat': '#FFF8DC',
  // Proteins
  'Pork Chashu': '#CD853F',
  'Chicken Chashu': '#F5DEB3',
  'Ajitama': '#FFA500',
  'Seitan Katsu': '#DAA520',
  'Karaage': '#D2691E',
  'Cauliflower Tempura': '#FFFACD',
  // Toppings
  'Corn': '#FFD700',
  'Bean Sprouts': '#F5F5DC',
  'Spinach': '#228B22',
  'Nori': '#1B3D2F',
  'Menma': '#C4A35A',
  'Negi': '#3CB371',
  'Narutomaki': '#FFB6C1',
}

/**
 * Adds a `color` property to each ingredient from the color map.
 * Falls back to a neutral gray if the ingredient isn't in the map.
 */
export function enrichWithColors(ingredients) {
  return ingredients.map(ing => ({
    ...ing,
    color: ingredientColors[ing.name] || '#888888',
  }))
}
