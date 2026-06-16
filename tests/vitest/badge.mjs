/**
 * Format an unread message count for the topbar inbox badge.
 *
 * Mirrors the logic embedded in popup.setNewBadge inside
 * views/default/framework/inbox/popup.js, extracted to a pure helper
 * so it can be unit-tested in isolation from the DOM. The AMD popup
 * module should be refactored to delegate to this helper in a
 * follow-up — see bead elgg-migrate-1y7z and the JS test policy in
 * SKILL.md.
 *
 * Rules (locked in by the test suite):
 *   - 0 unread → text '0', hidden
 *   - 1..99    → text 'N', visible
 *   - 100+     → text '99+', visible
 *   - non-int / negative → coerced to 0 → text '0', hidden
 */
export function formatBadge(unread) {
  const n = Number.isFinite(unread) && unread > 0 ? Math.floor(unread) : 0;
  if (n === 0) {
    return { text: '0', hidden: true };
  }
  if (n > 99) {
    return { text: '99+', hidden: false };
  }
  return { text: String(n), hidden: false };
}
