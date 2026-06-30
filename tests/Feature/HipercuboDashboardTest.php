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
            ->assertSet('selectedChartVariable', 'Anomalia de Precipitação Negativa');
    }

    /**
     * Test that opening drill-down dispatches the correct event and updates the modal state.
     */
    public function test_abrir_drilldown_dispatches_events(): void
    {
        Livewire::test(HipercuboDashboard::class)
            ->call('abrirDrillDown', 'Cametá', 'Dengue')
            ->assertSet('isModalOpen', true)
            ->assertSet('drillDownData.territory', 'Cametá')
            ->assertSet('drillDownData.disease', 'Dengue')
            ->assertDispatched('open-drilldown-modal', function ($event, $params) {
                $data = $params[0] ?? [];
                return ($data['disease'] ?? null) === 'Dengue' &&
                       ($data['territory'] ?? null) === 'Cametá' &&
                       is_array($data['series'] ?? null);
            });
    }

    /**
     * Test that cross-variable table data is fetched correctly based on current face.
     */
    public function test_get_face_variables_table_data(): void
    {
        $component = Livewire::test(HipercuboDashboard::class);
        
        $tableData = $component->instance()->getFaceVariablesTableData();
        
        $this->assertIsArray($tableData);
        $this->assertNotEmpty($tableData);
        
        $firstRow = $tableData[0];
        $this->assertArrayHasKey('variavel', $firstRow);
        $this->assertArrayHasKey('dimensao', $firstRow);
        $this->assertArrayHasKey('unidade', $firstRow);
        $this->assertArrayHasKey('periodo', $firstRow);
        $this->assertArrayHasKey('territories', $firstRow);
        
        // Should contain territory columns
        $this->assertArrayHasKey('Baião', $firstRow['territories']);
        $this->assertArrayHasKey('Cametá', $firstRow['territories']);
        $this->assertArrayHasKey('Mocajuba', $firstRow['territories']);
    }

    /**
     * Test that temporal chart data is generated correctly.
     */
    public function test_get_temporal_chart_data(): void
    {
        $component = Livewire::test(HipercuboDashboard::class);
        
        $chartData = $component->instance()->getTemporalChartData('Dengue');
        
        $this->assertIsArray($chartData);
        $this->assertArrayHasKey('labels', $chartData);
        $this->assertArrayHasKey('datasets', $chartData);
        $this->assertArrayHasKey('variavel', $chartData);
        $this->assertArrayHasKey('unidade', $chartData);
        
        $this->assertEquals('Dengue', $chartData['variavel']);
        $this->assertCount(3, $chartData['datasets']); // one dataset per territory
    }

    /**
     * Test that the component populates dimensions/indicators from the database.
     * Guarantees dropdowns will have real content after seeding.
     */
    public function test_dimensions_are_loaded_from_database(): void
    {
        $component = Livewire::test(HipercuboDashboard::class);

        // Dimensions must have all 4 axes and contain the required indicators
        $component->assertSet('dimensions.ambiental.indicators', function ($indicators) {
            return is_array($indicators) && 
                   in_array('Focos de Calor', $indicators) && 
                   in_array('Incremento Desflorestamento', $indicators);
        });

        $component->assertSet('dimensions.social.indicators', function ($indicators) {
            return is_array($indicators) && in_array('Populacao Total', $indicators);
        });

        $component->assertSet('dimensions.economica.indicators', function ($indicators) {
            return is_array($indicators) && in_array('PIB per capita', $indicators);
        });

        // Epidemiological axis must have 7 diseases
        $component->assertCount('dimensions.epidemiologica.indicators', 7);

        // Territories (municipalities) must be 3
        $component->assertCount('territories', 3);

        // selectedInd1 must be set to the first indicator of the front face's dim1 (ambiental)
        $component->assertSet('selectedInd1', 'Anomalia de Precipitação Negativa');
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
