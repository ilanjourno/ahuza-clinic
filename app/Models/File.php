<?php

namespace App\Models;

use App\Http\Requests\FileStoreRequest;
use App\Services\ImageOptimizer;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class File extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id', 'created_at', 'updated_at'];

    public function resolveRouteBinding($value, $field = null)
    {
        return $this->where($field ?? 'id', $value)->withTrashed()->firstOrFail();
    }

    /** Optimizes and stores an upload, returning the attributes to persist. */
    public static function attributesFor(UploadedFile $upload): array
    {
        app(ImageOptimizer::class)->optimize($upload);

        $size = $upload->getSize();
        $mimeType = $upload->getMimeType();
        $name = $upload->store();

        return [
            'name' => $name,
            'size' => $size,
            'mime_type' => $mimeType,
            'link' => Storage::url($name),
        ];
    }

    public static function store(FileStoreRequest $request)
    {
        return File::create(['label' => $request->label, ...self::attributesFor($request->file('file'))]);
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
