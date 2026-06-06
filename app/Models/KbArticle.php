<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Database\Factories\KbArticleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

#[Fillable([
    'company_id',
    'author_membership_id',
    'title',
    'slug',
    'body_markdown',
    'body_html_cached',
    'tags',
    'status',
    'published_at',
])]
class KbArticle extends Model
{
    /** @use HasFactory<KbArticleFactory> */
    use BelongsToCompany, HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'published_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (KbArticle $article): void {
            $article->uuid ??= (string) Str::uuid();
            $article->slug ??= static::uniqueSlugFor($article->company_id, $article->title);

            if ($article->status === 'published' && $article->published_at === null) {
                $article->published_at = now();
            }

            if ($article->body_html_cached === null) {
                $article->body_html_cached = static::renderMarkdown($article->body_markdown);
            }
        });
    }

    public function authorMembership(): BelongsTo
    {
        return $this->belongsTo(Membership::class, 'author_membership_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public static function uniqueSlugFor(int $companyId, string $title, ?int $ignoreArticleId = null): string
    {
        $base = Str::slug($title) ?: 'articulo';
        $slug = $base;
        $suffix = 2;

        while (self::withoutTenant()
            ->where('company_id', $companyId)
            ->where('slug', $slug)
            ->when($ignoreArticleId !== null, fn ($query) => $query->whereKeyNot($ignoreArticleId))
            ->exists()) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }

    private static function renderMarkdown(string $markdown): string
    {
        return Str::markdown($markdown, [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);
    }
}
