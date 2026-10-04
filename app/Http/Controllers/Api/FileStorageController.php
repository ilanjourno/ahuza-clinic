<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\FileStoreRequest;
use App\Http\Resources\ImageCollection;
use App\Models\File;
use Illuminate\Support\Facades\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class FileStorageController extends Controller
{
      public function index()
    {
        return new ResourceCollection(
            File::filter(Request::only('search'))->orderBy('label', 'asc')->paginate()
        );
    }

    public function list() {
        return new ImageCollection(File::orderBy('label', 'asc')->get());
    }

    public function store(FileStoreRequest $request)
    {
        return File::store($request);
    }
}
