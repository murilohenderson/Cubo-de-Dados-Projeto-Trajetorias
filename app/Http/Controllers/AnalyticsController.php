<?php
namespace App\Http\Controllers;

use App\Services\DashboardStatistics;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AnalyticsController extends Controller
{
    protected $statsService;

    public function __construct(DashboardStatistics $statsService)
    {
        $this->statsService = $statsService;
    }

    /**
     * Return monthly aggregated data as JSON.
     */
    public function monthly(Request $request): JsonResponse
    {
        $filters = $request->only(['activeFace', 'region_id']);
        $data = $this->statsService->getMonthlyCounts($filters);
        return response()->json($data);
    }

    /**
     * Return yearly aggregated data as JSON.
     */
    public function yearly(Request $request): JsonResponse
    {
        $filters = $request->only(['activeFace', 'region_id']);
        $data = $this->statsService->getYearlyCounts($filters);
        return response()->json($data);
    }
}
?>
