# Cabmedia

Laravel 12 admin for https://cabmedia.com/ (public site is built separately; `Site\PlaceholderController` only keeps the named routes alive).

## Setup
```
composer install
cp .env.example .env && php artisan key:generate
# create MySQL database `cabmedia`, set DB_* in .env
php artisan migrate --seed
php artisan storage:link
php artisan serve
```
Admin: `/admin` — default login from `ADMIN_EMAIL` / `ADMIN_PASSWORD` in `.env` (change after first login).

## Admin modules
Blogs (+ categories), Pages, Media library, SEO (per built-in page), Header & Footer menus, Settings, Users, Roles & permissions,
and **Blog automation** (blog sources, AI rewrite/scrape import, scheduled publishing, cron URLs — see `php artisan cron:urls`).
AI provider, language and audience for automated posts are set in Admin → Settings → AI provider / Blog automation.
