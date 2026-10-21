<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Page extends Model
{
    use SoftDeletes;

    public const TEMPLATES = ['default' => 'Standard', 'full' => 'Full width'];
    public const SCHEMA_TYPES = \App\Support\SeoRules::SCHEMA_TYPES;
    public const ROBOTS = \App\Support\SeoRules::ROBOTS;

    /** Paths that already belong to the site and must never be taken by a page slug. */
    public const RESERVED = ['admin', 'cron', 'blog', 'search', 'sitemap', 'robots', 'llms', 'feed', 'storage', 'uploads', 'css', 'js', 'img', 'vendor', 'build', 'login', 'logout', 'api', 'up'];

    protected $guarded = [];
    protected $casts = ['faq' => 'array', 'published_at' => 'datetime'];

    public function author() { return $this->belongsTo(User::class, 'author_id'); }

    public function scopePublished($q)
    {
        return $q->where('status', 'published')->where(fn ($x) => $x->whereNull('published_at')->orWhere('published_at', '<=', now()));
    }

    public function getUrlAttribute(): string { return url('/'.$this->slug); }

    public function getImageUrlAttribute(): ?string
    {
        return $this->featured_image ? (Str::startsWith($this->featured_image, ['http://', 'https://']) ? $this->featured_image : asset(ltrim($this->featured_image, '/'))) : null;
    }

    public function getIsLiveAttribute(): bool
    {
        return $this->status === 'published' && (! $this->published_at || $this->published_at->lte(now()));
    }

    /** Clean FAQ rows (both question and answer present). */
    public function faqItems(): array
    {
        return collect($this->faq ?? [])->filter(fn ($f) => filled($f['q'] ?? null) && filled($f['a'] ?? null))->values()->all();
    }

}
