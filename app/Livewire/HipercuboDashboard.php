<?php

namespace App\Livewire;

use App\Models\Municipio;
use App\Models\Eixo;
use App\Models\Variavel;
use App\Models\DadoCubo;
use Livewire\Component;

class HipercuboDashboard extends Component
{
    /**
     * The active face of the 3D cube controller.
     * Options: 'front', 'back', 'left', 'right', 'top', 'bottom'
     */
    public string $activeFace = 'front';

    /**
     * Active face for the comparative cube (Cube B) in split mode.
     */
    public string $activeFaceB = 'right';

    /**
     * Whether comparative view mode is enabled.
     */
    public bool $modoComparativo = false;

    /**
     * Selected indicator from the first dimension of the active pair.
     */
    public string $selectedInd1 = '';

    /**
     * Selected indicator from the second dimension of the active pair.
     */
    public string $selectedInd2 = '';

    /**
     * Start date filter (mapped to database years).
     */
    public string $data_inicio = '2000-01-01';

    /**
     * End date filter (mapped to database years).
     */
    public string $data_fim = '2019-12-31';

    /**
     * Selected epidemiological zone filter: 'total', 'rural', 'urban'.
     */
    public string $zona = 'total';

    /**
     * Whether the drill-down modal is open.
     */
    public bool $isModalOpen = false;

    /**
     * Data package for the drill-down visualizer.
     */
    public array $drillDownData = [];

    /**
     * Variable selected in the temporal chart dropdown.
     */
    public string $selectedChartVariable = '';

    /**
     * Territory selected for variable correlation comparison analysis.
     */
    public string $selectedTerritory = 'Cametá';

    /**
     * The 4 relational dimensions of the Hypercube, populated dynamically from the database.
     */
    public array $dimensions = [];

    /**
     * Contextual territory axis: municipalities of Baixo Tocantins.
     */
    public array $territories = [];

    /**
     * Mapping of 3D cube faces to their corresponding primary dimension pair.
     * Updated to have 6 unique combinations using all 4 dimensions, eliminating repeated faces.
     */
    public array $faceMappings = [
        'front' => [
            'key1' => 'ambiental',
            'key2' => 'social',
            'label' => 'Ambiental × Social',
            'desc' => 'Como desmatamento e focos de calor aliados à dinâmica populacional e infraestrutura afetam as doenças vetoriais.'
        ],
        'right' => [
            'key1' => 'ambiental',
            'key2' => 'economica',
            'label' => 'Ambiental × Econômica',
            'desc' => 'Análise de como a pecuária, PIB e degradação florestal combinados alteram os vetores biológicos.'
        ],
        'bottom' => [
            'key1' => 'social',
            'key2' => 'economica',
            'label' => 'Social × Econômica',
            'desc' => 'Relação entre densidade demográfica, saneamento e PIB no desenvolvimento social e incidência de doenças.'
        ],
        'left' => [
            'key1' => 'ambiental',
            'key2' => 'epidemiologica',
            'label' => 'Ambiental × Epidemiológica',
            'desc' => 'Correlação direta entre focos de calor e desmatamento com o histórico local de doenças vetoriais.'
        ],
        'top' => [
            'key1' => 'social',
            'key2' => 'epidemiologica',
            'label' => 'Social × Epidemiológica',
            'desc' => 'Como a vulnerabilidade populacional e infraestrutura influenciam a taxa de infecções na região.'
        ],
        'back' => [
            'key1' => 'economica',
            'key2' => 'epidemiologica',
            'label' => 'Econômica × Epidemiológica',
            'desc' => 'Estudo da correlação entre PIB per capita e a proliferação de endemias no território.'
        ]
    ];

    /**
     * Component boot lifecycle hook. Runs on every request (mount and subsequent hydrations)
     * to guarantee that dimensions and territories are always populated.
     */
    public function boot()
    {
        $this->territories = Municipio::orderBy('nome')->pluck('nome')->toArray();
        $this->loadDimensionsFromDatabase();
    }

    /**
     * Component mount lifecycle hook. Runs once on initial load.
     */
    public function mount()
    {
        $this->updateActiveFaceDefaults();
    }

    /**
     * Dynamic loading of dimensions and indicators directly from DB.
     */
    public function loadDimensionsFromDatabase()
    {
        $eixos = Eixo::with('variaveis')->get();
        $shorts = [
            'ambiental'      => 'Clima/Solo (INPE)',
            'social'         => 'Demografia/Infraestrutura',
            'economica'      => 'Mercado/Produção',
            'epidemiologica' => 'Saúde (Fiocruz)'
        ];

        $this->dimensions = [];
        foreach ($eixos as $eixo) {
            $this->dimensions[$eixo->slug] = [
                'name'       => 'Dimensão ' . $eixo->nome,
                'short'      => $shorts[$eixo->slug] ?? $eixo->nome,
                'indicators' => $eixo->variaveis->pluck('nome')->toArray()
            ];
        }
    }

    /**
     * Sets the active cube face and updates selectors dynamically.
     */
    public function setActiveFace(string $face)
    {
        if (array_key_exists($face, $this->faceMappings)) {
            $this->activeFace = $face;
            $this->updateActiveFaceDefaults();
            $this->dispatch('update-map');
        }
    }

    /**
     * Sets the active cube face for Cube B (comparative) and triggers a refresh.
     */
    public function setActiveFaceB(string $face)
    {
        if (array_key_exists($face, $this->faceMappings)) {
            $this->activeFaceB = $face;
            $this->dispatch('update-map');
        }
    }

    /**
     * Toggles split comparative screen view.
     */
    public function toggleModoComparativo()
    {
        $this->modoComparativo = !$this->modoComparativo;
        $this->dispatch('update-map');
    }

    /**
     * Resets the active selectors to defaults when changing faces.
     */
    private function updateActiveFaceDefaults()
    {
        if (empty($this->dimensions)) {
            return;
        }

        $mapping = $this->faceMappings[$this->activeFace];
        $dim1    = $mapping['key1'];
        $dim2    = $mapping['key2'];

        $this->selectedInd1 = $this->dimensions[$dim1]['indicators'][0] ?? '';
        $this->selectedInd2 = $this->dimensions[$dim2]['indicators'][0] ?? '';

        // Reset chart variable to first available variable in the new face
        $allVars = array_merge(
            $this->dimensions[$dim1]['indicators'] ?? [],
            $this->dimensions[$dim2]['indicators'] ?? []
        );
        $this->selectedChartVariable = $allVars[0] ?? '';
    }

    /**
     * Helper to get currently active influencing indicators.
     */
    public function getActiveIndicators(): array
    {
        $mapping = $this->faceMappings[$this->activeFace];
        return [
            $mapping['key1'] => $this->selectedInd1,
            $mapping['key2'] => $this->selectedInd2
        ];
    }

    /**
     * Helper to parse years safely.
     */
    private function getYearFromDate(string $date, int $default): int
    {
        try {
            return (int)(new \DateTime($date))->format('Y');
        } catch (\Exception $e) {
            return $default;
        }
    }

    /**
     * Query real indicator values for display from the database.
     */
    public function getIndicatorValue(string $territory, string $indicator): string
    {
        $municipio = Municipio::where('nome', $territory)->first();
        $variavel  = Variavel::where('nome', $indicator)->first();
        if (!$municipio || !$variavel) {
            return 'N/D';
        }

        $startYear = $this->getYearFromDate($this->data_inicio, 2000);
        $endYear   = $this->getYearFromDate($this->data_fim, 2019);

        $dados = DadoCubo::where('municipio_id', $municipio->id)
            ->where('variavel_id', $variavel->id)
            ->get();

        $filtrados = $dados->filter(function ($d) use ($startYear, $endYear) {
            if (str_contains($d->ano_periodo, '-')) {
                [$y1, $y2] = explode('-', $d->ano_periodo);
                return (int)$y1 <= $endYear && (int)$y2 >= $startYear;
            }
            $y = (int)$d->ano_periodo;
            return $y >= $startYear && $y <= $endYear;
        });

        if ($filtrados->isEmpty()) {
            return 'Sem dados no período';
        }

        $ultimo   = $filtrados->sortByDesc('ano_periodo')->first();
        $unidade  = $variavel->unidade;

        if ($unidade === 'R$') {
            return 'R$ ' . number_format($ultimo->valor, 2, ',', '.');
        }

        return number_format($ultimo->valor, 1, ',', '.') . ' ' . $unidade;
    }

    /**
     * Query cases count and incidence rate for text displays.
     */
    public function getDiseaseCases(string $territory, string $disease): string
    {
        $municipio = Municipio::where('nome', $territory)->first();
        $variavel  = Variavel::where('nome', $disease)->first();
        if (!$municipio || !$variavel) {
            return '0 casos';
        }

        $startYear = $this->getYearFromDate($this->data_inicio, 2000);
        $endYear   = $this->getYearFromDate($this->data_fim, 2019);

        $dados = DadoCubo::where('municipio_id', $municipio->id)
            ->where('variavel_id', $variavel->id)
            ->get();

        $filtrados = $dados->filter(function ($d) use ($startYear, $endYear) {
            if ($d->detalhes && isset($d->detalhes['zona_residencial']) && $d->detalhes['zona_residencial'] !== $this->zona) {
                return false;
            }
            if (str_contains($d->ano_periodo, '-')) {
                [$y1, $y2] = explode('-', $d->ano_periodo);
                return (int)$y1 <= $endYear && (int)$y2 >= $startYear;
            }
            $y = (int)$d->ano_periodo;
            return $y >= $startYear && $y <= $endYear;
        });

        if ($filtrados->isEmpty()) {
            return '0 casos';
        }

        $somaCasos = (int)$filtrados->sum('valor');
        $taxaMedia = $filtrados->avg('taxa');

        if ($taxaMedia !== null) {
            return "{$somaCasos} casos (Incidência: " . number_format($taxaMedia, 2, ',', '.') . ")";
        }

        return "{$somaCasos} casos";
    }

    /**
     * Extrai valor bruto numérico de casos de doenças.
     */
    public function getDiseaseCasesNumeric(string $territory, string $disease): int
    {
        $municipio = Municipio::where('nome', $territory)->first();
        $variavel  = Variavel::where('nome', $disease)->first();
        if (!$municipio || !$variavel) {
            return 0;
        }

        $startYear = $this->getYearFromDate($this->data_inicio, 2000);
        $endYear   = $this->getYearFromDate($this->data_fim, 2019);

        $dados = DadoCubo::where('municipio_id', $municipio->id)
            ->where('variavel_id', $variavel->id)
            ->get();

        $filtrados = $dados->filter(function ($d) use ($startYear, $endYear) {
            if ($d->detalhes && isset($d->detalhes['zona_residencial']) && $d->detalhes['zona_residencial'] !== $this->zona) {
                return false;
            }
            if (str_contains($d->ano_periodo, '-')) {
                [$y1, $y2] = explode('-', $d->ano_periodo);
                return (int)$y1 <= $endYear && (int)$y2 >= $startYear;
            }
            $y = (int)$d->ano_periodo;
            return $y >= $startYear && $y <= $endYear;
        });

        return (int)$filtrados->sum('valor');
    }

    /**
     * Extrai o valor numérico médio de um indicador.
     */
    public function getIndicatorValueNumeric(string $territory, string $indicator): float
    {
        $municipio = Municipio::where('nome', $territory)->first();
        $variavel  = Variavel::where('nome', $indicator)->first();
        if (!$municipio || !$variavel) {
            return 0.0;
        }

        $startYear = $this->getYearFromDate($this->data_inicio, 2000);
        $endYear   = $this->getYearFromDate($this->data_fim, 2019);

        $dados = DadoCubo::where('municipio_id', $municipio->id)
            ->where('variavel_id', $variavel->id)
            ->get();

        $filtrados = $dados->filter(function ($d) use ($startYear, $endYear) {
            if (str_contains($d->ano_periodo, '-')) {
                [$y1, $y2] = explode('-', $d->ano_periodo);
                return (int)$y1 <= $endYear && (int)$y2 >= $startYear;
            }
            $y = (int)$d->ano_periodo;
            return $y >= $startYear && $y <= $endYear;
        });

        return (float)$filtrados->avg('valor');
    }

    /**
     * Generates real database dataset for drill-down Chart analysis.
     */
    public function getHistoricalData(string $territory, string $disease): array
    {
        $municipio = Municipio::where('nome', $territory)->first();
        $variavel  = Variavel::where('nome', $disease)->first();

        $series = [];
        if ($municipio && $variavel) {
            $dados = DadoCubo::where('municipio_id', $municipio->id)
                ->where('variavel_id', $variavel->id)
                ->orderBy('ano_periodo')
                ->get();

            foreach ($dados as $d) {
                // Filtrar por zona residencial ativa para manter a fidelidade nos detalhes
                if ($d->detalhes && isset($d->detalhes['zona_residencial']) && $d->detalhes['zona_residencial'] !== $this->zona) {
                    continue;
                }
                $series[] = [
                    'year'  => $d->ano_periodo,
                    'cases' => (int)$d->valor
                ];
            }
        }

        if (empty($series)) {
            $series = [
                ['year' => '2004-2008', 'cases' => 0],
                ['year' => '2015-2019', 'cases' => 0]
            ];
        }

        return [
            'territory' => $territory,
            'disease'   => $disease,
            'series'    => $series
        ];
    }

    /**
     * Returns table data for the currently active cube face.
     * Fetches variables from both face dimensions, one row per variable,
     * columns per territory (municipality).
     */
    public function getFaceVariablesTableData(): array
    {
        if (empty($this->dimensions)) {
            return [];
        }

        $mapping = $this->faceMappings[$this->activeFace];
        $dim1Key = $mapping['key1'];
        $dim2Key = $mapping['key2'];

        $dim1Indicators = $this->dimensions[$dim1Key]['indicators'] ?? [];
        $dim2Indicators = $this->dimensions[$dim2Key]['indicators'] ?? [];

        $startYear = $this->getYearFromDate($this->data_inicio, 2000);
        $endYear   = $this->getYearFromDate($this->data_fim, 2019);

        $rows = [];

        // Helper closure to build a row for a given variable name and dimension label
        $buildRow = function (string $varName, string $dimShort) use ($startYear, $endYear): array {
            $variavel = Variavel::where('nome', $varName)->first();
            $unidade  = $variavel?->unidade ?? '';
            $periodo  = $startYear === $endYear ? (string)$startYear : "{$startYear}–{$endYear}";

            $territoryCells = [];
            foreach ($this->territories as $territory) {
                $municipio = Municipio::where('nome', $territory)->first();
                if (!$municipio || !$variavel) {
                    $territoryCells[$territory] = ['display' => 'N/D', 'raw' => null];
                    continue;
                }

                $dados = DadoCubo::where('municipio_id', $municipio->id)
                    ->where('variavel_id', $variavel->id)
                    ->get();

                $filtrados = $dados->filter(function ($d) use ($startYear, $endYear) {
                    if ($d->detalhes && isset($d->detalhes['zona_residencial']) && $d->detalhes['zona_residencial'] !== $this->zona) {
                        return false;
                    }
                    if (str_contains($d->ano_periodo, '-')) {
                        [$y1, $y2] = explode('-', $d->ano_periodo);
                        return (int)$y1 <= $endYear && (int)$y2 >= $startYear;
                    }
                    $y = (int)$d->ano_periodo;
                    return $y >= $startYear && $y <= $endYear;
                });

                if ($filtrados->isEmpty()) {
                    $territoryCells[$territory] = ['display' => '—', 'raw' => null];
                    continue;
                }

                $ultimo = $filtrados->sortByDesc('ano_periodo')->first();
                $raw    = $ultimo->taxa ?? $ultimo->valor;

                $display = $unidade === 'R$'
                    ? 'R$ ' . number_format((float)$raw, 2, ',', '.')
                    : number_format((float)$raw, 1, ',', '.');

                $territoryCells[$territory] = ['display' => $display, 'raw' => (float)$raw];
            }

            return [
                'variavel'       => $varName,
                'dimensao'       => $dimShort,
                'unidade'        => $unidade,
                'periodo'        => $periodo,
                'territories'    => $territoryCells
            ];
        };

        $dim1Short = $this->dimensions[$dim1Key]['short'] ?? $dim1Key;
        $dim2Short = $this->dimensions[$dim2Key]['short'] ?? $dim2Key;

        foreach ($dim1Indicators as $ind) {
            $rows[] = $buildRow($ind, $dim1Short);
        }
        foreach ($dim2Indicators as $ind) {
            $rows[] = $buildRow($ind, $dim2Short);
        }

        return $rows;
    }

    /**
     * Returns temporal series data for all territories for a given variable.
     * Used by the simple line chart below the cross-variable table.
     * Returns: ['labels' => [...], 'datasets' => [['territory'=>..., 'data'=>[...]], ...]]
     */
    public function getTemporalChartData(string $varName = ''): array
    {
        if (empty($varName)) {
            $varName = $this->selectedChartVariable;
        }

        $variavel  = Variavel::where('nome', $varName)->first();
        $startYear = $this->getYearFromDate($this->data_inicio, 2000);
        $endYear   = $this->getYearFromDate($this->data_fim, 2019);

        if (!$variavel) {
            return ['labels' => [], 'datasets' => [], 'variavel' => $varName, 'unidade' => ''];
        }

        // Collect all relevant periods across all territories to build a unified label axis
        $allPeriods = [];
        $territoryData = [];

        foreach ($this->territories as $territory) {
            $municipio = Municipio::where('nome', $territory)->first();
            if (!$municipio) {
                $territoryData[$territory] = [];
                continue;
            }

            $dados = DadoCubo::where('municipio_id', $municipio->id)
                ->where('variavel_id', $variavel->id)
                ->orderBy('ano_periodo')
                ->get();

            $filtrados = $dados->filter(function ($d) use ($startYear, $endYear) {
                if ($d->detalhes && isset($d->detalhes['zona_residencial']) && $d->detalhes['zona_residencial'] !== $this->zona) {
                    return false;
                }
                if (str_contains($d->ano_periodo, '-')) {
                    [$y1, $y2] = explode('-', $d->ano_periodo);
                    return (int)$y1 <= $endYear && (int)$y2 >= $startYear;
                }
                $y = (int)$d->ano_periodo;
                return $y >= $startYear && $y <= $endYear;
            });

            $byPeriod = [];
            foreach ($filtrados as $d) {
                $val = $d->taxa ?? $d->valor;
                $byPeriod[$d->ano_periodo] = round((float)$val, 2);
                $allPeriods[$d->ano_periodo] = true;
            }
            $territoryData[$territory] = $byPeriod;
        }

        ksort($allPeriods);
        $labels = array_keys($allPeriods);

        $datasets = [];
        foreach ($this->territories as $territory) {
            $data = [];
            foreach ($labels as $label) {
                $data[] = $territoryData[$territory][$label] ?? null;
            }
            $datasets[] = [
                'territory' => $territory,
                'data'      => $data
            ];
        }

        return [
            'labels'   => $labels,
            'datasets' => $datasets,
            'variavel' => $varName,
            'unidade'  => $variavel->unidade ?? ''
        ];
    }

    /**
     * Compiles and opens the drill-down time-series modal.
     */
    public function abrirDrillDown(string $territory = '', string $varName = '')
    {
        $territory = $territory ?: ($this->territories[0] ?? '');
        $varName   = $varName ?: $this->selectedChartVariable;

        if ($territory && $varName) {
            $this->drillDownData = $this->getHistoricalData($territory, $varName);
            $this->isModalOpen   = true;
            $this->dispatch('open-drilldown-modal', $this->drillDownData);
        }
    }

    /**
     * Streamed Response implementation for cross-variable table CSV export.
     */
    public function exportarCSV()
    {
        $fileName = 'variaveis-cruzadas-' . now()->format('Y-m-d') . '.csv';

        $headers = [
            "Content-type"        => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function () {
            $file = fopen('php://output', 'w');

            // UTF-8 BOM
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            $header = [
                'Cruzamento (Face)',
                'Variável',
                'Dimensão',
                'Unidade',
                'Período',
                'Zona Residencial',
            ];
            foreach ($this->territories as $territory) {
                $header[] = $territory;
            }
            fputcsv($file, $header, ';');

            $mapping  = $this->faceMappings[$this->activeFace];
            $tableData = $this->getFaceVariablesTableData();

            foreach ($tableData as $row) {
                $line = [
                    $mapping['label'],
                    $row['variavel'],
                    $row['dimensao'],
                    $row['unidade'],
                    $row['periodo'],
                    strtoupper($this->zona),
                ];
                foreach ($this->territories as $territory) {
                    $line[] = $row['territories'][$territory]['display'] ?? '—';
                }
                fputcsv($file, $line, ';');
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Life cycle hooks for dropdown filter changes.
     */
    public function updatedSelectedInd1($value)
    {
        $this->dispatch('update-map');
    }

    public function updatedSelectedInd2($value)
    {
        $this->dispatch('update-map');
    }

    public function updatedDataInicio()
    {
        $this->dispatch('update-map');
    }

    public function updatedDataFim()
    {
        $this->dispatch('update-map');
    }

    public function updatedZona()
    {
        $this->dispatch('update-map');
    }

    public function updatedSelectedChartVariable()
    {
        $this->dispatch('update-map');
    }

    public function updatedSelectedTerritory()
    {
        $this->dispatch('update-map');
    }

    /**
     * Query data to relate two indicators (dim1 and dim2 of active face) for a selected territory.
     * Generates datasets for dual Y-axis line chart and scatter plot.
     */
    public function getComparisonChartData(): array
    {
        $municipio = Municipio::where('nome', $this->selectedTerritory)->first();
        $var1 = Variavel::where('nome', $this->selectedInd1)->first();
        $var2 = Variavel::where('nome', $this->selectedInd2)->first();

        if (!$municipio || !$var1 || !$var2) {
            return [
                'labels' => [],
                'values1' => [],
                'values2' => [],
                'scatter' => [],
                'var1_name' => $this->selectedInd1 ?: 'Variável 1',
                'var2_name' => $this->selectedInd2 ?: 'Variável 2',
                'var1_unit' => '',
                'var2_unit' => '',
                'territory' => $this->selectedTerritory
            ];
        }

        $startYear = $this->getYearFromDate($this->data_inicio, 2000);
        $endYear   = $this->getYearFromDate($this->data_fim, 2019);

        // Fetch variable 1 data
        $dados1 = DadoCubo::where('municipio_id', $municipio->id)
            ->where('variavel_id', $var1->id)
            ->get()
            ->filter(function ($d) use ($startYear, $endYear) {
                if (str_contains($d->ano_periodo, '-')) {
                    [$y1, $y2] = explode('-', $d->ano_periodo);
                    return (int)$y1 <= $endYear && (int)$y2 >= $startYear;
                }
                $y = (int)$d->ano_periodo;
                return $y >= $startYear && $y <= $endYear;
            })
            ->sortBy('ano_periodo');

        // Fetch variable 2 data
        $dados2 = DadoCubo::where('municipio_id', $municipio->id)
            ->where('variavel_id', $var2->id)
            ->get()
            ->filter(function ($d) use ($startYear, $endYear) {
                if (str_contains($d->ano_periodo, '-')) {
                    [$y1, $y2] = explode('-', $d->ano_periodo);
                    return (int)$y1 <= $endYear && (int)$y2 >= $startYear;
                }
                $y = (int)$d->ano_periodo;
                return $y >= $startYear && $y <= $endYear;
            })
            ->sortBy('ano_periodo');

        // Map values by period
        $map1 = [];
        foreach ($dados1 as $d) {
            $val = $d->taxa ?? $d->valor;
            $map1[$d->ano_periodo] = (float)$val;
        }

        $map2 = [];
        foreach ($dados2 as $d) {
            $val = $d->taxa ?? $d->valor;
            $map2[$d->ano_periodo] = (float)$val;
        }

        $allPeriods = array_unique(array_merge(array_keys($map1), array_keys($map2)));
        sort($allPeriods);

        $labels = [];
        $values1 = [];
        $values2 = [];
        $scatter = [];

        foreach ($allPeriods as $period) {
            $val1 = $map1[$period] ?? null;
            $val2 = $map2[$period] ?? null;

            $labels[] = $period;
            $values1[] = $val1;
            $values2[] = $val2;

            if ($val1 !== null && $val2 !== null) {
                $scatter[] = [
                    'x' => $val1,
                    'y' => $val2,
                    'label' => $period
                ];
            }
        }

        return [
            'labels' => $labels,
            'values1' => $values1,
            'values2' => $values2,
            'scatter' => $scatter,
            'var1_name' => $var1->nome,
            'var2_name' => $var2->nome,
            'var1_unit' => $var1->unidade ?? '',
            'var2_unit' => $var2->unidade ?? '',
            'territory' => $this->selectedTerritory
        ];
    }

    /**
     * Render the Livewire component view.
     */
    public function render()
    {
        $mapping  = $this->faceMappings[$this->activeFace] ?? [];
        $mappingB = $this->faceMappings[$this->activeFaceB] ?? [];

        $dim1Key = $mapping['key1'] ?? '';
        $dim2Key = $mapping['key2'] ?? '';

        $dim1Indicators = $this->dimensions[$dim1Key]['indicators'] ?? [];
        $dim2Indicators = $this->dimensions[$dim2Key]['indicators'] ?? [];

        // All variables in the active face for the chart dropdown
        $allFaceVariables = array_merge($dim1Indicators, $dim2Indicators);

        // Ensure selectedChartVariable is set
        if (empty($this->selectedChartVariable) && !empty($allFaceVariables)) {
            $this->selectedChartVariable = $allFaceVariables[0];
        }

        $tableData    = $this->getFaceVariablesTableData();
        $temporalData = $this->getTemporalChartData();
        $comparisonChartData = $this->getComparisonChartData();

        return view('livewire.hipercubo-dashboard', [
            'dim1_key'   => $dim1Key,
            'dim2_key'   => $dim2Key,
            'dim1_label' => $this->dimensions[$dim1Key]['short'] ?? '',
            'dim2_label' => $this->dimensions[$dim2Key]['short'] ?? '',
            'dim1_indicators' => $dim1Indicators,
            'dim2_indicators' => $dim2Indicators,

            // Cube B fields
            'dim1_key_b'   => $mappingB['key1'] ?? '',
            'dim2_key_b'   => $mappingB['key2'] ?? '',
            'dim1_label_b' => $this->dimensions[$mappingB['key1'] ?? '']['short'] ?? '',
            'dim2_label_b' => $this->dimensions[$mappingB['key2'] ?? '']['short'] ?? '',

            'activeMapping'    => $mapping,
            'activeMappingB'   => $mappingB,
            'tableData'        => $tableData,
            'temporalData'     => $temporalData,
            'comparisonChartData' => $comparisonChartData,
            'allFaceVariables' => $allFaceVariables,
        ]);
    }
}
