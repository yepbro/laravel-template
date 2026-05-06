# Laravel Project Template

Starter repository for Laravel projects that need a modern frontend baseline
without Inertia.

## Purpose

This project is a reusable Laravel starter for teams that want:

- a modern frontend stack without Inertia
- a `Vue-only SPA` served from `/spa`
- shared UI primitives, forms, toasts, and state management ready from the start
- a codebase that can be trimmed down quickly after cloning

Use it as a base for new products, internal tools, admin panels, or
server-rendered Laravel apps that pair with a focused Vue SPA where needed.

## Frontend Documentation

- `docs/frontend-vue.md` - guide for the `Vue-only SPA`

## Frontend

### `Vue-only SPA`

- Route: `/spa`
- Stack: `Vue 3 + TypeScript + Vue Router + Pinia + Tailwind CSS v4`
- The frontend owns navigation and page composition.

The stack includes:

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

The SPA includes working examples for:

- validated form
- data table
- toast notifications

The examples live on top of shared demo components and a shared Pinia store, so
they are easy to replace with application code once a project is cloned.

## Key Files

- `resources/js/app.ts` - SPA entry
- `resources/js/spa/router.ts` - SPA routes
- `resources/js/shared/components/examples/*` - shared demo components
- `resources/js/shared/stores/demo.ts` - shared Pinia demo store
- `resources/views/spa.blade.php` - SPA shell
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

Target the SPA (prints the URL, then runs the same dev stack):

```bash
./vendor/bin/sail composer dev:spa
./vendor/bin/sail composer open:spa
```

Direct npm aliases are also available:

```bash
./vendor/bin/sail npm run open:spa
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

- Replace the shared demo store and example components with real domain logic.
- Remove unused UI primitives from `resources/js/components/ui` as the project
  narrows its scope.
