<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\KeywordGroupStoreOrUpdateRequest;
use App\Models\KeywordGroup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Illuminate\Support\Facades\Request;
use Inertia\Response;

class KeywordGroupController extends Controller
{
    public function index()
    {
        return Inertia::render('Dashboard/KeywordGroup/Index', [
            'filters' => Request::all('search'),
            'keywordGroups' => new ResourceCollection(
                KeywordGroup::filter(Request::only('search'))
                ->orderBy('label', 'asc')
                ->paginate()
            ),
        ]);
    }

    public function create()
    {
        return Inertia::render('Dashboard/KeywordGroup/Create');
    }

    public function store(KeywordGroupStoreOrUpdateRequest $request): RedirectResponse 
    {
        $keywordGroup = KeywordGroup::create($request->validated());
        $keywordGroup->keywords()->attach($request->keyword_ids);

        return Redirect::route('dashboard.keyword-groups.index')->with('success', 'Keyword group created.');
    }

    public function edit(KeywordGroup $keywordGroup): Response
    {
        return Inertia::render('Dashboard/KeywordGroup/Edit', [
            'keywordGroup' => $keywordGroup->load('keywords'),
        ]);
    }

    public function update(KeywordGroup $keywordGroup, KeywordGroupStoreOrUpdateRequest $request): RedirectResponse {
        $keywordGroup->updateOrFail($request->validated());
        $keywordGroup->keywords()->sync($request->keyword_ids);

        return Redirect::back()->with('success', 'Keyword group updated.');
    }

    public function destroy(KeywordGroup $keywordGroup): RedirectResponse {
        $keywordGroup->deleteOrFail();

        return Redirect::back()->with('success', 'Keyword group deleted.');
    }
}
