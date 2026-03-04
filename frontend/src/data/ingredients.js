/**
 * Hardcoded ingredient data for RamenCraft
 *
 * This file contains all categories and ingredients used in the bowl builder.
 * In Phase 3, this data will be replaced with API calls to the backend.
 * For now, it serves as the single source of truth for the static frontend.
 *
 * Sprite paths (spriteIcon, spriteBowl) are placeholders — real pixel art
 * sprites will be added when they're ready.
 */

// The 5 ingredient categories, in the order they appear in the bowl builder
export const categories = [
  { id: 1, name: 'broth', displayName: 'Broth', sortOrder: 1 },
  { id: 2, name: 'noodles', displayName: 'Noodles', sortOrder: 2 },
  { id: 3, name: 'oil', displayName: 'Flavor Oil', sortOrder: 3 },
  { id: 4, name: 'protein', displayName: 'Protein', sortOrder: 4 },
  { id: 5, name: 'topping', displayName: 'Toppings', sortOrder: 5 },
]

export const ingredients = [
  // ===== BROTHS (categoryId: 1) =====
  {
    id: 1,
    categoryId: 1,
    name: 'Tonkotsu',
    nameJp: '豚骨',
    description: 'Rich, creamy pork bone broth',
    spriteIcon: '/sprites/broth-tonkotsu-icon.png',
    spriteBowl: '/sprites/broth-tonkotsu-bowl.png',
    color: '#f5e6d3', // Creamy white — used as placeholder color
  },
  {
    id: 2,
    categoryId: 1,
    name: 'Shoyu',
    nameJp: '醤油',
    description: 'Soy sauce based, clear brown',
    spriteIcon: '/sprites/broth-shoyu-icon.png',
    spriteBowl: '/sprites/broth-shoyu-bowl.png',
    color: '#8B6914',
  },
  {
    id: 3,
    categoryId: 1,
    name: 'Miso',
    nameJp: '味噌',
    description: 'Fermented soybean, opaque orange-brown',
    spriteIcon: '/sprites/broth-miso-icon.png',
    spriteBowl: '/sprites/broth-miso-bowl.png',
    color: '#D2691E',
  },
  {
    id: 4,
    categoryId: 1,
    name: 'Shio',
    nameJp: '塩',
    description: 'Salt-based, light golden',
    spriteIcon: '/sprites/broth-shio-icon.png',
    spriteBowl: '/sprites/broth-shio-bowl.png',
    color: '#F0E68C',
  },
  {
    id: 5,
    categoryId: 1,
    name: 'Tantan',
    nameJp: '担々',
    description: 'Sesame-based, spicy, red-orange',
    spriteIcon: '/sprites/broth-tantan-icon.png',
    spriteBowl: '/sprites/broth-tantan-bowl.png',
    color: '#CD5C5C',
  },
  {
    id: 6,
    categoryId: 1,
    name: 'Ebi',
    nameJp: '海老',
    description: 'Shrimp-based miso, pink-tinted',
    spriteIcon: '/sprites/broth-ebi-icon.png',
    spriteBowl: '/sprites/broth-ebi-bowl.png',
    color: '#E8A0BF',
  },
  {
    id: 7,
    categoryId: 1,
    name: 'Tori Paitan',
    nameJp: '鶏白湯',
    description: 'Chicken, creamy yellow',
    spriteIcon: '/sprites/broth-tori-paitan-icon.png',
    spriteBowl: '/sprites/broth-tori-paitan-bowl.png',
    color: '#F5DEB3',
  },
  {
    id: 8,
    categoryId: 1,
    name: 'Veggie',
    nameJp: '野菜',
    description: 'Plant-based, creamy with greenish tint',
    spriteIcon: '/sprites/broth-veggie-icon.png',
    spriteBowl: '/sprites/broth-veggie-bowl.png',
    color: '#C5E1A5',
  },

  // ===== NOODLES (categoryId: 2) =====
  {
    id: 9,
    categoryId: 2,
    name: 'Thin Straight',
    nameJp: '細麺',
    description: 'Hosomen — typical for tonkotsu',
    spriteIcon: '/sprites/noodle-thin-icon.png',
    spriteBowl: '/sprites/noodle-thin-bowl.png',
    color: '#F5DEB3',
  },
  {
    id: 10,
    categoryId: 2,
    name: 'Thick Straight',
    nameJp: '太麺',
    description: 'Thick and chewy straight noodles',
    spriteIcon: '/sprites/noodle-thick-icon.png',
    spriteBowl: '/sprites/noodle-thick-bowl.png',
    color: '#FAEBD7',
  },
  {
    id: 11,
    categoryId: 2,
    name: 'Thick Wavy',
    nameJp: '縮れ麺',
    description: 'Typical for miso ramen',
    spriteIcon: '/sprites/noodle-wavy-icon.png',
    spriteBowl: '/sprites/noodle-wavy-bowl.png',
    color: '#FFE4B5',
  },

  // ===== FLAVOR OILS (categoryId: 3) =====
  {
    id: 12,
    categoryId: 3,
    name: 'Chili Oil',
    nameJp: '辣油',
    description: 'Layu — red, spicy chili oil',
    spriteIcon: '/sprites/oil-chili-icon.png',
    spriteBowl: '/sprites/oil-chili-bowl.png',
    color: '#DC143C',
  },
  {
    id: 13,
    categoryId: 3,
    name: 'Burnt Garlic Oil',
    nameJp: 'マー油',
    description: 'Mayu — black, smoky garlic oil',
    spriteIcon: '/sprites/oil-mayu-icon.png',
    spriteBowl: '/sprites/oil-mayu-bowl.png',
    color: '#2F1B14',
  },
  {
    id: 14,
    categoryId: 3,
    name: 'Garlic Oil',
    nameJp: 'にんにく油',
    description: 'White, garlic-flavored oil',
    spriteIcon: '/sprites/oil-garlic-icon.png',
    spriteBowl: '/sprites/oil-garlic-bowl.png',
    color: '#FFFDD0',
  },
  {
    id: 15,
    categoryId: 3,
    name: 'Chicken Oil',
    nameJp: '鸡油',
    description: 'Chi yu — rich chicken fat',
    spriteIcon: '/sprites/oil-chicken-icon.png',
    spriteBowl: '/sprites/oil-chicken-bowl.png',
    color: '#FFD700',
  },
  {
    id: 16,
    categoryId: 3,
    name: 'Back Fat',
    nameJp: '背脂',
    description: 'Sei abura — pork back fat',
    spriteIcon: '/sprites/oil-backfat-icon.png',
    spriteBowl: '/sprites/oil-backfat-bowl.png',
    color: '#FFF8DC',
  },

  // ===== PROTEINS (categoryId: 4) =====
  {
    id: 17,
    categoryId: 4,
    name: 'Pork Chashu',
    nameJp: 'チャーシュー',
    description: 'Rolled pork belly slice',
    spriteIcon: '/sprites/protein-chashu-icon.png',
    spriteBowl: null, // Same sprite for icon and bowl
    color: '#CD853F',
  },
  {
    id: 18,
    categoryId: 4,
    name: 'Chicken Chashu',
    nameJp: '鶏チャーシュー',
    description: 'Lighter colored chicken slice',
    spriteIcon: '/sprites/protein-chicken-icon.png',
    spriteBowl: null,
    color: '#F5DEB3',
  },
  {
    id: 19,
    categoryId: 4,
    name: 'Ajitama',
    nameJp: '味玉',
    description: 'Marinated soft-boiled egg, halved',
    spriteIcon: '/sprites/protein-ajitama-icon.png',
    spriteBowl: null,
    color: '#FFA500',
  },
  {
    id: 20,
    categoryId: 4,
    name: 'Seitan Katsu',
    nameJp: 'セイタンカツ',
    description: 'Breaded, golden — plant-based option',
    spriteIcon: '/sprites/protein-seitan-icon.png',
    spriteBowl: null,
    color: '#DAA520',
  },
  {
    id: 21,
    categoryId: 4,
    name: 'Karaage',
    nameJp: '唐揚げ',
    description: 'Japanese fried chicken pieces',
    spriteIcon: '/sprites/protein-karaage-icon.png',
    spriteBowl: null,
    color: '#D2691E',
  },
  {
    id: 22,
    categoryId: 4,
    name: 'Cauliflower Tempura',
    nameJp: 'カリフラワー天ぷら',
    description: 'Plant-based tempura option',
    spriteIcon: '/sprites/protein-cauliflower-icon.png',
    spriteBowl: null,
    color: '#FFFACD',
  },

  // ===== TOPPINGS (categoryId: 5) =====
  {
    id: 23,
    categoryId: 5,
    name: 'Corn',
    nameJp: 'コーン',
    description: 'Sweet corn kernels',
    spriteIcon: '/sprites/topping-corn-icon.png',
    spriteBowl: null,
    color: '#FFD700',
  },
  {
    id: 24,
    categoryId: 5,
    name: 'Bean Sprouts',
    nameJp: 'もやし',
    description: 'Moyashi — fresh bean sprouts',
    spriteIcon: '/sprites/topping-sprouts-icon.png',
    spriteBowl: null,
    color: '#F5F5DC',
  },
  {
    id: 25,
    categoryId: 5,
    name: 'Spinach',
    nameJp: 'ほうれん草',
    description: 'Hōrenso — blanched spinach',
    spriteIcon: '/sprites/topping-spinach-icon.png',
    spriteBowl: null,
    color: '#228B22',
  },
  {
    id: 26,
    categoryId: 5,
    name: 'Nori',
    nameJp: '海苔',
    description: 'Dried seaweed sheet',
    spriteIcon: '/sprites/topping-nori-icon.png',
    spriteBowl: null,
    color: '#1B3D2F',
  },
  {
    id: 27,
    categoryId: 5,
    name: 'Menma',
    nameJp: 'メンマ',
    description: 'Fermented bamboo shoots',
    spriteIcon: '/sprites/topping-menma-icon.png',
    spriteBowl: null,
    color: '#C4A35A',
  },
  {
    id: 28,
    categoryId: 5,
    name: 'Negi',
    nameJp: 'ねぎ',
    description: 'Sliced green onion',
    spriteIcon: '/sprites/topping-negi-icon.png',
    spriteBowl: null,
    color: '#3CB371',
  },
  {
    id: 29,
    categoryId: 5,
    name: 'Narutomaki',
    nameJp: 'なると巻き',
    description: 'Fish cake with pink swirl',
    spriteIcon: '/sprites/topping-naruto-icon.png',
    spriteBowl: null,
    color: '#FFB6C1',
  },
]

/**
 * Helper: get all ingredients for a specific category
 * @param {number} categoryId - The category ID to filter by
 * @returns {Array} Ingredients belonging to that category
 */
export function getIngredientsByCategory(categoryId) {
  return ingredients.filter(ingredient => ingredient.categoryId === categoryId)
}

/**
 * Helper: find a single ingredient by its ID
 * @param {number} id - The ingredient ID
 * @returns {Object|undefined} The ingredient object, or undefined if not found
 */
export function getIngredientById(id) {
  return ingredients.find(ingredient => ingredient.id === id)
}
