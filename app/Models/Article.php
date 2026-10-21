<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Article extends Model
{
    protected $guarded = [];
    protected $casts = ['published_at' => 'datetime', 'is_ai_generated' => 'boolean', 'is_featured' => 'boolean', 'tags' => 'array', 'tldr' => 'array', 'faq' => 'array', 'source_links' => 'array'];

    public function category() { return $this->belongsTo(Category::class); }
    public function author() { return $this->belongsTo(User::class, 'user_id'); }
    public function source() { return $this->belongsTo(NewsSource::class, 'news_source_id'); }

    public function scopePublished($q)
    {
        return $q->where('status', 'published')->where('published_at', '<=', now())->whereNull('duplicate_of');
    }

    /**
     * Story identity: "<subject-topic>:<event group>", e.g. "rbi-repo-rate:update". Rate, price, result and policy changes are the
     * "update" group; any other one-off announcement is "event". Anything else (explainers, opinion) has no key.
     */
    public static function storyKey(?string $subject, ?string $topic, ?string $event): ?string
    {
        $group = match ($event) { 'update', 'price_update', 'results', 'rate', 'policy' => 'update', 'event', 'announcement', 'launch', 'merger' => 'event', default => null };
        $name = Str::slug(trim(preg_replace('/^'.preg_quote((string) $subject, '/').'\s+/i', '', (string) $topic)));
        if (! $group || ! $subject || $name === '') return null;
        return Str::slug($subject).'-'.$name.':'.$group;
    }

    /** Same story? Compares ignoring hyphen placement, since the pre-check and the writer may slug "3 Series" differently. */
    public static function sameStory(?string $a, ?string $b): bool
    {
        if (! $a || ! $b || ! str_contains($a, ':') || ! str_contains($b, ':')) return false;
        $n = fn ($k) => str_replace('-', '', $k);
        return $n($a) === $n($b);
    }

    /** How long a story key blocks a second article: one-off events are covered for a week, rate/result updates for days. */
    public static function storyWindowDays(?string $key): int
    {
        return str_ends_with((string) $key, ':update') ? 3 : 7;
    }

    public function getImageUrlAttribute(): string
    {
        if (! $this->image_path) return asset('img/placeholder.svg');
        return Str::startsWith($this->image_path, ['http://', 'https://']) ? $this->image_path : (Str::startsWith($this->image_path, '/') ? asset(ltrim($this->image_path, '/')) : Storage::disk('public')->url($this->image_path));
    }

    public function getUrlAttribute(): string { return route('blog.show', $this->slug); }

    /** Estimated minutes to read (about 200 words a minute, never less than 1). */
    public function getReadMinutesAttribute(): int
    {
        return max(1, (int) ceil(str_word_count(strip_tags((string) $this->body)) / 200));
    }

    /** True when the article has its own picture (the cards draw a category-coloured cover otherwise). */
    public function getHasImageAttribute(): bool { return filled($this->image_path); }

    public static function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug(Str::limit($title, 90, '')) ?: 'article';
        $slug = $base; $i = 2;
        while (static::where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = $base.'-'.$i++;
        }
        return $slug;
    }

}
