<?php

namespace App\Domains\ResearchAndPDFAnnotationManager\Http\Controllers;

use App\Domains\ResearchAndPDFAnnotationManager\Http\Resources\PaperAnnotationResource;
use App\Domains\ResearchAndPDFAnnotationManager\Http\Resources\PaperResource;
use App\Domains\ResearchAndPDFAnnotationManager\Models\Paper;
use App\Domains\ResearchAndPDFAnnotationManager\Models\PaperAnnotation;
use App\Domains\SharedRestApiGateway\Http\Controllers\ApiController;
use Illuminate\Http\Request;

class PaperController extends ApiController
{
    public function index(Request $request)
    {
        $papers = Paper::forUser($request->user()->id)
            ->with('annotations')
            ->when($request->input('status'), fn ($q, $s) => $q->where('status', $s))
            ->when($request->input('q'), fn ($q, $s) => $q->where('title', 'like', "%{$s}%"))
            ->orderByDesc('updated_at')
            ->get();

        return $this->respondSuccess(PaperResource::collection($papers));
    }

    public function store(Request $request)
    {
        $paper = Paper::create(array_merge(
            ['user_id' => $request->user()->id],
            $this->validatePaper($request)
        ));

        return $this->respondCreated(PaperResource::make($paper));
    }

    public function show(Request $request, Paper $paper)
    {
        if ($paper->user_id !== $request->user()->id) {
            return $this->respondError('Paper not found.', 404);
        }
        return $this->respondSuccess(PaperResource::make($paper->load('annotations')));
    }

    public function update(Request $request, Paper $paper)
    {
        if ($paper->user_id !== $request->user()->id) {
            return $this->respondError('Paper not found.', 404);
        }
        $paper->update($this->validatePaper($request, true));
        return $this->respondSuccess(PaperResource::make($paper->fresh('annotations')));
    }

    public function destroy(Request $request, Paper $paper)
    {
        if ($paper->user_id !== $request->user()->id) {
            return $this->respondError('Paper not found.', 404);
        }
        $paper->delete();
        return $this->respondNoContent();
    }

    // ------------------------------------------------------------------
    // Annotations
    // ------------------------------------------------------------------

    public function annotations(Request $request, Paper $paper)
    {
        if ($paper->user_id !== $request->user()->id) {
            return $this->respondError('Paper not found.', 404);
        }
        return $this->respondSuccess(PaperAnnotationResource::collection($paper->annotations()->get()));
    }

    public function addAnnotation(Request $request, Paper $paper)
    {
        if ($paper->user_id !== $request->user()->id) {
            return $this->respondError('Paper not found.', 404);
        }

        $validated = $request->validate([
            'page' => ['nullable', 'integer', 'min:0'],
            'text' => ['required', 'string'],
            'note' => ['nullable', 'string'],
            'color' => ['nullable', 'string', 'max:16'],
        ]);

        $annotation = $paper->annotations()->create($validated);

        return $this->respondCreated(PaperAnnotationResource::make($annotation));
    }

    public function updateAnnotation(Request $request, Paper $paper, PaperAnnotation $annotation)
    {
        if ($paper->user_id !== $request->user()->id || $annotation->paper_id !== $paper->id) {
            return $this->respondError('Annotation not found.', 404);
        }

        $annotation->update($request->validate([
            'page' => ['nullable', 'integer', 'min:0'],
            'text' => ['sometimes', 'string'],
            'note' => ['nullable', 'string'],
            'color' => ['nullable', 'string', 'max:16'],
        ]));

        return $this->respondSuccess(PaperAnnotationResource::make($annotation));
    }

    public function deleteAnnotation(Request $request, Paper $paper, PaperAnnotation $annotation)
    {
        if ($paper->user_id !== $request->user()->id || $annotation->paper_id !== $paper->id) {
            return $this->respondError('Annotation not found.', 404);
        }
        $annotation->delete();
        return $this->respondNoContent();
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    private function validatePaper(Request $request, bool $partial = false): array
    {
        $rules = [
            'title' => [$partial ? 'sometimes' : 'required', 'string', 'max:512'],
            'authors' => ['nullable', 'array'],
            'authors.*' => ['string', 'max:255'],
            'year' => ['nullable', 'string', 'max:8'],
            'source' => ['nullable', 'string', 'max:255'],
            'url' => ['nullable', 'url', 'max:2048'],
            'file_path' => ['nullable', 'string', 'max:2048'],
            'abstract' => ['nullable', 'string'],
            'status' => ['sometimes', 'in:unread,reading,read,reviewed'],
            'rating' => ['nullable', 'integer', 'between:1,5'],
            'metadata' => ['nullable', 'array'],
        ];

        return $request->validate($rules);
    }
}