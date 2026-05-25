# Phase 4 — Premium SaaS UI (CI3)

This pass adds a **token layer** and **shared UI primitives** without changing controllers or business logic.

## Files

| File | Role |
|------|------|
| `assets/css/ka-saas-tokens.css` | Spacing, radius, typography, shadows, semantic aliases (`--ka-space-*`, `--ka-radius-lg`, `--ka-shadow-sm`, …) |
| `assets/css/ka-saas-ui.css` | Shell, page headers, premium cards (`.crs-panel`, `.crt-panel`, `.db-card`, …), forms, tables, empty states, modals, SweetAlert2 toast styling |
| `assets/js/ka-saas-shell.js` | `Escape` closes module modal (`#modModalOverlay`) or question modal (`#qModalOverlay`) |
| `application/views/components/empty_state.php` | Reusable empty state (emoji, title, description, optional CTA) |

## Layout

- `layouts/header.php` — loads token + UI CSS after Tabler.
- `layouts/main.php` — wraps page content in `<main class="ka-saas-shell">`.
- `layouts/footer.php` — loads `ka-saas-shell.js` after jQuery.
- `layouts/alerts.php` — `KA.toast` uses consistent timer (4200ms), typed `customClass`, CSS variables for surface/text.

## Views touched (examples)

- `manage_courses/create.php`, `edit.php` — `ka-form-flow` for field rhythm; modules empty → `empty_state`; modal scroll lock.
- `assessments/create.php` — `ka-page-head` / `ka-btn` (no inline layout styles); empty modules → `empty_state`; `ka-req-star` for required marker.
- `assessments/edit.php` — questions empty → `empty_state` with `id="qEmpty"`.
- `assessments/index.php` — grid empty → `empty_state`.

## Extending

1. Prefer **classes** (`ka-page-head`, `ka-empty`, `ka-form-flow`) over inline styles on new screens.
2. Pull repeated empty blocks into `components/empty_state.php`.
3. Add new tokens only in `ka-saas-tokens.css`; component rules in `ka-saas-ui.css`.
