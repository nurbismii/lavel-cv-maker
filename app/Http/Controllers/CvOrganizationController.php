<?php

namespace App\Http\Controllers;

use App\Services\VPeopleOrganizationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class CvOrganizationController extends Controller
{
    public function departments(VPeopleOrganizationService $organizations): JsonResponse
    {
        return $this->safeResponse(function () use ($organizations) {
            return $organizations->departments();
        });
    }

    public function divisions(Request $request, VPeopleOrganizationService $organizations): JsonResponse
    {
        $request->validate([
            'department_id' => ['nullable', 'string', 'max:32'],
        ]);

        return $this->safeResponse(function () use ($request, $organizations) {
            return $organizations->divisions($request->query('department_id'));
        });
    }

    public function positions(Request $request, VPeopleOrganizationService $organizations): JsonResponse
    {
        $request->validate([
            'department_id' => ['nullable', 'string', 'max:32'],
            'division_id' => ['nullable', 'string', 'max:32'],
        ]);

        return $this->safeResponse(function () use ($request, $organizations) {
            return $organizations->positions(
                $request->query('department_id'),
                $request->query('division_id')
            );
        });
    }

    private function safeResponse(callable $callback): JsonResponse
    {
        try {
            return response()->json([
                'data' => $callback(),
            ]);
        } catch (Throwable $exception) {
            Log::warning('V-People organization master lookup failed.', [
                'exception' => get_class($exception),
            ]);

            return response()->json([
                'message' => 'Master organisasi V-People sedang tidak tersedia.',
                'data' => [],
            ], 503);
        }
    }
}
