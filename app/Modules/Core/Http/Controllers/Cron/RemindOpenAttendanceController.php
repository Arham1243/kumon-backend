<?php

namespace App\Modules\Core\Http\Controllers\Cron;

use App\Http\Controllers\Controller;
use App\Modules\Core\Services\OpenAttendanceReminderService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class RemindOpenAttendanceController extends Controller
{
    public function __invoke(Request $request, OpenAttendanceReminderService $service): Response
    {
        $token = (string) $request->query('token', '');
        $expected = (string) config('services.attendance_cron.token', '');
        if ($expected === '' || ! hash_equals($expected, $token)) {
            abort(404);
        }

        $stats = $service->run();

        return response(
            'OK companies_messaged='.$stats['companies_messaged']
            .' emails_sent='.$stats['emails_sent']
            .' emails_failed='.$stats['emails_failed']
            .' companies_skipped='.$stats['companies_skipped'],
            200
        );
    }
}
