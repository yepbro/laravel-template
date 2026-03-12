# Vue-only SPA Mode

## Purpose

This mode is for projects where Vue owns navigation and page composition, while
Laravel stays the backend and serves a single Blade shell.

Route entry:

- `/spa`

Best fit:

- dashboards
- admin panels
- apps with client-side navigation
- projects that need `Vue Router` and `Pinia` from day one

## Technologies Used

- `Laravel` for backend routes and the Blade shell
- `Blade` only as a host page for the SPA root
- `Vue 3` as the application runtime
- `TypeScript` for SPA code
- `Vue Router` for client-side pages
- `Pinia` for shared state
- `pinia-plugin-persistedstate` for persisted client state
- `@tanstack/vue-query` for API/server-state caching
- `vue-i18n` for localization
- `@unhead/vue` for document title and head management
- `@vueuse/core`, `@vueuse/integrations`, and `@vueuse/motion` for composables and motion
- `Vite` for bundling and dev server
- `Tailwind CSS v4` for styling
- `shadcn-vue` + `Reka UI` for UI primitives
- `maska` for input masks
- `@formkit/auto-animate` for automatic list/form transitions
- `@internationalized/date` for date primitives
- `vue-draggable-plus` for drag-and-drop
- `cropperjs` + `vue-picture-cropper` for image cropping
- `vue3-dropzone` for file dropzones
- `vue-sonner` for toast notifications
- `vee-validate` + `zod` for forms
- `MSW` for browser API mocks in development/tests
- `Vitest` + `@vue/test-utils` for unit tests
- `Playwright` for end-to-end browser tests
- `@vue/devtools` for local Vue debugging

## Main Files

Laravel and Blade:

- `routes/web.php` - Laravel route that sends `/spa/{path?}` to the SPA shell
- `app/Http/Controllers/SpaController.php` - controller that returns the SPA view
- `resources/views/spa.blade.php` - Blade shell with `<div id="app"></div>`
- `resources/views/layouts/frontend.blade.php` - shared frontend layout, includes `#toast-root`

SPA entry and structure:

- `resources/js/app.ts` - SPA bootstrap, installs plugins, mounts toaster, mounts Vue
- `resources/js/App.vue` - current SPA root, renders the main layout
- `resources/js/layouts/SpaLayout.vue` - app shell and navigation
- `resources/js/spa/router.ts` - route table for SPA pages
- `resources/js/spa/pages/*` - page components

Shared frontend infrastructure:

- `resources/js/shared/installPlugins.ts` - installs shared app plugins
- `resources/js/shared/createRouter.ts` - router factory
- `resources/js/shared/mountToaster.ts` - global toaster mount
- `resources/js/shared/stores/demo.ts` - shared demo `Pinia` store
- `resources/js/shared/components/examples/*` - example components reused by both modes
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

## How Routing Works

Laravel handles only the shell:

- request to `/spa` or `/spa/...`
- `SpaController` returns `resources/views/spa.blade.php`
- Vite loads `resources/js/app.ts`
- Vue Router takes over inside the browser

This means:

- add Laravel routes only when you need a different backend endpoint or shell
- add frontend pages in `Vue Router` for normal SPA navigation

## How To Add a New SPA Page

1. Create a new page component in `resources/js/spa/pages/`.
2. Add a new route object in `resources/js/spa/router.ts`.
3. If the page should appear in the top navigation, update the `navigation` array in `resources/js/layouts/SpaLayout.vue`.
4. If the page needs shared app state, create or extend a store in `resources/js/shared/stores/` or `resources/js/spa/stores/`.

Recommended pattern:

- keep page-level concerns in `resources/js/spa/pages/`
- keep reusable visual pieces in `resources/js/shared/components/`
- keep route-independent business state in `Pinia`

## How To Add a New Store

1. Create a new file in `resources/js/shared/stores/` or `resources/js/spa/stores/`.
2. Export a `defineStore(...)`.
3. Use it directly in pages or components.

You do not need extra registration because `Pinia` is installed globally in
`resources/js/shared/installPlugins.ts`.

If the store should survive reloads:

- add a `persist` option
- keep only stable, serializable values in persisted state

Example source:

- `resources/js/shared/stores/demo.ts`

## Data Fetching

`@tanstack/vue-query` is already installed globally.

Use it for:

- API calls
- caching and refetching
- optimistic UI
- background refresh

The shared query client lives in:

- `resources/js/shared/queryClient.ts`

Recommendation:

- use `Pinia` for client/app state
- use `Vue Query` for server/API state

## Localization

`vue-i18n` is already wired globally.

Main files:

- `resources/js/shared/i18n/index.ts`
- `resources/js/shared/i18n/messages/en.ts`

To add a new locale:

1. Create another messages file.
2. Register it in `resources/js/shared/i18n/index.ts`.
3. Use `useI18n()` inside components.

## Head Management

Use `@unhead/vue` for title/meta tags.

Current setup:

- `resources/js/shared/head.ts`
- `resources/js/layouts/SpaLayout.vue` already uses `useHead(...)`

Use it in pages when:

- page title changes by route
- SEO/meta tags matter
- social tags or canonical links are needed

## Input Masks, Motion, Dates, Drag and Drop

Already available globally or as installable imports:

- `maska` is registered as the `v-maska` directive
- `@vueuse/motion` is installed globally
- `@internationalized/date` is available for advanced date handling
- `vue-draggable-plus` is available for sortable UIs
- `@formkit/auto-animate` is available for simple animated transitions

These are intentionally not forced into the starter examples, but they are ready
to use without more package setup.

## File Uploads and Image Editing

Available in the template:

- `vue3-dropzone`
- `cropperjs`
- `vue-picture-cropper`

Use them when:

- building avatar/image upload flows
- adding drag-and-drop file selection
- needing a crop step before upload

They are included as optional building blocks, not mounted by default.

## How To Add a New Layout

If the SPA needs another shell:

1. Create a layout in `resources/js/layouts/`.
2. Use it inside a page component or in `resources/js/App.vue`.

Keep `resources/js/App.vue` small. It should usually compose layouts, not
contain business logic.

## Toasts

Global toasts are already wired:

- Blade layout has `#toast-root`
- `resources/js/app.ts` calls `mountToaster()`
- shared wrapper lives in `resources/js/shared/components/ToasterRoot.vue`

Use:

- `toast.success(...)`
- `toast.loading(...)`
- `toast(...)`

from `vue-sonner` inside SPA components.

## Forms

Starter form example uses:

- `vee-validate`
- `@vee-validate/zod`
- `zod`
- shared UI form primitives from `resources/js/components/ui/form/`

When adding real forms:

- keep validation schema near the form or in a dedicated schema file
- prefer typed `zod` schemas for predictable form contracts
- keep submit side effects in a service/store if the form becomes large

For masked inputs:

- use the global `v-maska` directive
- keep the unmasked value contract explicit when sending data to the backend

## Mocking APIs

`MSW` is included for frontend API mocking.

Main files:

- `resources/js/shared/mocks/browser.ts`
- `resources/js/shared/mocks/handlers.ts`
- `public/mockServiceWorker.js`

MSW starts only when:

- `VITE_ENABLE_MSW=true`

Typical use:

1. Add handlers in `resources/js/shared/mocks/handlers.ts`.
2. Start the app with the environment flag enabled.
3. Use mocked endpoints during UI development or testing.

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

## What To Remove If You Do Not Use Blade + Vue Islands

Delete:

- `app/Http/Controllers/IslandsController.php`
- `resources/views/islands.blade.php`
- `resources/js/islands/app.ts`

Update:

- `routes/web.php` - remove the `/islands` route
- `vite.config.js` - remove `resources/js/islands/app.ts` from `input`
- `README.md` and docs if you want a single-mode template

Keep:

- `resources/views/layouts/frontend.blade.php`
- `resources/js/shared/*`

because the SPA still uses them.

## What Not To Delete By Mistake

Even if you keep only the SPA mode, these are still required:

- `resources/views/layouts/frontend.blade.php`
- `resources/css/app.css`
- `resources/js/shared/installPlugins.ts`
- `resources/js/shared/mountToaster.ts`
- `resources/js/shared/components/ToasterRoot.vue`

## Developer Notes

- Use `./vendor/bin/sail composer dev:spa` to start the app and target the SPA route.
- Use `./vendor/bin/sail composer open:spa` to print or try to open the SPA URL.
- Use `./vendor/bin/sail npm run lint`, `typecheck`, `build`, and `test:unit` before calling the frontend work complete.
- Add backend JSON/API endpoints in Laravel as normal; this mode does not depend on Inertia.
- Keep route names and file names explicit. This template is meant to be easy to prune after cloning.
