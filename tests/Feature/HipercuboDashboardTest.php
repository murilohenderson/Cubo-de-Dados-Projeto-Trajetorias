<?php

namespace Tests\Feature;

use App\Livewire\HipercuboDashboard;
use Livewire\Livewire;
use Tests\TestCase;

class HipercuboDashboardTest extends TestCase
{
    /**
     * Test that the Livewire component renders successfully on the home page.
     */
    public function test_component_renders_on_the_page(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSeeLivewire(HipercuboDashboard::class);
    }

    /**
     * Test that changing the active face of the 3D cube works.
     */
    public function test_setting_active_face_updates_defaults(): void
    {
        Livewire::test(HipercuboDashboard::class)
            ->assertSet('activeFace', 'front')
            ->call('setActiveFace', 'right')
            ->assertSet('activeFace', 'right')
            ->assertSet('selectedCell', null);
    }

    /**
     * Test that selecting a cell updates the state and dispatches the correct event.
     */
    public function test_select_cell_dispatches_events(): void
    {
        Livewire::test(HipercuboDashboard::class)
            ->call('selectCell', 'Cametá', 'Dengue')
            ->assertSet('selectedCell.territory', 'Cametá')
            ->assertSet('selectedCell.row_indicator', 'Dengue')
            ->assertDispatched('selected-cell-updated', function ($event, $params) {
                $data = $params[0] ?? [];
                return ($data['disease'] ?? null) === 'Dengue' &&
                       ($data['territory'] ?? null) === 'Cametá' &&
                       is_array($data['historical'] ?? null) &&
                       is_array($data['comparison'] ?? null);
            });
    }

    /**
     * Test that selecting first cell of risk works correctly.
     */
    public function test_select_first_cell_of_risk(): void
    {
        // Risk level 4 (Critical) selection
        Livewire::test(HipercuboDashboard::class)
            ->call('selectFirstCellOfRisk', 4)
            ->assertSet('selectedCell.risk_level', 4);
    }
}
