/**
 * Sound effects utility for RamenCraft
 *
 * Uses Kenney's CC0 audio packs. All sounds are preloaded as Audio objects.
 * Call playSound('click') etc. from any component.
 */

import clickSfx from '@/assets/kenney_ui-audio/Audio/click3.ogg'
import hoverSfx from '@/assets/kenney_ui-audio/Audio/rollover1.ogg'
import selectSfx from '@/assets/kenney_interface-sounds/Audio/confirmation_002.ogg'
import serveSfx from '@/assets/kenney_digital-audio/Audio/powerUp7.ogg'
import achievementSfx from '@/assets/kenney_digital-audio/Audio/powerUp12.ogg'
import errorSfx from '@/assets/kenney_interface-sounds/Audio/error_004.ogg'
import skipSfx from '@/assets/kenney_interface-sounds/Audio/switch_002.ogg'
import backSfx from '@/assets/kenney_interface-sounds/Audio/back_001.ogg'

const sounds = {
  click: clickSfx,
  hover: hoverSfx,
  select: selectSfx,
  serve: serveSfx,
  achievement: achievementSfx,
  error: errorSfx,
  skip: skipSfx,
  back: backSfx,
}

/**
 * Play a named sound effect.
 * @param {string} name - One of: click, hover, select, serve, achievement, error, skip, back
 * @param {number} volume - Volume from 0 to 1 (default 0.3)
 */
export function playSound(name, volume = 0.3) {
  const src = sounds[name]
  if (!src) return

  const audio = new Audio(src)
  audio.volume = volume
  audio.play().catch(() => {
    // Browser may block autoplay before user interaction — ignore silently
  })
}
