<?php
namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use App\Models\Cell; // Assuming there is a Cell model representing the data cube cells

class DashboardStatistics
{
    /**
     * Get monthly aggregated case counts (or other metrics) grouped by year-month.
     *
     * @param array $filters Optional filters such as ['activeFace' => 'inpe', 'region_id' => 5]
     * @return Collection
     */
    public function getMonthlyCounts(array $filters = []): Collection
    {
        return Cache::remember(
            $this->cacheKey('monthly', $filters),
            now()->addMinutes(30),
            function () use ($filters) {
                $query = Cell::query();
                // Apply optional filters
                if (!empty($filters['activeFace'])) {
                    $query->where('face', $filters['activeFace']);
                }
                if (!empty($filters['region_id'])) {
                    $query->where('region_id', $filters['region_id']);
                }
                // Assuming the cell has a "date" column (Y-m-d) and a "cases" integer column
                return $query->selectRaw(
                    "DATE_FORMAT(`date`, '%Y-%m') as period, SUM(cases) as total"
                )
                ->groupBy('period')
                ->orderBy('period')
                ->get()
                ->map(function ($row) {
                    return ['label' => $row->period, 'value' => (int) $row->total];
                });
            }
        );
    }

    /**
     * Get yearly aggregated case counts.
     *
     * @param array $filters
     * @return Collection
     */
    public function getYearlyCounts(array $filters = []): Collection
    {
        return Cache::remember(
            $this->cacheKey('yearly', $filters),
            now()->addMinutes(30),
            function () use ($filters) {
                $query = Cell::query();
                if (!empty($filters['activeFace'])) {
                    $query->where('face', $filters['activeFace']);
                }
                if (!empty($filters['region_id'])) {
                    $query->where('region_id', $filters['region_id']);
                }
                return $query->selectRaw(
                    "YEAR(`date`) as year, SUM(cases) as total"
                )
                ->groupBy('year')
                ->orderBy('year')
                ->get()
                ->map(function ($row) {
                    return ['label' => (string) $row->year, 'value' => (int) $row->total];
                });
            }
        );
    }

    /**
     * Build a cache key based on type and filters.
     */
    private function cacheKey(string $type, array $filters): string
    {
        return 'dashboard_stats:' . $type . ':' . md5(json_encode($filters));
    }
}
?>
