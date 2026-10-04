<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KeywordGroup extends Model
{
    use HasFactory;

    protected $fillable = ['label'];

    public function keywords()
    {
        return $this->belongsToMany(Keyword::class)->withTimestamps();
    }

    public function scopeFilter($query, array $filters)
    {
        $query->when($filters['search'] ?? null, fn ($query, string $search) =>
            $query->where(fn ($query) =>
                $query->whereRaw('LOWER(label) LIKE ?', ['%' . strtolower($search) . '%'])
            )
        );
    }
}
