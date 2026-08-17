<?php

namespace App\Domains\CertificationAndSkillRoadmap\Http\Controllers;

use App\Domains\CertificationAndSkillRoadmap\Http\Resources\CertificationResource;
use App\Domains\CertificationAndSkillRoadmap\Models\Certification;
use App\Domains\SharedRestApiGateway\Http\Controllers\ApiController;
use Illuminate\Http\Request;

class CertificationController extends ApiController
{
    public function index(Request $request)
    {
        $certifications = Certification::forUser($request->user()->id)
            ->when($request->input('status'), fn ($q, $s) => $q->where('status', $s))
            ->orderBy('expiry_at')
            ->orderBy('title')
            ->get();

        return $this->respondSuccess(CertificationResource::collection($certifications));
    }

    public function store(Request $request)
    {
        $cert = Certification::create(array_merge(
            ['user_id' => $request->user()->id],
            $this->validateCert($request)
        ));

        return $this->respondCreated(CertificationResource::make($cert));
    }

    public function show(Request $request, Certification $certification)
    {
        if ($certification->user_id !== $request->user()->id) {
            return $this->respondError('Certification not found.', 404);
        }
        return $this->respondSuccess(CertificationResource::make($certification));
    }

    public function update(Request $request, Certification $certification)
    {
        if ($certification->user_id !== $request->user()->id) {
            return $this->respondError('Certification not found.', 404);
        }
        $certification->update($this->validateCert($request, true));
        return $this->respondSuccess(CertificationResource::make($certification));
    }

    public function destroy(Request $request, Certification $certification)
    {
        if ($certification->user_id !== $request->user()->id) {
            return $this->respondError('Certification not found.', 404);
        }
        $certification->delete();
        return $this->respondNoContent();
    }

    public function expiringSoon(Request $request)
    {
        $days = min(365, max(1, (int) $request->input('days', 60)));

        $certs = Certification::forUser($request->user()->id)
            ->where('status', 'attained')
            ->whereNotNull('expiry_at')
            ->where('expiry_at', '>=', now()->toDateString())
            ->where('expiry_at', '<=', now()->addDays($days)->toDateString())
            ->orderBy('expiry_at')
            ->get();

        return $this->respondSuccess(CertificationResource::collection($certs));
    }

    public function stats(Request $request)
    {
        $certs = Certification::forUser($request->user()->id)->get();

        $skills = [];
        foreach ($certs as $cert) {
            foreach ($cert->skills ?? [] as $skill) {
                $skills[$skill] = ($skills[$skill] ?? 0) + 1;
            }
        }
        arsort($skills);

        return $this->respondSuccess([
            'total' => $certs->count(),
            'by_status' => $certs->groupBy('status')->map->count(),
            'expiring_within_90d' => $certs->where('status', 'attained')
                ->filter(fn ($c) => $c->daysUntilExpiry() !== null && $c->daysUntilExpiry() <= 90 && $c->daysUntilExpiry() >= 0)
                ->count(),
            'skills' => $skills,
        ]);
    }

    private function validateCert(Request $request, bool $partial = false): array
    {
        $rules = [
            'title' => [$partial ? 'sometimes' : 'required', 'string', 'max:255'],
            'issuer' => ['nullable', 'string', 'max:255'],
            'status' => ['sometimes', 'in:planned,in_progress,attained,expired'],
            'issued_at' => ['nullable', 'date'],
            'expiry_at' => ['nullable', 'date', 'after_or_equal:issued_at'],
            'credential_url' => ['nullable', 'url', 'max:2048'],
            'skills' => ['nullable', 'array'],
            'skills.*' => ['string', 'max:128'],
            'notes' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
        ];

        return $request->validate($rules);
    }
}