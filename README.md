# WP Counter Plugin

A lightweight, secure WordPress counter with an admin settings page and a
`[counter]` shortcode. Visitors press **+** and the value increases by the
configured step **without reloading the page** (REST/AJAX); the value is saved
to the database and shared by every place the shortcode is used.

## Features

| Area        | What you get                                                                                                                                                             |
| ----------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| Admin menu  | Settings → **WP Counter** (`add_options_page()` on `admin_menu`)                                                                                                         |
| Settings    | Settings API: `register_setting()`, `add_settings_section()`, `add_settings_field()`                                                                                     |
| Fields      | Title, starting value, step, accent colour (wp-color-picker), show/hide button                                                                                           |
| Validation  | Sanitize callback with admin error messages (empty title, step &lt; 1, bad colour)                                                                                       |
| Shortcode   | `[counter]` plus `title`, `step`, `color`, `button` attributes                                                                                                           |
| Frontend    | Markup, responsive `assets/css/counter.css`, `wp_enqueue_scripts`                                                                                                        |
| Live update | `assets/js/counter.js` posts to a REST endpoint — no reload                                                                                                              |
| Reset       | Nonce-protected reset button on the settings screen                                                                                                                      |
| Security    | `ABSPATH` guard, `sanitize_text_field()`, `absint()`, `intval()`, `esc_html()`, `esc_attr()`, `current_user_can( 'manage_options' )`, REST nonce check, light rate limit |
| Cleanup     | `uninstall.php` deletes both options (including on multisite)                                                                                                            |
| i18n        | Text domain `wp-counter-plugin`, `__()`, `esc_html__()`                                                                                                                  |

## Installation

**From the ZIP**

1. wp-admin → **Plugins → Add New Plugin → Upload Plugin**.
2. Select `wp-counter-plugin.zip` → **Install Now** → **Activate**.

**From source**

1. Copy the `wp-counter-plugin` folder into `wp-content/plugins/`.
2. Activate it in **Plugins**.

Requires WordPress 5.8+ and PHP 7.4+.

## Usage

1. Go to **Settings → WP Counter**.
2. Set **Title**, **Starting value**, **Step** (1 or more) and **Accent colour**,
   then **Save Changes**.
3. Paste `[counter]` into any post, page or text widget.
4. Press **+** on the frontend: the number increases by the step and is saved
   immediately, without a page reload.
5. Press **Reset counter to starting value** on the settings screen whenever you
   want to start over.

### Shortcode attributes

Every attribute is optional and falls back to the settings screen values.

```
[counter title="Visitors" step="5" color="#16a34a" button="yes"]
```

| Attribute | Default         | Description                                   |
| --------- | --------------- | --------------------------------------------- |
| `title`   | settings title  | Heading shown above the number                |
| `step`    | settings step   | Amount added per click (must be ≥ 1)          |
| `color`   | settings colour | Accent colour as a hex value, e.g. `#16a34a`  |
| `button`  | `yes`           | `no` hides the "+" button (read-only display) |

### REST endpoint

| Method | Route                                            | Purpose                                  |
| ------ | ------------------------------------------------ | ---------------------------------------- |
| `GET`  | `/wp-json/wp-counter-plugin/v1/counter`          | Read the current value                   |
| `POST` | `/wp-json/wp-counter-plugin/v1/counter?amount=5` | Increase the value (REST nonce required) |

The amount is capped at 100 per request and the endpoint is limited to 30
updates per minute per visitor.

## Folder structure

```
wp-counter-plugin/
├── wp-counter-plugin.php     Main file: plugin header, options, bootstrap
├── includes/
│   ├── class-admin.php       Settings page (Settings API, reset, help)
│   ├── class-shortcode.php   [counter] shortcode + asset loading
│   └── class-rest.php        REST endpoint for live updates
├── assets/
│   ├── css/counter.css       Frontend styles
│   ├── js/counter.js         Frontend increment logic
│   └── js/admin.js           "Copy shortcode" button
├── uninstall.php             Cleanup on plugin deletion
├── readme.txt                WordPress.org readme
└── README.md                 This file
```

## Data & privacy

Two options are used: `wcp_counter_settings` (title, starting value, step,
colour, button) and `wcp_counter_value` (the current number). Nothing else is
stored, no external service is contacted, and both options are deleted by
`uninstall.php`.

## Testing locally

(LocalWP / XAMPP / Local by Flywheel):

1. Activate the plugin.
2. Check **Settings → WP Counter** saves and shows errors for an empty title or
   a step of `0`.
3. Add `[counter]` to a page, press **+**, reload — the value must persist.
4. Deactivate and **Delete** the plugin; both options should disappear from
   `wp_options`.

## License

GPL-2.0-or-later.
