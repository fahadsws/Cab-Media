<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Content tables: settings, categories, blog sources, blog posts (articles table), pages, SEO, menus, view counters, automation log. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $t) {
            $t->string('key')->primary();
            $t->longText('value')->nullable();
            $t->boolean('is_secret')->default(false);
            $t->timestamps();
        });

        Schema::create('categories', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('slug')->unique();
            $t->boolean('is_active')->default(true)->index();
            $t->timestamps();
        });

        Schema::create('news_sources', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('type', 10)->default('rss');             // rss | page
            $t->string('feed_url', 500);
            $t->string('link_pattern')->nullable();
            $t->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $t->boolean('is_active')->default(true);
            $t->timestamp('last_fetched_at')->nullable();
            $t->string('last_status')->nullable();
            $t->unsignedSmallInteger('fail_count')->default(0);
            $t->timestamp('last_success_at')->nullable();
            $t->unsignedSmallInteger('last_found')->default(0);
            $t->timestamps();
        });

        Schema::create('articles', function (Blueprint $t) {
            $t->id();
            $t->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('news_source_id')->nullable()->constrained()->nullOnDelete();
            $t->string('title');
            $t->string('slug')->unique();
            $t->string('excerpt', 600)->nullable();
            $t->longText('body')->nullable();
            $t->string('image_path')->nullable();
            // SEO
            $t->string('meta_title')->nullable();
            $t->string('meta_description', 320)->nullable();
            $t->string('meta_keywords', 300)->nullable();
            $t->string('canonical_url', 500)->nullable();
            $t->string('robots', 40)->default('index,follow');
            $t->string('og_title', 160)->nullable();
            $t->string('og_description', 320)->nullable();
            $t->string('og_image', 500)->nullable();
            $t->string('schema_type', 30)->default('BlogPosting');
            $t->longText('schema_json')->nullable();
            $t->json('tags')->nullable();
            $t->json('tldr')->nullable();
            $t->json('faq')->nullable();
            // Publishing
            $t->string('status', 20)->default('draft')->index();      // draft | scheduled | published
            $t->timestamp('published_at')->nullable()->index();
            // Source / automation
            $t->string('source_url', 700)->nullable();
            $t->string('source_title', 300)->nullable();
            $t->string('source_hash', 64)->nullable()->unique();
            $t->json('source_links')->nullable();
            $t->unsignedTinyInteger('similarity')->nullable();
            $t->string('story_key', 120)->nullable()->index();
            $t->unsignedBigInteger('duplicate_of')->nullable()->index();
            $t->boolean('is_ai_generated')->default(false);
            // Homepage placement
            $t->boolean('is_featured')->default(false)->index();
            $t->unsignedBigInteger('views')->default(0);
            $t->timestamps();
        });

        Schema::create('article_views_daily', function (Blueprint $t) {
            $t->id();
            $t->foreignId('article_id')->constrained()->cascadeOnDelete();
            $t->date('day');
            $t->unsignedInteger('views')->default(0);
            $t->unique(['article_id', 'day']);
            $t->index('day');
        });

        Schema::create('pages', function (Blueprint $t) {
            $t->id();
            $t->string('title', 200);
            $t->string('slug', 160)->unique();
            $t->string('template', 20)->default('default');
            $t->string('status', 20)->default('draft')->index();
            $t->text('excerpt')->nullable();
            $t->longText('body')->nullable();
            $t->string('featured_image', 500)->nullable();
            $t->string('meta_title', 120)->nullable();
            $t->string('meta_description', 320)->nullable();
            $t->string('meta_keywords', 300)->nullable();
            $t->string('canonical_url', 500)->nullable();
            $t->string('robots', 40)->default('index,follow');
            $t->string('og_title', 160)->nullable();
            $t->string('og_description', 320)->nullable();
            $t->string('og_image', 500)->nullable();
            $t->string('schema_type', 30)->default('WebPage');
            $t->longText('schema_json')->nullable();
            $t->json('faq')->nullable();
            $t->timestamp('published_at')->nullable();
            $t->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->softDeletes();
        });

        Schema::create('seo_entries', function (Blueprint $t) {
            $t->id();
            $t->string('route_key', 40)->unique();
            $t->string('meta_title', 120)->nullable();
            $t->string('meta_description', 320)->nullable();
            $t->string('meta_keywords', 300)->nullable();
            $t->string('canonical_url', 500)->nullable();
            $t->string('robots', 40)->default('index,follow');
            $t->string('og_title', 160)->nullable();
            $t->string('og_description', 320)->nullable();
            $t->string('og_image', 500)->nullable();
            $t->string('schema_type', 30)->default('None');
            $t->longText('schema_json')->nullable();
            $t->json('faq')->nullable();
            $t->timestamps();
        });

        Schema::create('menu_items', function (Blueprint $t) {
            $t->id();
            $t->string('location', 10)->index();                     // header | footer
            $t->foreignId('parent_id')->nullable()->constrained('menu_items')->cascadeOnDelete();
            $t->string('title', 100);
            $t->string('url', 500)->nullable();
            $t->boolean('open_new_tab')->default(false);
            $t->boolean('is_active')->default(true);
            $t->unsignedInteger('sort_order')->default(0);
            $t->timestamps();
        });

        Schema::create('automation_logs', function (Blueprint $t) {
            $t->id();
            $t->string('task', 40)->index();
            $t->string('status', 10);
            $t->text('message')->nullable();
            $t->unsignedInteger('duration_ms')->default(0);
            $t->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        foreach (['automation_logs', 'menu_items', 'seo_entries', 'pages', 'article_views_daily', 'articles', 'news_sources', 'categories', 'settings'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
