<?php

namespace Tests\Feature;

use App\Livewire\HipercuboDashboard;
use Livewire\Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HipercuboDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\DataCubeSeeder::class);
    }

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

    /**
     * Test that the component populates dimensions/indicators from the database.
     * Guarantees dropdowns will have real content after seeding.
     */
    public function test_dimensions_are_loaded_from_database(): void
    {
        $component = Livewire::test(HipercuboDashboard::class);

        // Dimensions must have all 4 axes
        $component->assertSet('dimensions.ambiental.indicators', ['Focos de Calor', 'Incremento Desflorestamento']);
        $component->assertSet('dimensions.social.indicators', ['Populacao Total']);
        $component->assertSet('dimensions.economica.indicators', ['PIB per capita']);

        // Epidemiological axis must have 7 diseases
        $component->assertCount('dimensions.epidemiologica.indicators', 7);

        // Territories (municipalities) must be 3
        $component->assertCount('territories', 3);

        // selectedInd1 must be set to the first indicator of the front face's dim1 (ambiental)
        $component->assertSet('selectedInd1', 'Focos de Calor');
    }

    /**
     * Test that all 6 cube faces have unique dimension-pair labels.
     */
    public function test_cube_faces_are_unique(): void
    {
        $component = Livewire::test(HipercuboDashboard::class);
        $faceMappings = $component->get('faceMappings');

        $labels = array_column($faceMappings, 'label');
        $this->assertCount(6, array_unique($labels), 'All 6 cube faces must have unique labels');

        $pairs = array_map(fn($m) => $m['key1'] . '_' . $m['key2'], $faceMappings);
        $this->assertCount(6, array_unique($pairs), 'All 6 cube faces must have unique dimension pairs');
    }
}
