<?php

namespace App\Models;

use App\Helpers\HasTraduction;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Session;

class FAQ extends Model
{
    use HasFactory, HasTraduction, SoftDeletes;

    protected $table = 'faqs';

    protected $fillable = [
        'label',
    ];

   public function scopeWithTraductionInLocale(Builder $query)
    {
        return $query->with('traductions', function ($query) {
            return $query->where('langue', Session::get('locale'));
        })->whereHas('traductions', function (Builder $builder) {
            $builder->where('langue', Session::get('locale'));
        });
    }

    public function scopeFilter($query, array $filters)
    {
        $query->when($filters['search'] ?? null, fn ($query, string $search) =>
            $query->where(fn ($query) =>
                $query->whereRaw('LOWER(label) LIKE ?', ['%' . strtolower($search) . '%'])
            )
        );

        $query->when($filters['trashed'] ?? null, function ($query, string $trashed) {
            if ($trashed === 'with') {
                $query->withTrashed();
            } elseif ($trashed === 'only') {
                $query->onlyTrashed();
            }
        });
    }
}
