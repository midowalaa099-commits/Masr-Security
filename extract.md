# MASR Security — Branding Implementation Extract

Tracking file for the "replace placeholder/demo branding with real MASR Security branding" task.
Last updated: 2026-09-05

## Verified facts used (no fabrication)

| Fact | Source |
| --- | --- |
| Company name: **MASR Security** | Owner / project / official Facebook page |
| Official Facebook page | `https://www.facebook.com/profile.php?id=61586652051946` (owner-provided) |
| Authorized Hikvision distributor | Owner-described |
| Business areas: security cameras, NVR/DVR recorders, interactive displays, professional solutions for home & business | Owner-described positioning |

## Pages and components — significant edits

### New / rewritten
- `resources/views/components/store/brand.blade.php` — settings-driven logo (`site_logo` via public storage) with the existing monogram wordmark as placeholder fallback.
- `resources/views/components/admin/layout.blade.php` — sidebar brand now shows uploaded logo when configured; favicon link added.
- `app/Support/helpers.php` — new `setting_array()` and `setting_image()` helpers.
- `app/Http/Controllers/Admin/AdminSettingsController.php` — accepts logo/hero/gallery image uploads through the public `site/{branding,hero,gallery}` storage; deletes replaced files.
- `app/Http/Requests/Admin/SettingsUpdateRequest.php` — image validation rules for the new brand uploads.
- `resources/views/admin/settings/edit.blade.php` — multipart form with Branding section (logo, hero, gallery upload + remove).
- `lang/en/admin.php`, `lang/ar/admin.php` — new labels for branding controls.
- `public/favicon.svg` — brand monogram favicon (replaces empty 0-byte `favicon.ico`; layouts link the SVG).
- `tests/Feature/BrandSettingsTest.php` — 4 feature tests for upload/remove/rendering/hidden placeholders.

### Storefront pages
- `resources/views/store/home.blade.php`
  - Removed the invented stat block (`100%`, `24m`, `Cairo`).
  - Hero visual: `hero_image` setting used when present (object-cover, eager + high fetchpriority); falls back to featured product or shield placeholder.
  - New "What We Do" section driven by settings `why_points_{en,ar}` (hidden when unset).
  - New settings-driven "Gallery" section (hidden until images uploaded; aspect-ratio, lazy-loaded, object-cover).
- `resources/views/store/about.blade.php` — removed invented claims (genuine/warranty, next-day dispatch, wholesale pricing, "Egyptian distributor"). Replaced with owner-described positioning + settings-driven point cards + official Facebook link.
- `resources/views/store/contact.blade.php` — added official Facebook card (guarded by setting); repurposed generic contact wording.
- `resources/views/components/store/layout.blade.php` — favicon, meta description, Open Graph tags (title/description/url/image from settings).
- `resources/views/layouts/guest.blade.php`, `resources/views/layouts/navigation.blade.php` — settings-driven brand + favicon.

### Content (lang)
- `lang/en/store.php`, `lang/ar/store.php` — added `why_title/desc`, `gallery_title/desc`; neutralized `cta_desc` and `contact_description` (removed unverified "within 24 hours" / "reach us any day" claims).

### Settings
- `database/seeders/SettingsSeeder.php` — `facebook` set to the official URL; unverified placeholder contact (`phone`, `whatsapp`, `email`, `address`, `instagram`) blanked until provided by the owner; added `why_points_{en,ar}`.

## Assets integrated
- `public/favicon.svg` — placeholder monogram favicon (owner may replace with the brand mark).
- None of the Facebook assets (logo, cover, product/project/gallery photos) are present yet.

## Unavailable / placeholder — awaiting owner input
- Logo file: the pasted clipboard image cannot be read by the tooling and no logo file exists in the project; admin Settings → Branding accepts a PNG/JPEG upload stored at `storage/app/public/site/branding/`.
- Facebook page content: the page returns HTTP 400 to all standard fetches (no verification path available; scraping bypasses are out of scope). No photos/contact verified from it.
- Real phone / WhatsApp / email / address / Instagram: blanked on the public site to avoid displaying fabricated contact data; editable in admin Settings.
- Hero / gallery photos: uploadable in admin Settings (`site/hero`, `site/gallery`).

## Verification
- `php artisan test` — 100 tests / 338 assertions passing (baseline was 96 / 309).
- `vendor/bin/pint --format agent` — clean.
- `npm run build` — rebuilt CSS/JS + manifest.
- `php artisan view:cache` — all Blade templates cached without errors.
- `php artisan storage:link` — link present.
- Live smoke: `/`, `/about`, `/contact` return 200; official Facebook link in footer/contact/about; Arabic why-points + hero rendering verified; no invented claims render.