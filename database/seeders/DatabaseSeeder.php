<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\MenuItem;
use App\Models\NewsSource;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class DatabaseSeeder extends Seeder
{
    /** group => permissions (also drives the Roles & permissions screen) */
    public const PERMISSIONS = [
        'Dashboard' => ['admin.access'],
        'Blogs' => ['articles.view', 'articles.create', 'articles.edit_own', 'articles.edit_all', 'articles.publish', 'articles.delete'],
        'Content' => ['categories.manage', 'pages.manage', 'seo.manage', 'media.manage'],
        'Engagement' => ['contacts.manage'],
        'System' => ['users.manage', 'roles.manage', 'settings.manage', 'menus.manage', 'sources.manage', 'automation.manage'],
    ];

    private const CATEGORIES = ['Advertising', 'Branding', 'Digital Marketing', 'Media & PR', 'Case Studies'];

    /** Blog sources are added by the team in Admin > Blog sources. */
    private const SOURCES = [];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $perms) {
            foreach ($perms as $p) Permission::findOrCreate($p, 'web');
        }
        $all = Permission::pluck('name')->all();

        Role::findOrCreate('Super Admin', 'web');
        Role::findOrCreate('Admin', 'web')->syncPermissions(array_diff($all, ['roles.manage']));
        Role::findOrCreate('Editor', 'web')->syncPermissions([
            'admin.access', 'articles.view', 'articles.create', 'articles.edit_own', 'articles.edit_all', 'articles.publish', 'articles.delete',
            'categories.manage', 'pages.manage', 'seo.manage', 'media.manage', 'contacts.manage',
        ]);
        Role::findOrCreate('Author', 'web')->syncPermissions(['admin.access', 'articles.view', 'articles.create', 'articles.edit_own']);

        // First login. Override with ADMIN_EMAIL / ADMIN_PASSWORD in .env before seeding a real server, and change the password after logging in.
        $admin = User::firstOrCreate(['email' => env('ADMIN_EMAIL', 'admin@cabmedia.test')], ['name' => 'Super Admin', 'password' => env('ADMIN_PASSWORD', 'Admin@12345')]);
        $admin->assignRole('Super Admin');

        foreach (self::CATEGORIES as $c) {
            Category::firstOrCreate(['slug' => Str::slug($c)], ['name' => $c]);
        }

        foreach (self::SOURCES as [$name, $url, $catSlug]) {
            NewsSource::firstOrCreate(['name' => $name], [
                'type' => 'rss', 'feed_url' => $url, 'category_id' => Category::where('slug', $catSlug)->value('id'), 'is_active' => false,
            ]);
        }

        $defaults = [
            'site.name' => 'Cab Media', 'site.tagline' => 'One stop solution for all your media & marketing needs',
            'site.about' => 'A team of experienced professionals in the field of Communications, Advertising & Branding.', 'site.email' => 'info@cabmedia.in', 'site.phone' => '+91 9820446872 / +91 8805013828',
            'ai.base_url' => 'https://openrouter.ai/api/v1', 'ai.model' => 'meta-llama/llama-3.3-70b-instruct:free', 'ai.fallback_model' => 'google/gemma-3-27b-it:free',
            'news.mode' => 'ai', 'news.rewrite_mode' => 'faithful', 'news.language' => 'Spanish', 'news.per_run' => '1', 'news.auto_publish' => '0', 'news.max_age_days' => '3',
            'news.audience' => 'Pymes y autónomos de habla hispana que quieren aplicar inteligencia artificial y automatización a sus procesos.',
            'seo.article_default_schema' => 'BlogPosting',
            'site.offices' => "Ahmedabad\nBengaluru\nChennai\nDelhi\nMangalore\nLucknow\nUSA",
            'site.cta_text' => 'Get in touch', 'site.cta_url' => '/#contact',
            'site.footer_cta_text' => 'Start a brief', 'site.footer_cta_url' => '/#contact',
            'site.footer_note' => '© {year} Cab Media. All rights reserved.',
        ];
        foreach ($defaults as $k => $v) {
            if (Setting::get($k) === null) Setting::put($k, $v);
        }
        if (! Setting::get('cron.token')) Setting::put('cron.token', Str::random(40));

        if (MenuItem::count() === 0) $this->menus();
    }

    /** Header and footer menus (editable in Admin > Header & Footer). Parents without a URL act as column headings in the footer. */
    private function menus(): void
    {
        $services = [
            ['Advertising & Media', '/#services', 'Creative, advertising, branding, media, PR'],
            ['Digital Marketing', '/services/digital-marketing', 'Be found, be remembered and keep customers coming back.'],
            ['Development', '/services/development', 'Websites, apps and software built around how you work.'],
            ['IT Services', '/services/it-services', 'The infrastructure and data foundations behind a reliable business.'],
            ['AI Services', '/services/ai-services', 'Practical AI inside your marketing, websites and decisions.'],
        ];
        $links = [['Home', '/#home'], ['About Us', '/#about'], ['Testimonials', '/#testimonials'], ['Blog', '/blog'], ['Contact', '/#contact']];

        $n = 0;
        foreach ([['Home', '/#home'], ['About Us', '/#about'], ['Services', '/#services', $services], ['Testimonials', '/#testimonials'], ['Blog', '/blog'], ['Contact', '/#contact']] as $row) {
            $parent = MenuItem::create(['location' => 'header', 'title' => $row[0], 'url' => $row[1], 'sort_order' => $n++, 'is_active' => true]);
            foreach ($row[2] ?? [] as $i => [$t, $u, $d]) MenuItem::create(['location' => 'header', 'parent_id' => $parent->id, 'title' => $t, 'url' => $u, 'description' => $d, 'sort_order' => $i, 'is_active' => true]);
        }

        $explore = MenuItem::create(['location' => 'footer', 'title' => 'Explore', 'url' => null, 'sort_order' => 0, 'is_active' => true]);
        foreach ($links as $i => [$t, $u]) MenuItem::create(['location' => 'footer', 'parent_id' => $explore->id, 'title' => $t, 'url' => $u, 'sort_order' => $i, 'is_active' => true]);
        $svc = MenuItem::create(['location' => 'footer', 'title' => 'Services', 'url' => null, 'sort_order' => 1, 'is_active' => true]);
        foreach ($services as $i => [$t, $u]) MenuItem::create(['location' => 'footer', 'parent_id' => $svc->id, 'title' => $t, 'url' => $u, 'sort_order' => $i, 'is_active' => true]);
    }
}
