<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property integer $id
 * @property integer $user_id
 * @property string $title
 * @property string $logo_url
 * @property string $qrcode_url
 * @property string $company_url
 * @property string $created_at
 * @property string $updated_at
 */

class Company extends Model
{
    use HasFactory;

    // Pinned explicitly: Eloquent's newRelatedInstance() inherits the
    // caller's connection for any related model that doesn't set its own,
    // so being eager-loaded from a 'wordpress'-connection model (e.g.
    // CompanyEmployee::with('company')) would otherwise resolve this model
    // on the 'wordpress' connection too — double-prefixing the already
    // wp_-prefixed table name into wp_wp_companies.
    protected $connection = 'mysql';

    protected $table = 'wp_companies';

    protected $fillable = [
        'user_id', 'title', 'slug', 'logo_url', 'qrcode_url', 'company_url',
        'role', 'team', 'organizational_goals', 'readiness_priorities',
    ];

    protected static function booted(): void
    {
        // URL slug (/{slug}/arp ...) defaults to the company name; whatever is
        // typed in manually is only normalized, never overridden.
        static::saving(function (Company $company) {
            // Deployed before the slug column exists? Don't break company saves.
            static $hasSlugColumn = null;
            $hasSlugColumn ??= \Illuminate\Support\Facades\Schema::hasColumn('wp_companies', 'slug');
            if (! $hasSlugColumn) {
                unset($company->slug);

                return;
            }

            $wanted = trim((string) $company->slug) !== '' ? (string) $company->slug : (string) $company->title;
            if ($company->isDirty('slug') || trim((string) $company->getOriginal('slug')) === '') {
                $company->slug = static::uniqueSlug($wanted, $company->exists ? $company->id : null);
            }
        });
    }

    /** Lower-case, dash-separated slug that no other company uses (suffixes -2, -3 ... on clash). */
    public static function uniqueSlug(string $source, ?int $ignoreId = null): string
    {
        $base = \Illuminate\Support\Str::slug($source) ?: 'company';
        $slug = $base;
        $i = 2;
        while (static::query()->where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }

    public function companyEmployees()
    {
        return $this->hasMany(CompanyEmployee::class,'company_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class,'user_id');
    }

    public function companyGroups()
    {
        return $this->hasMany(CompanyGroup::class, 'company_id');
    }
}
