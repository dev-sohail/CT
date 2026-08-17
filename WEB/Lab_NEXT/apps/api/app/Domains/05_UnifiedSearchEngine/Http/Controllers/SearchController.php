<?php

namespace App\Domains\UnifiedSearchEngine\Http\Controllers;

use App\Domains\SharedRestApiGateway\Http\Controllers\ApiController;
use App\Domains\UnifiedSearchEngine\Http\Resources\SearchResultResource;
use App\Domains\UnifiedSearchEngine\Services\SearchService;
use Illuminate\Http\Request;

class SearchController extends ApiController
{
    public function __construct(private readonly SearchService $search) {}

    public function index(Request $request)
    {
        $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:255'],
            'type' => ['nullable', 'string', 'max:255'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $results = $this->search->search(
            $request->user()->id,
            $request->string('q'),
            $request->string('type')->toString() ?: null,
            $request->integer('limit', 50)
        );

        return $this->respondSuccess(
            SearchResultResource::collection($results)
        );
    }
}
