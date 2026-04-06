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
 * Sprite icon mappings — maps ingredient names to Panna's pixel art PNGs.
 * These are used as the icon in IngredientCard.
 */
import tonkotsuIcon from '@/assets/Graphics/tonkotsu.png'
import shoyuIcon from '@/assets/Graphics/shoyu.png'
import misoIcon from '@/assets/Graphics/miso.png'
import shioIcon from '@/assets/Graphics/shio.png'
import tantanIcon from '@/assets/Graphics/tantan.png'
import ebiIcon from '@/assets/Graphics/ebi.png'
import toriPaitanIcon from '@/assets/Graphics/toripaitan.png'
import veggieIcon from '@/assets/Graphics/veggie.png'
import thinNoodleIcon from '@/assets/Graphics/thin.pasta.ikon.png'
import thickNoodleIcon from '@/assets/Graphics/thick.pasta.ikon.png'
import wavyNoodleIcon from '@/assets/Graphics/wavy.pasta.ikon.png'
import chiliOilIcon from '@/assets/Graphics/chili.oil.in.bowl.png'
import mayuOilIcon from '@/assets/Graphics/mayu.oil.in.bowl.png'
import garlicOilIcon from '@/assets/Graphics/garlic.oil.in.bowl.png'
import chickenOilIcon from '@/assets/Graphics/chicken.oil.in.bowl.png'
import backFatIcon from '@/assets/Graphics/sei.ebura.oil.in.bowl.png'
import porkChashuIcon from '@/assets/Graphics/protein.pork.char.siu.png'
import ajitamaIcon from '@/assets/Graphics/proteins.eggs.png'
import seitanIcon from '@/assets/Graphics/proteins.seitan.katsu.png'
import karaageIcon from '@/assets/Graphics/proteins.karaage.png'
import cornIcon from '@/assets/Graphics/toppings.corn.png'
import sproutsIcon from '@/assets/Graphics/toppings.sprout.png'
import spinachIcon from '@/assets/Graphics/toppings.spinach.png'
import noriIcon from '@/assets/Graphics/toppings.nori.png'
import menmaIcon from '@/assets/Graphics/toppings.menma.png'
import negiIcon from '@/assets/Graphics/toppings.onions.png'
import narutoIcon from '@/assets/Graphics/toppings.narutomaki.png'
import cauliflowerIcon from '@/assets/Graphics/proteins.cauliflower.png'

// In-bowl sprites for the BowlBuilder
import thinNoodleBowl from '@/assets/Graphics/thin.pasta.in.bowl.png'
import thickNoodleBowl from '@/assets/Graphics/thick.pasta.in.bowl.png'
import wavyNoodleBowl from '@/assets/Graphics/wavy.pasta.in.bowl.png'
import chiliOilSoup from '@/assets/Graphics/chili.oil.in.soup.png'
import mayuOilSoup from '@/assets/Graphics/black.garlic.oil.in.soup.png'
import garlicOilSoup from '@/assets/Graphics/garlic.oil.in.soup.png'
import chickenOilSoup from '@/assets/Graphics/chicken.oil.in.soup.png'
import backFatSoup from '@/assets/Graphics/sei.abura.oil.in.soup.png'

export const ingredientSprites = {
  // Icons (shown in IngredientCard)
  'Tonkotsu': { icon: tonkotsuIcon },
  'Shoyu': { icon: shoyuIcon },
  'Miso': { icon: misoIcon },
  'Shio': { icon: shioIcon },
  'Tantan': { icon: tantanIcon },
  'Ebi': { icon: ebiIcon },
  'Tori Paitan': { icon: toriPaitanIcon },
  'Veggie': { icon: veggieIcon },
  'Thin Straight': { icon: thinNoodleIcon, bowl: thinNoodleBowl },
  'Thick Straight': { icon: thickNoodleIcon, bowl: thickNoodleBowl },
  'Thick Wavy': { icon: wavyNoodleIcon, bowl: wavyNoodleBowl },
  'Chili Oil': { icon: chiliOilIcon, soup: chiliOilSoup },
  'Burnt Garlic Oil': { icon: mayuOilIcon, soup: mayuOilSoup },
  'Garlic Oil': { icon: garlicOilIcon, soup: garlicOilSoup },
  'Chicken Oil': { icon: chickenOilIcon, soup: chickenOilSoup },
  'Back Fat': { icon: backFatIcon, soup: backFatSoup },
  'Pork Chashu': { icon: porkChashuIcon },
  'Chicken Chashu': { icon: null }, // CSS fallback — pinkish chicken blob
  'Ajitama': { icon: ajitamaIcon },
  'Seitan Katsu': { icon: seitanIcon },
  'Karaage': { icon: karaageIcon },
  'Cauliflower Tempura': { icon: cauliflowerIcon },
  'Corn': { icon: cornIcon },
  'Bean Sprouts': { icon: sproutsIcon },
  'Spinach': { icon: spinachIcon },
  'Nori': { icon: noriIcon },
  'Menma': { icon: menmaIcon },
  'Negi': { icon: negiIcon },
  'Narutomaki': { icon: narutoIcon },
}

/**
 * Adds color + sprite properties to each ingredient from the maps.
 * Falls back to colored circle if no sprite exists.
 */
export function enrichWithColors(ingredients) {
  return ingredients.map(ing => {
    const sprites = ingredientSprites[ing.name] || {}
    return {
      ...ing,
      color: ingredientColors[ing.name] || '#888888',
      spriteIcon: sprites.icon !== undefined ? sprites.icon : (ing.spriteIcon || null),
      spriteBowl: sprites.bowl || null,
      spriteSoup: sprites.soup || null,
    }
  })
}
