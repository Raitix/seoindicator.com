<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Scheduler\RankCheckScheduler;
use Illuminate\Http\JsonResponse;

class SchedulerController extends Controller
{
    public function runRankChecks(RankCheckScheduler $scheduler): JsonResponse
    {
        $result = $scheduler->run(true);

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }
}
