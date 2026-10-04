<?php

namespace App\Models;

use App\Helpers\HasTraduction;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Session;

class Article extends Model
{
    use HasFactory, HasTraduction, SoftDeletes;

    protected $fillable = ['illustration_id', 'title', 'slug', 'published_at'];

    public function resolveRouteBinding($value, $field = null)
    {
        return $this->where($field ?? 'id', $value)->withTrashed()->firstOrFail();
    }

    public function scopeWithTraductionInLocale(Builder $query)
    {
        return $query->with('traductions', function ($query) {
            return $query->where('langue', Session::get('locale'));
        })->whereHas('traductions', function (Builder $builder) {
            $builder->where('langue', Session::get('locale'));
        });
    }

    public function scopePublished(Builder $query)
    {
        return $query->whereNotNull('published_at')->where('published_at', '<=', now());
    }

    public function illustration()
    {
        return $this->belongsTo(File::class);
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class)->using(ArticleCategory::class)->withTimestamps();
    }

    public function internalCategories()
    {
        return $this->belongsToMany(InternalCategory::class)->using(ArticleInternalCategory::class)->withTimestamps();
    }

    public function keywords()
    {
        return $this->belongsToMany(Keyword::class)->withTimestamps();
    }

    public function scopeFilter($query, array $filters)
    {
        $query->when($filters['search'] ?? null, fn ($query, string $search) =>
            $query->where(fn ($query) =>
                $query->whereRaw('LOWER(title) LIKE ?', ['%' . strtolower($search) . '%'])
            )
        );

        $query->when($filters['trashed'] ?? null, function ($query, string $trashed) {
            if ($trashed === 'with') {
                $query->withTrashed();
            } elseif ($trashed === 'only') {
                $query->onlyTrashed();
            }
        });

        $query->when($filters['internal_category_id'] ?? null, fn ($query, string $internal_category_id) =>
            $query->whereHas('internalCategories', fn ($query) =>
                $query->where('internal_category_id', $internal_category_id)
            )
        );

        $query->when($filters['published'] ?? null, function($query, string $published) {
            if ($published === 'only') {
                $query->whereNotNull('published_at');
            }
        });
    }
}
