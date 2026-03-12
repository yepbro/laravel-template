# Blade + Vue Islands Mode

## Purpose

This mode is for projects where Laravel and Blade keep control of page
rendering, but selected parts of the page are enhanced with isolated Vue apps.

Route entry:

- `/islands`

Best fit:

- classic Laravel apps
- server-rendered marketing or backoffice pages
- projects that need a few interactive widgets, not a full SPA

## Technologies Used

- `Laravel` for routing and controller-driven pages
- `Blade` as the main rendering layer
- `Vue 3` for isolated interactive widgets
- `TypeScript` for island code
- `Pinia` shared across islands
- `pinia-plugin-persistedstate` for persisted client state
- `Vue Router` available through a memory router per island
- `@tanstack/vue-query` for API/server-state caching inside islands
- `vue-i18n` for localization
- `@unhead/vue` available in shared plugin setup
- `@vueuse/core`, `@vueuse/integrations`, and `@vueuse/motion` for composables and motion
- `Vite` for bundling and dev server
- `Tailwind CSS v4` for styling
- `shadcn-vue` + `Reka UI` for UI primitives
- `maska` for input masks
- `@formkit/auto-animate` for simple transitions
- `@internationalized/date` for date utilities
- `vue-draggable-plus` for sortable UIs
- `cropperjs` + `vue-picture-cropper` for image editing
- `vue3-dropzone` for file uploads
- `vue-sonner` for toast notifications
- `vee-validate` + `zod` for forms
- `MSW` for browser API mocks in development/tests
- `Vitest` + `@vue/test-utils` for unit tests
- `Playwright` for end-to-end browser tests
- `@vue/devtools` for local Vue debugging

## Main Files

Laravel and Blade:

- `routes/web.php` - Laravel route for `/islands`
- `app/Http/Controllers/IslandsController.php` - controller that returns the islands page
- `resources/views/islands.blade.php` - Blade page with island mount points
- `resources/views/layouts/frontend.blade.php` - shared frontend layout and `#toast-root`

Islands entry:

- `resources/js/islands/app.ts` - imports island components and mounts them
- `resources/js/shared/mountIsland.ts` - helper that creates a Vue app per island
- `resources/js/shared/createRouter.ts` - creates the memory router used by islands
- `resources/js/shared/installPlugins.ts` - installs shared app plugins into each island

Shared components and state:

- `resources/js/shared/components/examples/*` - demo islands for form/table/toast
- `resources/js/shared/stores/demo.ts` - shared demo `Pinia` store
- `resources/js/shared/mountToaster.ts` - global toaster mount
- `resources/js/shared/i18n/index.ts` - shared i18n instance
- `resources/js/shared/head.ts` - shared head manager
- `resources/js/shared/queryClient.ts` - shared Vue Query client
- `resources/js/shared/mocks/*` - MSW setup and handlers

Styling and tooling:

- `resources/css/app.css`
- `vite.config.js`
- `vitest.config.ts`
- `playwright.config.ts`
- `tsconfig.json`
- `eslint.config.js`
- `.prettierrc`
- `tests/frontend/*`
- `tests/e2e/*`

## How Islands Work

The page is rendered by Blade first.

Then `resources/js/islands/app.ts`:

- finds DOM targets like `[data-island="form-demo"]`
- creates a small Vue app for each target
- installs `Pinia` and a memory-based `Vue Router`
- mounts the chosen component into that target

This keeps the page server-rendered while allowing modern Vue widgets where
they are actually useful.

## How To Add a New Island

1. Create a Vue component for the widget.
2. Add a mount target to the Blade page, for example:
    - `<div data-island="analytics-summary"></div>`
3. Register the new island in `resources/js/islands/app.ts` by calling `mountIsland(...)`.

Typical example:

- Blade provides the placeholder
- `resources/js/islands/app.ts` decides which component mounts there
- the component can use `Pinia`, router APIs, shared UI components, and toasts

If the widget needs app-wide helpers, they are already available through the
shared plugin installer:

- `Pinia`
- `Vue Router`
- `Vue Query`
- `vue-i18n`
- `Unhead`
- `Motion`
- `v-maska`

## How To Add a New Blade Page With Islands

1. Create a Blade view in `resources/views/`.
2. Add the needed `data-island="..."` placeholders.
3. Add or update a controller in `app/Http/Controllers/`.
4. Add a Laravel route in `routes/web.php`.
5. If the page should use the islands bundle, make sure the controller passes:
    - `resources/css/app.css`
    - `resources/js/islands/app.ts`

You can keep several Blade pages on the same islands entry if that stays simple.
If a page becomes very different, consider creating another dedicated islands
entry file.

## How To Share State Between Islands

Shared state belongs in a `Pinia` store, for example:

- `resources/js/shared/stores/demo.ts`

Important:

- each mounted island gets the same store definitions
- state is shared only within the same page runtime, not between requests
- server truth still belongs to Laravel or your API/database

Use shared stores when two widgets on the same page should react to each other.

If state should survive reloads:

- add `persist` to the store definition
- keep persisted values serializable and UI-safe

## Router Usage In Islands

This mode still installs `Vue Router`, but through `createMemoryHistory()`.

That means:

- there is no browser URL ownership like in the SPA
- you can still use router-aware composables and route names inside islands
- treat router usage here as an app convenience, not as page navigation

If an island needs real browser navigation, that is usually a signal to move the
feature to the SPA mode instead.

## Data Fetching

`@tanstack/vue-query` is installed globally through the shared plugin setup.

Use it when an island:

- fetches data from Laravel JSON endpoints
- needs caching or background refetching
- should not duplicate server-state logic in `Pinia`

Recommendation:

- `Pinia` for client/shared widget state
- `Vue Query` for remote/server state

The shared query client lives in:

- `resources/js/shared/queryClient.ts`

## Localization

`vue-i18n` is available in islands as well.

Main files:

- `resources/js/shared/i18n/index.ts`
- `resources/js/shared/i18n/messages/en.ts`

Use `useI18n()` inside island components the same way as in the SPA mode.

## Head Management

`@unhead/vue` is installed globally, but in the islands mode it should be used
carefully.

Good uses:

- title or meta changes for Blade pages that still benefit from client updates
- isolated widgets that need small head updates

Bad use:

- trying to reimplement whole page navigation behavior inside islands

## Toasts

Toasts are global for the frontend layout:

- Blade layout contains `#toast-root`
- `resources/js/islands/app.ts` calls `mountToaster()`

Any island can trigger notifications through `vue-sonner`.

## Forms and Enhanced Inputs

The template already includes support for:

- `vee-validate`
- `zod`
- `maska`
- `@internationalized/date`

Use these when islands need:

- validated forms
- masked phone/date/code inputs
- locale-aware date handling

## Motion, Drag-and-Drop, Uploads, and Media

These optional building blocks are already installed:

- `@vueuse/motion`
- `@formkit/auto-animate`
- `vue-draggable-plus`
- `vue3-dropzone`
- `cropperjs`
- `vue-picture-cropper`

They are best used for:

- sortable admin widgets
- animated list/item transitions
- avatar and image uploads
- crop-before-upload flows

They are not wired to the default islands page, but no extra package setup is
needed before using them.

## Mocking APIs

`MSW` is included for frontend API mocking.

Main files:

- `resources/js/shared/mocks/browser.ts`
- `resources/js/shared/mocks/handlers.ts`
- `public/mockServiceWorker.js`

MSW starts only when:

- `VITE_ENABLE_MSW=true`

This is useful for building islands against mocked JSON endpoints before backend
implementation is finished.

Useful command:

- `./vendor/bin/sail composer front:msw:init`

## Tests

Unit test baseline:

- `Vitest`
- `@vue/test-utils`
- config: `vitest.config.ts`
- tests: `tests/frontend/*`

E2E baseline:

- `Playwright`
- config: `playwright.config.ts`
- tests: `tests/e2e/*`

Useful commands:

- `./vendor/bin/sail npm run test:unit`
- `./vendor/bin/sail npm run test:unit:watch`
- `./vendor/bin/sail npm run test:e2e`
- `./vendor/bin/sail npm run test:e2e:headed`
- `./vendor/bin/sail npm run test:e2e:ui`
- `./vendor/bin/sail composer front:test:unit`
- `./vendor/bin/sail composer front:test:e2e`

## DevTools

Vue DevTools is included in dependencies.

Useful command:

- `./vendor/bin/sail npm run devtools`

## What To Remove If You Do Not Use Vue-only SPA

Delete:

- `app/Http/Controllers/SpaController.php`
- `resources/views/spa.blade.php`
- `resources/js/app.ts`
- `resources/js/App.vue`
- `resources/js/layouts/SpaLayout.vue`
- `resources/js/spa/`

Update:

- `routes/web.php` - remove `/spa/{path?}` and change root redirect if needed
- `vite.config.js` - remove `resources/js/app.ts` from `input`
- `README.md` and docs if you want a single-mode template

Keep:

- `resources/views/layouts/frontend.blade.php`
- `resources/js/shared/*`

because the islands mode still depends on them.

## What Not To Delete By Mistake

Even if you keep only the Blade mode, these files are still central:

- `resources/js/islands/app.ts`
- `resources/js/shared/mountIsland.ts`
- `resources/js/shared/installPlugins.ts`
- `resources/js/shared/mountToaster.ts`
- `resources/views/layouts/frontend.blade.php`
- `resources/css/app.css`

## When To Prefer Islands Over SPA

Prefer islands when:

- Blade already renders most of the page well
- SEO and server-rendered HTML matter
- the interactive part is local and limited
- the team is more comfortable with Laravel-first flows

Prefer the SPA mode when:

- the frontend owns navigation
- the page behaves like an application, not a document
- cross-page client state matters
- several widgets start depending on each other heavily

## Developer Notes

- Use `./vendor/bin/sail composer dev:islands` to start the app and target the islands route.
- Use `./vendor/bin/sail composer open:islands` to print or try to open the islands URL.
- Use `./vendor/bin/sail npm run lint`, `typecheck`, `build`, and `test:unit` before finalizing frontend changes.
- Keep island selectors stable. They are the contract between Blade and Vue.
- Avoid putting too much orchestration in Blade. If page interactivity grows large, move it to the SPA mode instead of forcing more islands into one page.
