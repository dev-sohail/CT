<?php

namespace App\Domains\SecondBrainPersonalWiki\Http\Controllers;

use App\Domains\SecondBrainPersonalWiki\Http\Requests\TagRequest;
use App\Domains\SecondBrainPersonalWiki\Http\Resources\TagResource;
use App\Domains\SecondBrainPersonalWiki\Models\Tag;
use App\Domains\SharedRestApiGateway\Http\Controllers\ApiController;
use Illuminate\Http\Request;

class TagController extends ApiController
{
    public function index(Request $request)
    {
        return $this->respondSuccess(TagResource::collection(Tag::orderBy('name')->get()));
    }

    public function store(TagRequest $request)
    {
        return $this->respondCreated(TagResource::make(Tag::create($request->validated())));
    }
}
