<?php

namespace App\Modules\Core\Http\Controllers\Cron;

use App\Http\Controllers\Controller;
use App\Modules\Core\Services\AutoCloseOpenAttendanceService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AutoCloseOpenAttendanceController extends Controller
{
    public function __invoke(Request $request, AutoCloseOpenAttendanceService $service): Response
    {
        $token = (string) $request->query('token', '');
        $expected = (string) config('services.attendance_cron.token', '');
        if ($expected === '' || ! hash_equals($expected, $token)) {
            abort(404);
        }

        $stats = $service->run();

        return response(
            'OK closed='.$stats['closed']
            .' skipped='.$stats['skipped']
            .' companies='.$stats['companies'],
            200
        );
    }
}
