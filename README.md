<p align="center">
  <img src="public/favicon.svg" alt="Mind Whispers" width="140">
</p>

<h1 align="center" style="margin-top: -30px">Mind Whispers</h1>

Mind Whispers is a Laravel 13 & Livewire 4 publication. Readers browse free and Premium posts, subscribe through Stripe Checkout, and manage billing. Staff publish articles, schedule newsletters, and watch Cashier subscriptions from a permissioned dashboard.

Auth sits on Fortify (email verification, 2FA, passkeys). Access control uses Spatie roles. The public site and staff UI are Livewire pages.

## Demo

### Admin View

https://github.com/user-attachments/assets/09ea0fee-2a41-418a-9825-b2d9d9cf3814

Dashboard, users, subscriptions, newsletters, and full content moderation.

### Author View

https://github.com/user-attachments/assets/a32ae34f-5236-4075-8460-8ebe6f234ae4

Create and publish posts, manage comments on your own articles, and track views.

### Reader View

https://github.com/user-attachments/assets/feeb5721-8811-4ad2-bee4-be717c974808

Browse free and Premium posts, subscribe via Stripe, leave comments, and manage billing.

## Libraries and tools

<p>
  <a href="https://livewire.laravel.com/"><img src="https://img.shields.io/badge/Livewire-4-FB70A9?logo=livewire&logoColor=white" alt="Livewire"></a>
  <a href="https://fluxui.dev/"><img src="https://fluxui.dev/faviconcircle32x32.png" alt="" height="20"> <img src="https://img.shields.io/badge/Flux_UI-0F172A" alt="Flux UI"></a>
  <a href="https://laravel.com/docs/fortify"><img src="https://img.shields.io/badge/Fortify-FF2D20?logo=laravel&logoColor=white" alt="Fortify"></a>
  <a href="https://laravel.com/docs/billing"><img src="https://img.shields.io/badge/Cashier-Stripe-635BFF?logo=stripe&logoColor=white" alt="Cashier Stripe"></a>
  <a href="https://spatie.be/docs/laravel-permission"><img src="https://img.shields.io/badge/Spatie-Permission-197593?logo=php&logoColor=white" alt="Spatie Permission"></a>
  <a href="https://tailwindcss.com/"><img src="https://img.shields.io/badge/Tailwind-v4-06B6D4?logo=tailwindcss&logoColor=white" alt="Tailwind CSS"></a>
  <a href="https://vitejs.dev/"><img src="https://img.shields.io/badge/Vite-646CFF?logo=vite&logoColor=white" alt="Vite"></a>
  <a href="https://flatpickr.js.org/"><img src="https://img.shields.io/badge/Flatpickr-133337?logo=javascript&logoColor=F7DF1E" alt="Flatpickr"></a>
  <a href="https://trix-editor.org/"><img src="https://img.shields.io/badge/Trix-70B873?logo=javascript&logoColor=white" alt="Trix"></a>
  <a href="https://www.chartjs.org/"><img src="https://img.shields.io/badge/Chart.js-FF6384?logo=chartdotjs&logoColor=white" alt="Chart.js"></a>
  <a href="https://alpinejs.dev/"><img src="https://img.shields.io/badge/Alpine.js-8BC0D0?logo=alpinedotjs&logoColor=white" alt="Alpine.js"></a>
  <a href="https://pestphp.com/"><img src="https://pestphp.com/www/favicon-32.png" alt="" height="20"> <img src="https://img.shields.io/badge/Pest-18B69B" alt="Pest"></a>
</p>

| | Library | Why it’s here |
| :---: | --- | --- |
| <img src="https://cdn.simpleicons.org/livewire/FB70A9" alt="Livewire" height="20"> | [Laravel Livewire](https://livewire.laravel.com/) | Reactive pages (SFCs, islands, navigate, poll). |
| <img src="https://fluxui.dev/faviconcircle32x32.png" alt="Flux" height="20"> | [Livewire Flux](https://fluxui.dev/) | UI kit for settings and staff: inputs, nav, toasts, modals. |
| <img src="https://cdn.simpleicons.org/laravel/FF2D20" alt="Laravel" height="20"> | [Laravel Fortify](https://laravel.com/docs/fortify) | Login, registration, email verification, 2FA, and passkeys. |
| <img src="https://cdn.simpleicons.org/stripe/635BFF" alt="Stripe" height="20"> | [Laravel Cashier (Stripe)](https://laravel.com/docs/billing) | Premium Checkout, Customer Portal, and webhooks. |
| <img src="https://cdn.simpleicons.org/php/197593" alt="PHP" height="20"> | [Spatie Laravel Permission](https://spatie.be/docs/laravel-permission) | Roles and permissions that gate staff features. |
| <img src="https://cdn.simpleicons.org/tailwindcss/06B6D4" alt="Tailwind CSS" height="20"> | [Tailwind CSS v4](https://tailwindcss.com/) + [@tailwindcss/typography](https://github.com/tailwindlabs/tailwindcss-typography) | App styling, plus prose styles for post HTML. |
| <img src="https://cdn.simpleicons.org/vite/646CFF" alt="Vite" height="20"> | [Vite](https://vitejs.dev/) + [laravel-vite-plugin](https://laravel.com/docs/vite) | Builds CSS/JS and loads Instrument Sans. |
| <img src="https://cdn.simpleicons.org/javascript/F7DF1E" alt="JavaScript" height="20"> | [Flatpickr](https://flatpickr.js.org/) | Date/time pickers for scheduled posts and newsletters. |
| <img src="https://cdn.simpleicons.org/javascript/70B873" alt="JavaScript" height="20"> | [Trix](https://trix-editor.org/) | Rich-text editor for posts and newsletters. |
| <img src="https://cdn.simpleicons.org/chartdotjs/FF6384" alt="Chart.js" height="20"> | [Chart.js](https://www.chartjs.org/) | Views chart on the staff dashboard. |
| <img src="https://cdn.simpleicons.org/alpinedotjs/8BC0D0" alt="Alpine.js" height="20"> | [Alpine.js](https://alpinejs.dev/) | Small client UI state (e.g. navbar avatar menu). |
| <img src="https://pestphp.com/www/favicon.svg" alt="Pest" height="20"> | [Pest](https://pestphp.com/) | Feature and unit tests in CI. |

## Project structure

```text
.
├── .github/
│   └── workflows/
├── app/
│   ├── Actions/
│   ├── Concerns/
│   ├── Console/
│   ├── Http/
│   ├── Jobs/
│   ├── Listeners/
│   ├── Livewire/
│   ├── Mail/
│   ├── Models/
│   ├── Notifications/
│   ├── Observers/
│   ├── Providers/
│   └── Support/
├── bootstrap/
├── config/
├── database/
│   ├── factories/
│   ├── migrations/
│   └── seeders/
├── public/
│   ├── build/
│   └── storage/          → symlink to storage/app/public
├── resources/
│   ├── css/
│   ├── js/
│   └── views/
│       ├── components/
│       ├── custom-components/
│       ├── flux/
│       ├── layouts/
│       ├── livewire/
│       ├── pages/
│       ├── partials/
│       └── vendor/
├── routes/
├── storage/
│   └── app/
│       └── public/
│           ├── livewire-tmp/
│           └── posts/
├── tests/
│   ├── Feature/
│   └── Unit/
├── artisan
├── composer.json
├── package.json
├── phpstan.neon
├── pint.json
├── phpunit.xml
└── vite.config.js
```

## How to use

### Requirements

- PHP 8.3+
- Composer
- Node.js 22
- SQLite (default) or MySQL

### Install

```bash
git clone git@github.com:Maryam-Eid/livewire-blog.git
cd livewire-blog
composer setup
php artisan db:seed
composer run dev
```

`composer setup` copies [`.env.example`](.env.example), generates `APP_KEY`, creates `database/database.sqlite` if needed, migrates, and builds Vite assets.

Open [http://localhost:8000](http://localhost:8000). `/` redirects to `/blog`.

### Demo accounts

| Role | Email | Password |
| --- | --- |----------|
| Admin | `admin@example.com` | password |
| Author | `author@example.com` | password |

- Pricing is at `/pricing`; billing at `/account/billing`

### Stripe

Checkout returns **503** until keys and Price IDs exist. Add to `.env`:

```env
STRIPE_KEY=pk_test_...
STRIPE_SECRET=sk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...
CASHIER_CURRENCY=egp
```

Then open `/subscriptions/plans`, save `price_…` IDs, and activate monthly and yearly.

Forward webhooks locally:

```bash
stripe listen --forward-to localhost:8000/stripe/webhook
```


