<?php

namespace App\Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Http\Requests\PublicKioskCardRequest;
use App\Modules\Core\Http\Requests\PublicKioskPunchRequest;
use App\Modules\Core\Http\Resources\PublicKioskCompanyResource;
use App\Modules\Core\Models\Company;
use App\Modules\Core\Models\Student;
use App\Modules\Core\Services\AttendanceKioskService;
use App\Modules\Core\Support\CardUid;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class PublicKioskController extends Controller
{
    public function show(string $companyUuid): PublicKioskCompanyResource|JsonResponse
    {
        $company = Company::query()->where('uuid', $companyUuid)->first();

        if ($company === null) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        return new PublicKioskCompanyResource($company);
    }

    public function punch(
        PublicKioskPunchRequest $request,
        string $companyUuid,
        AttendanceKioskService $kiosk
    ): JsonResponse {
        $company = Company::query()->where('uuid', $companyUuid)->first();

        if ($company === null) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        $result = $kiosk->punch($company, $request->validated('student_id'));

        return response()->json($result);
    }

    public function card(
        PublicKioskCardRequest $request,
        string $companyUuid,
        AttendanceKioskService $kiosk
    ): JsonResponse {
        $company = Company::query()->where('uuid', $companyUuid)->first();

        if ($company === null) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        $uid = CardUid::normalize($request->validated('uid'));
        if ($uid === null) {
            return response()->json(['message' => 'Card not registered'], 404);
        }

        $cacheKey = "kiosk-card:{$company->id}:{$uid}";
        $cached = Cache::get($cacheKey);
        if (is_array($cached) && isset($cached['body'])) {
            return response()->json($cached['body'], (int) ($cached['status'] ?? 200));
        }

        $student = Student::query()
            ->where('company_id', $company->id)
            ->where('card_uid', $uid)
            ->first();

        if ($student === null) {
            return response()->json(['message' => 'Card not registered'], 404);
        }

        $result = $kiosk->punchStudent($company, $student);

        Cache::put($cacheKey, [
            'status' => 200,
            'body' => $result,
        ], now()->addSeconds(3));

        return response()->json($result);
    }
}
