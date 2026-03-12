# Laravel Project Template

Starter repository for Laravel projects that need a modern frontend baseline
without Inertia.

## Purpose

This project is a reusable Laravel starter for teams that want:

- a modern frontend stack without Inertia
- both `Vue-only SPA` and `Blade + Vue islands` available in one template
- shared UI primitives, forms, toasts, and state management ready from the start
- a codebase that can be trimmed down quickly after cloning

Use it as a base for new products, internal tools, admin panels, or
server-rendered Laravel apps that still need focused Vue interactivity.

## Frontend Documentation

- `docs/frontend-vue.md` - guide for the `Vue-only SPA` mode
- `docs/frontent-blade.md` - guide for the `Blade + Vue islands` mode

## Frontend Modes

This template ships with two parallel frontend modes:

### `Vue-only SPA`

- Route: `/spa`
- Stack: `Vue 3 + TypeScript + Vue Router + Pinia + Tailwind CSS v4`
- Best when the frontend owns navigation and page composition.

### `Blade + Vue islands`

- Route: `/islands`
- Stack: `Blade + focused Vue widgets + TypeScript + Pinia + Vue Router`
- Best when Laravel keeps page rendering and Vue enhances selected sections.

Both modes share:

- `Vite`
- `Tailwind CSS v4`
- `shadcn-vue` UI components
- `Reka UI`
- `@tanstack/vue-query`
- `vue-i18n`
- `@unhead/vue`
- `vue-sonner` toaster
- `pinia-plugin-persistedstate`
- `maska`
- `@vueuse/motion`
- `vee-validate + zod`
- `ESLint + Prettier + vue-tsc + Vitest + Playwright`

## Included Libraries

This template also includes a wider Vue toolbox that can be used when the
project grows:

- data and app infrastructure: `@tanstack/vue-query`, `@unhead/vue`, `vue-i18n`
- state and persistence: `Pinia`, `pinia-plugin-persistedstate`
- Vue utilities: `@vueuse/core`, `@vueuse/integrations`, `@vueuse/motion`
- forms and inputs: `vee-validate`, `zod`, `@internationalized/date`, `maska`
- interaction and UX: `@formkit/auto-animate`, `vue-draggable-plus`
- files and media: `cropperjs`, `vue-picture-cropper`, `vue3-dropzone`
- charts and tables: `@tanstack/vue-table`, `@unovis/vue`
- testing and mocking: `Vitest`, `@vue/test-utils`, `Playwright`, `MSW`
- tooling: `@vue/devtools`

## Starter Examples

Each mode includes working examples for:

- validated form
- data table
- toast notifications

The examples live on top of shared demo components and a shared Pinia store, so
they are easy to replace with application code once a project is cloned.

## Key Files

- `resources/js/app.ts` - SPA entry
- `resources/js/spa/router.ts` - SPA routes
- `resources/js/islands/app.ts` - Blade islands entry
- `resources/js/shared/components/examples/*` - shared demo components
- `resources/js/shared/stores/demo.ts` - shared Pinia demo store
- `resources/views/spa.blade.php` - SPA shell
- `resources/views/islands.blade.php` - Blade islands page
- `resources/views/layouts/frontend.blade.php` - shared frontend Blade layout

## Local Setup

Install backend and frontend dependencies through Sail:

```bash
./vendor/bin/sail composer install
./vendor/bin/sail npm install
```

Run the dev environment:

```bash
./vendor/bin/sail composer dev
```

Target a specific frontend mode:

```bash
./vendor/bin/sail composer dev:spa
./vendor/bin/sail composer dev:islands
./vendor/bin/sail composer open:spa
./vendor/bin/sail composer open:islands
```

Direct npm aliases are also available:

```bash
./vendor/bin/sail npm run open:spa
./vendor/bin/sail npm run open:islands
```

Useful frontend checks:

```bash
./vendor/bin/sail npm run lint
./vendor/bin/sail npm run typecheck
./vendor/bin/sail npm run build
./vendor/bin/sail npm run test:unit
```

Frontend testing and mocking helpers:

```bash
./vendor/bin/sail composer front:test:unit
./vendor/bin/sail composer front:test:e2e
./vendor/bin/sail composer front:msw:init
```

Additional npm scripts:

```bash
./vendor/bin/sail npm run test:unit
./vendor/bin/sail npm run test:unit:watch
./vendor/bin/sail npm run test:e2e
./vendor/bin/sail npm run test:e2e:headed
./vendor/bin/sail npm run test:e2e:ui
./vendor/bin/sail npm run msw:init
```

## Customizing After Clone

- Keep only `/spa` if the project is a client-driven Vue app.
- Keep only `/islands` if the project is mostly Blade with progressive
  enhancement.
- Replace the shared demo store and example components with real domain logic.
- Remove unused UI primitives from `resources/js/components/ui` as the project
  narrows its scope.
