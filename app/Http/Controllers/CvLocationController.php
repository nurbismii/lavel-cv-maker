<?php

namespace App\Http\Controllers;

use App\Services\VPeopleLocationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class CvLocationController extends Controller
{
    public function provinces(VPeopleLocationService $locations): JsonResponse
    {
        return $this->safeResponse(function () use ($locations) {
            return $locations->provinces();
        });
    }

    public function regencies(Request $request, VPeopleLocationService $locations): JsonResponse
    {
        $request->validate([
            'province_id' => ['nullable', 'string', 'max:32'],
        ]);

        return $this->safeResponse(function () use ($request, $locations) {
            return $locations->regencies($request->query('province_id'));
        });
    }

    public function districts(Request $request, VPeopleLocationService $locations): JsonResponse
    {
        $request->validate([
            'regency_id' => ['nullable', 'string', 'max:32'],
        ]);

        return $this->safeResponse(function () use ($request, $locations) {
            return $locations->districts($request->query('regency_id'));
        });
    }

    public function villages(Request $request, VPeopleLocationService $locations): JsonResponse
    {
        $request->validate([
            'district_id' => ['nullable', 'string', 'max:32'],
        ]);

        return $this->safeResponse(function () use ($request, $locations) {
            return $locations->villages($request->query('district_id'));
        });
    }

    private function safeResponse(callable $callback): JsonResponse
    {
        try {
            return response()->json([
                'data' => $callback(),
            ]);
        } catch (Throwable $exception) {
            Log::warning('V-People location master lookup failed.', [
                'exception' => get_class($exception),
            ]);

            return response()->json([
                'message' => 'Master wilayah V-People sedang tidak tersedia.',
                'data' => [],
            ], 503);
        }
    }
}
