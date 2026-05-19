<?php

namespace App\Livewire;

use App\Models\Category;
use App\Models\Indicator;
use App\Models\Region;
use App\Models\MatrixCell;
use Livewire\Component;

class DataCubeDashboard extends Component
{
    /**
     * The active face/dimension filter for the 3D cube.
     * Can be 'all', 'inpe', or 'fiocruz'.
     *
     * @var string
     */
    public string $activeFace = 'all';

    /**
     * The ID of the currently selected matrix cell.
     *
     * @var int|null
     */
    public ?int $selectedCellId = null;

    /**
     * Details of the selected matrix cell including indicator and region names,
     * density level, and the scientific correlation text.
     *
     * @var array|null
     */
    public ?array $cellDetails = null;

    /**
     * Set the active face for filtering.
     *
     * @param string $face
     * @return void
     */
    public function setActiveFace(string $face): void
    {
        $validFaces = ['all', 'inpe', 'fiocruz', 'territorio'];
        if (in_array($face, $validFaces)) {
            // Map 'territorio' to 'all' to show both INPE and Fiocruz contextually
            $this->activeFace = $face === 'territorio' ? 'all' : $face;
        }
    }

    /**
     * Select a specific cell inside the matrix using its indicator and region IDs.
     *
     * @param int $indicatorId
     * @param int $regionId
     * @return void
     */
    public function selectCell(int $indicatorId, int $regionId): void
    {
        $cell = MatrixCell::with(['indicator.category', 'region'])
            ->where('indicator_id', $indicatorId)
            ->where('region_id', $regionId)
            ->first();

        if ($cell) {
            $this->selectedCellId = $cell->id;
            $this->cellDetails = [
                'id' => $cell->id,
                'indicator_name' => $cell->indicator->name,
                'category_name' => $cell->indicator->category->name,
                'region_name' => $cell->region->name,
                'density_level' => $cell->density_level,
                'correlation_text' => $cell->correlation_text,
                'updated_at' => $cell->updated_at ? $cell->updated_at->format('d/m/Y H:i') : null,
            ];
        } else {
            $this->selectedCellId = null;
            $this->cellDetails = null;
        }
    }

    /**
     * Render the Livewire view.
     *
     * @return \Illuminate\View\View
     */
    public function render()
    {
        $categoriesQuery = Category::with('indicators');

        if ($this->activeFace === 'inpe') {
            $categoriesQuery->where('name', 'like', '%INPE%');
        } elseif ($this->activeFace === 'fiocruz') {
            $categoriesQuery->where('name', 'like', '%Fiocruz%');
        }

        $categories = $categoriesQuery->get();
        $indicatorIds = $categories->pluck('indicators')->flatten()->pluck('id');
        $regions = Region::all();

        $cells = MatrixCell::whereIn('indicator_id', $indicatorIds)
            ->get()
            ->groupBy(function ($cell) {
                return $cell->indicator_id . '-' . $cell->region_id;
            });

        return view('livewire.data-cube-dashboard', [
            'categories' => $categories,
            'regions' => $regions,
            'cells' => $cells,
        ]);
    }
}
