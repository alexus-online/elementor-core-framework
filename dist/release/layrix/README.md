# Layrix

WordPress plugin for managing design tokens, typography, spacing, shadows, utility classes and Elementor sync from one structured admin UI.

## What It Does

- Defines reusable site tokens for colors, radius, spacing, typography and shadows
- Manages starter classes and utility classes for Elementor
- Syncs selected variables and classes into Elementor without overwriting unrelated existing data
- Exports and imports plugin settings as JSON
- Provides UI flows and browser-based regression checks for important admin interactions

## Main Areas

- `Colors & Radius`
- `Typography`
- `Spacing`
- `Shadows`
- `Variables`
- `Classes`
- `Sync & Export`
- `General Settings`
- `Help`

## Recommended First Steps

1. Open `General Settings`
2. Set root font size, body text size, body font, heading font and base colors
3. Adjust token systems in `Colors & Radius`, `Typography`, `Spacing` and `Shadows`
4. Review starter classes and utility classes
5. Run `Sync & Export` and reload open Elementor tabs once

## Architecture Notes

- PHP traits in `includes/` contain most admin rendering, sync logic, config defaults and sanitization
- `assets/admin.js` drives the admin UI, autosave, layout persistence and panel interactions
- `assets/admin.css` contains the admin design system and responsive behavior
- user-facing texts are translated through the `ecf-framework` textdomain
- one-off redirect notices are handled as queued flash notices instead of URL message payloads

## Testing

### Local sanity checks

```bash
./scripts/regression-check.sh
./scripts/smoke-check.sh
```

### Browser UI flows

```bash
bash scripts/e2e-ui-check.sh
```

These tests run against a real WordPress admin instance in a real browser. They need valid admin login credentials so Playwright can sign in and open the Layrix backend.

Required environment variables:

```bash
export ECF_WP_URL="https://example.com"
export ECF_WP_LOGIN_PATH="/wp-login.php"
export ECF_WP_ADMIN_USER="admin"
export ECF_WP_ADMIN_PASSWORD="secret"
```

Optional for mutation-heavy flows:

```bash
export ECF_UI_ALLOW_MUTATION=1
```

## Development Rules

- New UI features should get a matching UI flow
- User-visible strings belong in the textdomain and translation files
- Avoid putting sensitive live-site URLs into changelogs, docs or release notes
- Keep the internal developer guide in `tmp/docu/plugin-entwicklungsleitfaden.md` updated when architecture or workflow rules change
