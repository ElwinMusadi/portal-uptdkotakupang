/** true di macOS/iOS: pintasan memakai ⌘, selain itu Ctrl */
export const IS_MAC =
  typeof navigator !== "undefined" && /Mac|iPhone|iPad|iPod/i.test(navigator.userAgent)

export const TOMBOL_MOD = IS_MAC ? "⌘" : "Ctrl"
