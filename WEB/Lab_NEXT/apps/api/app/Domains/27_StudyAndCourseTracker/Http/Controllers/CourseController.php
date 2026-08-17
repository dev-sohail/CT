<?php

namespace App\Domains\StudyAndCourseTracker\Http\Controllers;

use App\Domains\SharedRestApiGateway\Http\Controllers\ApiController;
use App\Domains\StudyAndCourseTracker\Http\Resources\CourseResource;
use App\Domains\StudyAndCourseTracker\Models\Course;
use Illuminate\Http\Request;

class CourseController extends ApiController
{
    public function index(Request $request)
    {
        $courses = Course::forUser($request->user()->id)
            ->when($request->input('status'), fn ($q, $s) => $q->where('status', $s))
            ->when($request->input('subject'), fn ($q, $s) => $q->where('subject', $s))
            ->orderByDesc('updated_at')
            ->get();

        return $this->respondSuccess(CourseResource::collection($courses));
    }

    public function store(Request $request)
    {
        $course = Course::create(array_merge(
            ['user_id' => $request->user()->id],
            $this->validateCourse($request)
        ));

        return $this->respondCreated(CourseResource::make($course));
    }

    public function show(Request $request, Course $course)
    {
        if ($course->user_id !== $request->user()->id) {
            return $this->respondError('Course not found.', 404);
        }
        return $this->respondSuccess(CourseResource::make($course));
    }

    public function update(Request $request, Course $course)
    {
        if ($course->user_id !== $request->user()->id) {
            return $this->respondError('Course not found.', 404);
        }

        $data = $this->validateCourse($request, true);
        if (($data['status'] ?? null) === 'completed' && !$course->completed_at) {
            $data['completed_at'] = now();
        }
        if (($data['status'] ?? null) === 'in_progress' && !$course->started_at) {
            $data['started_at'] = now();
        }

        $course->update($data);

        return $this->respondSuccess(CourseResource::make($course));
    }

    public function destroy(Request $request, Course $course)
    {
        if ($course->user_id !== $request->user()->id) {
            return $this->respondError('Course not found.', 404);
        }
        $course->delete();
        return $this->respondNoContent();
    }

    public function logHours(Request $request, Course $course)
    {
        if ($course->user_id !== $request->user()->id) {
            return $this->respondError('Course not found.', 404);
        }

        $validated = $request->validate(['hours' => ['required', 'numeric', 'min:0.1']]);
        $course->increment('hours_spent', $validated['hours']);

        if (!$course->started_at) {
            $course->update(['started_at' => now()]);
        }

        return $this->respondSuccess(CourseResource::make($course->fresh()));
    }

    public function stats(Request $request)
    {
        $courses = Course::forUser($request->user()->id)->get();

        return $this->respondSuccess([
            'total_courses' => $courses->count(),
            'in_progress' => $courses->where('status', 'in_progress')->count(),
            'completed' => $courses->where('status', 'completed')->count(),
            'total_hours_spent' => round($courses->sum('hours_spent'), 2),
            'by_subject' => $courses->groupBy('subject')->map->count(),
        ]);
    }

    private function validateCourse(Request $request, bool $partial = false): array
    {
        $rules = [
            'title' => [$partial ? 'sometimes' : 'required', 'string', 'max:255'],
            'subject' => ['nullable', 'string', 'max:255'],
            'url' => ['nullable', 'url', 'max:2048'],
            'provider' => ['nullable', 'string', 'max:255'],
            'status' => ['sometimes', 'in:not_started,in_progress,completed,paused'],
            'hours_target' => ['nullable', 'integer', 'min:0'],
            'hours_spent' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
        ];

        return $request->validate($rules);
    }
}