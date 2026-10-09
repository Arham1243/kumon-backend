<?php

namespace App\Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Http\Requests\PublicKioskPunchRequest;
use App\Modules\Core\Http\Resources\PublicKioskCompanyResource;
use App\Modules\Core\Models\Company;
use App\Modules\Core\Services\AttendanceKioskService;
use Illuminate\Http\JsonResponse;

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
}
