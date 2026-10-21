<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Site;
use Illuminate\Support\Facades\Route;

/*
| Public website: single-page site for cabmedia.in (Site\PageController@home + resources/views/site/pages/home.blade.php).
| Blog: SiteBlogController. Admin-created pages: SitePageController@cms (catch-all, last).
*/
Route::get('/', [Site\PageController::class, 'home'])->name('home');
Route::get('/services', fn () => redirect('/#services', 301));
Route::get('/services/{slug}', [Site\PageController::class, 'service'])->where('slug', '[a-z0-9-]+')->name('service.show');
Route::get('/sitemap.xml', [Site\PageController::class, 'sitemap'])->name('sitemap');
Route::post('/api/contacto', [Site\PageController::class, 'contactSubmit'])->middleware('throttle:6,1')->name('contacto.submit');
Route::get('/blog', [Site\BlogController::class, 'index'])->name('blog.index');
Route::get('/blog/feed.xml', [Site\BlogController::class, 'feed'])->name('blog.feed');
Route::get('/blog/category/{category:slug}', [Site\BlogController::class, 'index'])->name('blog.category');
Route::get('/blog/{slug}', [Site\BlogController::class, 'show'])->where('slug', '[a-z0-9-]+')->name('blog.show');

// ---- Cron (hit from cPanel cron / cron-job.org) ----------------------------------------------------------------------
// The work runs in the background AFTER the response is sent, under a lock. Task intervals: App\Services\Automation::TASKS
// (admin: /admin/automation shows the real URLs; `php artisan cron:urls` prints ready-to-paste crontab lines).
//   /cron/{token}          -> every task that is due (ping every 5 min)
//   /cron/{token}/{task}   -> one task on its own timeline
Route::match(['get', 'post'], '/cron/{token}/{task?}', Site\CronController::class)
    ->where('task', implode('|', array_keys(\App\Services\Automation::TASKS)))
    ->middleware('throttle:30,1')->name('cron');

/* ------------------------------ Admin panel ------------------------------ */
Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('login', [Admin\AuthController::class, 'showLogin'])->name('login');
        Route::post('login', [Admin\AuthController::class, 'login'])->middleware('throttle:10,1')->name('login.submit');
    });

    Route::middleware(['auth', 'permission:admin.access'])->group(function () {
        Route::post('logout', [Admin\AuthController::class, 'logout'])->name('logout');
        Route::get('/', Admin\DashboardController::class)->name('dashboard');
        Route::post('media/upload', [Admin\MediaController::class, 'upload'])->name('media.upload');   // editor (TinyMCE) uploads
        Route::middleware('permission:media.manage')->prefix('media')->name('media.')->group(function () {   // central media library
            Route::get('/', [Admin\MediaLibraryController::class, 'index'])->name('index');
            Route::get('list', [Admin\MediaLibraryController::class, 'list'])->name('list');
            Route::post('files', [Admin\MediaLibraryController::class, 'upload'])->name('files');
            Route::post('folder', [Admin\MediaLibraryController::class, 'folder'])->name('folder');
            Route::delete('/', [Admin\MediaLibraryController::class, 'destroy'])->name('destroy');
        });
        Route::middleware('permission:contacts.manage')->group(function () {
            Route::get('contacts', [Admin\ContactRequestController::class, 'index'])->name('contacts.index');
            Route::get('contacts/{contact}', [Admin\ContactRequestController::class, 'show'])->name('contacts.show');
            Route::put('contacts/{contact}', [Admin\ContactRequestController::class, 'update'])->name('contacts.update');
            Route::delete('contacts/{contact}', [Admin\ContactRequestController::class, 'destroy'])->name('contacts.destroy');
        });
        Route::get('profile', [Admin\ProfileController::class, 'edit'])->name('profile');
        Route::put('profile', [Admin\ProfileController::class, 'update'])->name('profile.update');

        // Articles (per-record ownership rules are enforced in the controller)
        Route::middleware('permission:articles.view')->group(function () {
            Route::get('articles', [Admin\ArticleController::class, 'index'])->name('articles.index');
            Route::get('articles/data', [Admin\ArticleController::class, 'data'])->name('articles.data');
            Route::post('articles/bulk', [Admin\ArticleController::class, 'bulk'])->name('articles.bulk');
        });
        Route::middleware('permission:articles.create')->group(function () {
            Route::get('articles/create', [Admin\ArticleController::class, 'create'])->name('articles.create');
            Route::post('articles', [Admin\ArticleController::class, 'store'])->name('articles.store');
        });
        Route::get('articles/{article}/edit', [Admin\ArticleController::class, 'edit'])->name('articles.edit');
        Route::get('articles/{article}/preview', [Admin\ArticleController::class, 'preview'])->middleware('permission:articles.view')->name('articles.preview');
        Route::put('articles/{article}', [Admin\ArticleController::class, 'update'])->name('articles.update');
        Route::delete('articles/{article}', [Admin\ArticleController::class, 'destroy'])->name('articles.destroy');
        Route::post('articles/import-url', [Admin\ArticleController::class, 'importUrl'])->middleware('permission:articles.create')->name('articles.import-url');
        Route::post('articles/{article}/ai-seo', [Admin\ArticleController::class, 'aiSeo'])->name('articles.ai-seo');
        Route::post('articles/ai-draft', [Admin\ArticleController::class, 'aiDraft'])->middleware('permission:articles.create')->name('articles.ai');

        Route::middleware('permission:pages.manage')->group(function () {
            Route::get('pages/data', [Admin\PageController::class, 'data'])->name('pages.data');
            Route::post('pages/bulk', [Admin\PageController::class, 'bulk'])->name('pages.bulk');
            Route::get('pages/{page}/preview', [Admin\PageController::class, 'preview'])->name('pages.preview');
            Route::resource('pages', Admin\PageController::class)->except(['show']);
        });

        Route::middleware('permission:seo.manage')->group(function () {
            Route::get('seo', [Admin\SeoController::class, 'index'])->name('seo.index');
            Route::get('seo/{key}', [Admin\SeoController::class, 'edit'])->name('seo.edit');
            Route::put('seo/{key}', [Admin\SeoController::class, 'update'])->name('seo.update');
            Route::delete('seo/{key}', [Admin\SeoController::class, 'destroy'])->name('seo.destroy');
        });

        Route::middleware('permission:categories.manage')->group(function () {
            Route::get('categories/data', [Admin\CategoryController::class, 'data'])->name('categories.data');
            Route::post('categories/bulk', [Admin\CategoryController::class, 'bulk'])->name('categories.bulk');
            Route::resource('categories', Admin\CategoryController::class)->except(['show']);
        });

        Route::middleware('permission:sources.manage')->group(function () {
            Route::get('sources/data', [Admin\NewsSourceController::class, 'data'])->name('sources.data');
            Route::post('sources/bulk', [Admin\NewsSourceController::class, 'bulk'])->name('sources.bulk');
            Route::resource('sources', Admin\NewsSourceController::class)->except(['show']);
            Route::post('sources/{source}/fetch', [Admin\NewsSourceController::class, 'fetch'])->name('sources.fetch');
        });


        Route::middleware('permission:users.manage')->group(function () {
            Route::get('users/data', [Admin\UserController::class, 'data'])->name('users.data');
            Route::post('users/bulk', [Admin\UserController::class, 'bulk'])->name('users.bulk');
            Route::resource('users', Admin\UserController::class)->except(['show']);
        });
        Route::middleware('permission:roles.manage')->group(function () {
            Route::get('roles/data', [Admin\RoleController::class, 'data'])->name('roles.data');
            Route::post('roles/bulk', [Admin\RoleController::class, 'bulk'])->name('roles.bulk');
            Route::resource('roles', Admin\RoleController::class)->except(['show']);
        });

        Route::middleware('permission:settings.manage')->group(function () {
            Route::get('settings', [Admin\SettingController::class, 'edit'])->name('settings');
            Route::put('settings', [Admin\SettingController::class, 'update'])->name('settings.update');
            Route::post('settings/check', [Admin\SettingController::class, 'check'])->name('settings.check');
        });

        Route::middleware('permission:menus.manage')->group(function () {
            Route::resource('menus', Admin\MenuItemController::class)->except(['show']);
        });

        Route::middleware('permission:automation.manage')->group(function () {
            Route::get('automation', [Admin\AutomationController::class, 'index'])->name('automation');
            Route::get('automation/logs', [Admin\AutomationController::class, 'logs'])->name('automation.logs');
            Route::post('automation/run', [Admin\AutomationController::class, 'run'])->name('automation.run');
        });
    });
});

/* Custom pages created in the admin: keep this LAST so it only matches when no other route does. */
Route::get('/{slug}', [Site\PageController::class, 'cms'])->where('slug', '[a-z0-9]+(?:-[a-z0-9]+)*')->name('page.show');
