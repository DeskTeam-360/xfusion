<?php

namespace App\Models;

use App\Services\XfusionLlmKnowledgeService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class XfusionKnowledge extends WpPost
{
    public const POST_TYPE = 'xfusion_knowledge';

    public const META_CATEGORY = '_xfusion_knowledge_category';

    public const META_SYNC_STATUS = '_xfusion_llm_sync_status';

    public const META_SYNCED_AT = '_xfusion_llm_synced_at';

    public const META_SYNC_ERROR = '_xfusion_llm_sync_error';

    public const META_CHUNKS_ADDED = '_xfusion_llm_chunks_added';

    protected static function booted(): void
    {
        static::addGlobalScope('xfusion_knowledge_type', function (Builder $builder): void {
            $builder->where($builder->getModel()->getTable().'.post_type', self::POST_TYPE);
        });

        static::deleting(function (self $post): void {
            app(XfusionLlmKnowledgeService::class)->deleteFromVector((int) $post->ID);
            WpPostMeta::where('post_id', $post->ID)->delete();
        });
    }

    public function getCategoryAttribute(): ?string
    {
        return $this->getMeta(self::META_CATEGORY);
    }

    public function getSyncStatusAttribute(): ?string
    {
        return $this->getMeta(self::META_SYNC_STATUS);
    }

    public function getMeta(string $key, mixed $default = null): mixed
    {
        $row = $this->postMeta()->where('meta_key', $key)->first();

        return $row !== null ? $row->meta_value : $default;
    }

    public function setMeta(string $key, mixed $value): void
    {
        WpPostMeta::updateOrCreate(
            ['post_id' => $this->ID, 'meta_key' => $key],
            ['meta_value' => (string) $value]
        );
    }

    /**
     * @return array<string, mixed>
     */
    public static function defaultPostAttributes(int $authorId): array
    {
        $now = now();

        return [
            'post_author' => $authorId,
            'post_date' => $now->format('Y-m-d H:i:s'),
            'post_date_gmt' => $now->utc()->format('Y-m-d H:i:s'),
            'post_modified' => $now->format('Y-m-d H:i:s'),
            'post_modified_gmt' => $now->utc()->format('Y-m-d H:i:s'),
            'post_excerpt' => '',
            'comment_status' => 'closed',
            'ping_status' => 'closed',
            'post_password' => '',
            'to_ping' => '',
            'pinged' => '',
            'post_content_filtered' => '',
            'post_parent' => 0,
            'menu_order' => 0,
            'post_mime_type' => '',
            'comment_count' => 0,
            'post_type' => self::POST_TYPE,
        ];
    }

    public static function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title);
        if ($base === '') {
            $base = 'knowledge';
        }

        $slug = $base;
        $n = 1;

        while (static::withoutGlobalScopes()
            ->where('post_name', $slug)
            ->when($ignoreId, fn ($q) => $q->where('ID', '!=', $ignoreId))
            ->exists()) {
            $slug = $base.'-'.$n;
            $n++;
        }

        return $slug;
    }
}
