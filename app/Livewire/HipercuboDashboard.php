<?php

namespace App\Livewire;

use Livewire\Component;

class HipercuboDashboard extends Component
{
    /**
     * The active face of the 3D cube controller, representing a "Pair of Forces".
     * Options: 'front', 'back', 'left', 'right', 'top', 'bottom'
     *
     * @var string
     */
    public string $activeFace = 'front';

    /**
     * Active face for the comparative cube (Cube B) in split mode.
     *
     * @var string
     */
    public string $activeFaceB = 'right';

    /**
     * Whether comparative view mode is enabled.
     *
     * @var bool
     */
    public bool $modoComparativo = false;

    /**
     * Selected indicator from the first dimension of the active pair.
     *
     * @var string
     */
    public string $selectedInd1 = '';

    /**
     * Selected indicator from the second dimension of the active pair.
     *
     * @var string
     */
    public string $selectedInd2 = '';

    /**
     * Start date filter.
     *
     * @var string
     */
    public string $data_inicio = '2025-01-01';

    /**
     * End date filter.
     *
     * @var string
     */
    public string $data_fim = '2025-12-31';

    /**
     * Whether the drill-down modal is open.
     *
     * @var bool
     */
    public bool $isModalOpen = false;

    /**
     * Data package for the drill-down visualizer.
     *
     * @var array
     */
    public array $drillDownData = [];

    /**
     * Details of the selected matrix cell for the scientific evidence panel.
     *
     * @var array|null
     */
    public ?array $selectedCell = null;

    /**
     * The 4 relational dimensions of the Hypercube.
     *
     * @var array
     */
    public array $dimensions = [
        'epidemiologica' => [
            'name' => 'Dimensão Epidemiológica',
            'short' => 'Saúde (Fiocruz)',
            'indicators' => ['Dengue', 'Doença de Chagas', 'Leishmaniose', 'Malária']
        ],
        'ambiental' => [
            'name' => 'Dimensão Ambiental',
            'short' => 'Clima/Solo (INPE)',
            'indicators' => [
                'Desmatamento por Corte Raso (PRODES)',
                'Focos de Calor (Queimadas)',
                'Degradação Florestal Crônica',
                'Conversão para Pastagem'
            ]
        ],
        'economica' => [
            'name' => 'Dimensão Econômica',
            'short' => 'Mercado/Produção',
            'indicators' => ['PIB do Agronegócio Municipal', 'Produção de Ouro/Garimpo', 'Crédito Agrícola Cedido']
        ],
        'social' => [
            'name' => 'Dimensão Social',
            'short' => 'Demografia/Infraestrutura',
            'indicators' => ['Índice de Vulnerabilidade Social (IVS)', 'Acesso a Saneamento Básico', 'Densidade Populacional Rural']
        ]
    ];

    /**
     * Contextual territory axis: 3 municipalities of Baixo Tocantins.
     *
     * @var array
     */
    public array $territories = [
        'Baião',
        'Cametá',
        'Mocajuba'
    ];

    /**
     * Mapping of 3D cube faces to their corresponding primary dimension pair (forces),
     * updated for the Baixo Tocantins study.
     *
     * @var array
     */
    public array $faceMappings = [
        'front' => [
            'key1' => 'ambiental',
            'key2' => 'social',
            'label' => 'Ambiental × Social',
            'desc' => 'Como desmatamento, focos de calor e perda de dossel aliados a vulnerabilidade social e saneamento básico afetam as doenças vetoriais.'
        ],
        'right' => [
            'key1' => 'ambiental',
            'key2' => 'economica',
            'label' => 'Ambiental × Econômica',
            'desc' => 'Análise de como a pecuária, garimpo e PIB agropecuário combinados à degradação ambiental alteram vetores biológicos.'
        ],
        'bottom' => [
            'key1' => 'social',
            'key2' => 'economica',
            'label' => 'Social × Econômica',
            'desc' => 'Relação entre saneamento básico, vulnerabilidade (IVS) e atratividade financeira com a incidência local de doenças.'
        ],
        'left' => [
            'key1' => 'social',
            'key2' => 'ambiental',
            'label' => 'Social × Ambiental',
            'desc' => 'Como a vulnerabilidade demográfica rural reage sob pressões de degradação da cobertura vegetal e focos de calor.'
        ],
        'top' => [
            'key1' => 'economica',
            'key2' => 'ambiental',
            'label' => 'Econômica × Ambiental',
            'desc' => 'Como incentivos financeiros de crédito e produção agropecuária aceleram o desmatamento e alteram o ecótono regional.'
        ],
        'back' => [
            'key1' => 'economica',
            'key2' => 'social',
            'label' => 'Econômica × Social',
            'desc' => 'Estudo da distribuição de crédito agrícola e PIB no desenvolvimento social e saneamento municipal.'
        ]
    ];

    /**
     * Component mount lifecycle hook.
     */
    public function mount()
    {
        $this->updateActiveFaceDefaults();
    }

    /**
     * Sets the active cube face and updates selectors dynamically.
     *
     * @param string $face
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
     *
     * @param string $face
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
        $mapping = $this->faceMappings[$this->activeFace];
        $dim1 = $mapping['key1'];
        $dim2 = $mapping['key2'];

        $this->selectedInd1 = $this->dimensions[$dim1]['indicators'][0];
        $this->selectedInd2 = $this->dimensions[$dim2]['indicators'][0];
        
        $this->selectedCell = null;
    }

    /**
     * Helper to get currently active influencing indicators.
     *
     * @return array
     */
    public function getActiveIndicators(): array
    {
        $mapping = $this->faceMappings[$this->activeFace];
        $key1 = $mapping['key1'];
        $key2 = $mapping['key2'];

        $indicators = [];
        $indicators[$key1] = $this->selectedInd1;
        $indicators[$key2] = $this->selectedInd2;

        return $indicators;
    }

    /**
     * Deterministically calculates the risk level for a given disease and territory.
     * Takes date limits into account to show variability under filter changes.
     *
     * @param string $territory
     * @param string $disease
     * @return int
     */
    public function getRiskLevel(string $territory, string $disease): int
    {
        $hashString = $territory . $disease . $this->selectedInd1 . $this->selectedInd2 . $this->data_inicio . $this->data_fim;
        $hash = crc32($hashString);
        return abs($hash % 4) + 1; // 1 to 4
    }

    /**
     * Faked database/helper to return realistic statistics for the selected indicators.
     *
     * @param string $territory
     * @param string $indicator
     * @return string
     */
    public function getIndicatorValue(string $territory, string $indicator): string
    {
        $ind = mb_strtolower($indicator);
        
        if (str_contains($ind, 'corte raso')) {
            if ($territory === 'Cametá') return '180 hectares desmatados (PRODES)';
            if ($territory === 'Mocajuba') return '95 hectares desmatados (PRODES)';
            if ($territory === 'Baião') return '320 hectares desmatados (PRODES)';
        }
        if (str_contains($ind, 'focos de calor')) {
            if ($territory === 'Cametá') return '85 focos ativos (INPE BDQueimadas)';
            if ($territory === 'Mocajuba') return '45 focos ativos (INPE BDQueimadas)';
            if ($territory === 'Baião') return '190 focos ativos (INPE BDQueimadas)';
        }
        if (str_contains($ind, 'degradação florestal')) {
            if ($territory === 'Cametá') return '2,1% de cobertura municipal (DEGRAD)';
            if ($territory === 'Mocajuba') return '1,5% de cobertura municipal (DEGRAD)';
            if ($territory === 'Baião') return '4,8% de cobertura municipal (DEGRAD)';
        }
        if (str_contains($ind, 'conversão para pastagem')) {
            if ($territory === 'Cametá') return '1.200 hectares acumulados (MapBiomas)';
            if ($territory === 'Mocajuba') return '750 hectares acumulados (MapBiomas)';
            if ($territory === 'Baião') return '4.500 hectares acumulados (MapBiomas)';
        }
        
        if (str_contains($ind, 'vulnerabilidade social')) {
            if ($territory === 'Cametá') return '0,385 - Alta (IPEA IVS)';
            if ($territory === 'Mocajuba') return '0,390 - Alta (IPEA IVS)';
            if ($territory === 'Baião') return '0,410 - Muito Alta (IPEA IVS)';
        }
        if (str_contains($ind, 'saneamento')) {
            if ($territory === 'Cametá') return '14,2% de saneamento coletivo (SNIS)';
            if ($territory === 'Mocajuba') return '9,8% de saneamento coletivo (SNIS)';
            if ($territory === 'Baião') return '6,5% de saneamento coletivo (SNIS)';
        }
        if (str_contains($ind, 'densidade populacional rural')) {
            if ($territory === 'Cametá') return '18,5 hab/km² em zona rural (IBGE)';
            if ($territory === 'Mocajuba') return '11,2 hab/km² em zona rural (IBGE)';
            if ($territory === 'Baião') return '8,4 hab/km² em zona rural (IBGE)';
        }
        
        if (str_contains($ind, 'pib do agronegócio')) {
            if ($territory === 'Cametá') return 'R$ 48,5M anuais (IBGE Pam)';
            if ($territory === 'Mocajuba') return 'R$ 18,2M anuais (IBGE Pam)';
            if ($territory === 'Baião') return 'R$ 64,1M anuais (IBGE Pam)';
        }
        if (str_contains($ind, 'ouro') || str_contains($ind, 'garimpo')) {
            if ($territory === 'Cametá') return 'Sem mineração ativa registrada';
            if ($territory === 'Mocajuba') return 'R$ 1,2M (Lavra aluvial)';
            if ($territory === 'Baião') return 'R$ 8,9M (Extração fluvial)';
        }
        if (str_contains($ind, 'crédito agrícola')) {
            if ($territory === 'Cametá') return 'R$ 12,4M outorgados (BCB)';
            if ($territory === 'Mocajuba') return 'R$ 4,8M outorgados (BCB)';
            if ($territory === 'Baião') return 'R$ 22,1M outorgados (BCB)';
        }
        
        return 'Dados não registrados no intervalo';
    }

    /**
     * Faked database to return disease cases statistics.
     *
     * @param string $territory
     * @param string $disease
     * @return string
     */
    public function getDiseaseCases(string $territory, string $disease): string
    {
        $d = mb_strtolower($disease);
        if (str_contains($d, 'dengue')) {
            if ($territory === 'Cametá') return '420 casos (Sinan)';
            if ($territory === 'Mocajuba') return '85 casos (Sinan)';
            if ($territory === 'Baião') return '110 casos (Sinan)';
        }
        if (str_contains($d, 'malária') || str_contains($d, 'malaria')) {
            if ($territory === 'Cametá') return '28 casos (Sivep)';
            if ($territory === 'Mocajuba') return '4 casos (Sivep)';
            if ($territory === 'Baião') return '75 casos (Sivep)';
        }
        if (str_contains($d, 'leishmaniose')) {
            if ($territory === 'Cametá') return '45 casos (Sinan)';
            if ($territory === 'Mocajuba') return '15 casos (Sinan)';
            if ($territory === 'Baião') return '92 casos (Sinan)';
        }
        if (str_contains($d, 'chagas')) {
            if ($territory === 'Cametá') return '12 casos (SVA surto oral)';
            if ($territory === 'Mocajuba') return '3 casos (SVA)';
            if ($territory === 'Baião') return '5 casos (SVA)';
        }
        return '0 casos cadastrados';
    }

    /**
     * Generates simulated multi-year dataset for drill-down Chart analysis.
     *
     * @param string $territory
     * @param string $disease
     * @return array
     */
    public function getHistoricalData(string $territory, string $disease): array
    {
        $years = ['2020', '2021', '2022', '2023', '2024', '2025'];
        $seed = crc32($territory . $disease . $this->data_inicio . $this->data_fim);
        $series = [];
        
        $base = abs($seed % 80) + 15;
        foreach ($years as $index => $year) {
            $fluctuation = sin($index + $seed) * ($base * 0.25) + (($index * 0.1) * $base);
            $value = max(0, (int)round($base + $fluctuation));
            $series[] = [
                'year' => $year,
                'cases' => $value
            ];
        }

        return [
            'territory' => $territory,
            'disease' => $disease,
            'series' => $series
        ];
    }

    /**
     * Selects a specific cell inside the matrix and opens drilldown modal.
     *
     * @param string $territory
     * @param string $rowIndicator
     */
    public function selectCell(string $territory, string $rowIndicator)
    {
        $mapping = $this->faceMappings[$this->activeFace];
        $riskLevel = $this->getRiskLevel($territory, $rowIndicator);
        $evidenceText = $this->generateEvidence($territory, $rowIndicator, $riskLevel);

        $ind1_val = $this->getIndicatorValue($territory, $this->selectedInd1);
        $ind2_val = $this->getIndicatorValue($territory, $this->selectedInd2);
        $disease_val = $this->getDiseaseCases($territory, $rowIndicator);

        $this->selectedCell = [
            'territory' => $territory,
            'row_indicator' => $rowIndicator,
            'risk_level' => $riskLevel,
            'evidence_text' => $evidenceText,
            'indicator_1' => $this->selectedInd1,
            'indicator_2' => $this->selectedInd2,
            'ind1_val' => $ind1_val,
            'ind2_val' => $ind2_val,
            'disease_val' => $disease_val,
            'dim_1_label' => $this->dimensions[$mapping['key1']]['short'],
            'dim_2_label' => $this->dimensions[$mapping['key2']]['short'],
            'timestamp' => now()->format('H:i:s')
        ];

        $this->dispatch('update-map');
    }

    /**
     * Compiles and opens the drill-down time-series modal for the currently selected cell.
     */
    public function abrirDrillDown()
    {
        if ($this->selectedCell) {
            $territory = $this->selectedCell['territory'];
            $rowIndicator = $this->selectedCell['row_indicator'];
            
            $this->drillDownData = $this->getHistoricalData($territory, $rowIndicator);
            $this->isModalOpen = true;

            $this->dispatch('open-drilldown-modal', $this->drillDownData);
        }
    }

    /**
     * Streamed Response implementation for performant high-volume CSV exports without server overhead.
     */
    public function exportarCSV()
    {
        $fileName = 'matriz-correlacao-' . now()->format('Y-m-d') . '.csv';

        $headers = [
            "Content-type"        => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function() {
            $file = fopen('php://output', 'w');
            
            // UTF-8 BOM
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // CSV Header Row
            fputcsv($file, [
                'Cruzamento Hipercubo',
                'Filtro Indicador 1',
                'Valor Indicador 1',
                'Filtro Indicador 2',
                'Valor Indicador 2',
                'Período Início',
                'Período Fim',
                'Município (Território)',
                'Doença Vetorial Incidência',
                'Casos Locais',
                'Nível de Risco Calculado'
            ], ';');

            $mapping = $this->faceMappings[$this->activeFace];
            
            // Loop through all data combinations to export
            foreach ($this->dimensions['epidemiologica']['indicators'] as $disease) {
                foreach ($this->territories as $territory) {
                    $risk = $this->getRiskLevel($territory, $disease);
                    $cases = $this->getDiseaseCases($territory, $disease);
                    $val1 = $this->getIndicatorValue($territory, $this->selectedInd1);
                    $val2 = $this->getIndicatorValue($territory, $this->selectedInd2);

                    fputcsv($file, [
                        $mapping['label'],
                        $this->selectedInd1,
                        $val1,
                        $this->selectedInd2,
                        $val2,
                        $this->data_inicio,
                        $this->data_fim,
                        $territory,
                        $disease,
                        $cases,
                        $risk
                    ], ';');
                }
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Life cycle hook for real-time updates when SelectedInd1 changes.
     */
    public function updatedSelectedInd1($value)
    {
        $this->syncSelectedCellFromDropdowns();
    }

    /**
     * Life cycle hook for real-time updates when SelectedInd2 changes.
     */
    public function updatedSelectedInd2($value)
    {
        $this->syncSelectedCellFromDropdowns();
    }

    /**
     * Sincroniza dados com filtros de períodos
     */
    public function updatedDataInicio()
    {
        $this->syncSelectedCellFromDropdowns();
    }

    public function updatedDataFim()
    {
        $this->syncSelectedCellFromDropdowns();
    }

    /**
     * Synchronizes selected cell when dropdown filters change.
     */
    private function syncSelectedCellFromDropdowns()
    {
        if ($this->selectedCell) {
            $this->selectCell($this->selectedCell['territory'], $this->selectedCell['row_indicator']);
        } else {
            $this->dispatch('update-map');
        }
    }

    /**
     * Helper to generate realistic scientific synthesis for Baixo Tocantins context.
     */
    private function generateEvidence(string $territory, string $disease, int $riskLevel): string
    {
        $ind1 = $this->selectedInd1;
        $ind2 = $this->selectedInd2;
        
        $ind1Val = $this->getIndicatorValue($territory, $ind1);
        $ind2Val = $this->getIndicatorValue($territory, $ind2);
        $diseaseVal = $this->getDiseaseCases($territory, $disease);

        $description = "A modelagem espacial multicritério em {$territory} indica que a proximidade de focos de alteração antrópica ('{$ind1}') associada a vulnerabilidades locais de infraestrutura ('{$ind2}') cria um ecótono propício para a proliferação vetorial. ";

        if (str_contains(mb_strtolower($ind1), 'corte raso') && str_contains(mb_strtolower($ind2), 'saneamento')) {
            $description = "O desmatamento por corte raso de matas secundárias altera o microclima, gerando bolsões térmicos e removendo barreiras naturais de proteção contra os mosquitos. Aliado à precariedade de saneamento básico (apenas {$ind2Val}), que gera descarte inadequado de águas e acúmulo de resíduos no peridomicílio, cria-se o cenário perfeito para o ciclo de transmissão rápida da doença {$disease}.";
        } elseif (str_contains(mb_strtolower($ind1), 'focos') && str_contains(mb_strtolower($ind2), 'vulnerabilidade')) {
            $description = "A fumaça e a poluição decorrentes de queimadas agrícolas provocam estresse cardiorrespiratório na população rústica. Em áreas com elevado índice de vulnerabilidade social (estimado em {$ind2Val}), a falta de assistência primária e moradias de várzea/degradadas impede o bloqueio adequado de vetores, agravando a distribuição regional de {$disease}.";
        } elseif (str_contains(mb_strtolower($ind1), 'degradação') && str_contains(mb_strtolower($ind2), 'rural')) {
            $description = "A degradação florestal crônica na periferia municipal aproxima vetores silvestres (como flebotomíneos) das residências rurais. Com uma densidade populacional rural de {$ind2Val}, o contato de extrativistas familiares com frentes silvestres é constante, acelerando a incidência de {$disease}.";
        }

        switch ($riskLevel) {
            case 4:
                return "NEXO CAUSAL CRÍTICO: Em {$territory}, a sinergia ecológica e de infraestrutura entre {$ind1} e {$ind2} atingiu o limiar de saturação sanitária. {$description} O nível crítico de associação exige a ativação imediata de canais de atenção primária intersetorial e controle vetorial focado.";
            case 3:
                return "NEXO CAUSAL ALTO: Registra-se forte tendência de avanço epidemiológico em {$territory}. {$description} O indicador reflete um padrão sazonal de alta transmissibilidade, sendo necessária a intervenção de saneamento ambiental temporário nas comunidades.";
            case 2:
                return "NEXO CAUSAL MODERADO: Em {$territory}, a dinâmica de associação está dentro do canal de resposta municipal histórica. {$description} Recomenda-se ações preventivas de monitoramento e conscientização local.";
            default:
                return "NEXO CAUSAL BASELINE / MONITORAMENTO: Sem evidência estatística de sobrecarga sanitária ativa em {$territory}. {$description} A relação opera nos padrões esperados para o ecossistema local.";
        }
    }

    /**
     * Render the Livewire component.
     */
    public function render()
    {
        $mapping = $this->faceMappings[$this->activeFace];
        $mappingB = $this->faceMappings[$this->activeFaceB];
        
        // Rows are always the 4 epidemiological diseases to study how they are influenced
        $heatmapRows = [];
        foreach ($this->dimensions['epidemiologica']['indicators'] as $indicator) {
            $heatmapRows[] = [
                'dimension' => $this->dimensions['epidemiologica']['short'],
                'indicator' => $indicator
            ];
        }

        return view('livewire.hipercubo-dashboard', [
            'dim1_key' => $mapping['key1'],
            'dim2_key' => $mapping['key2'],
            'dim1_label' => $this->dimensions[$mapping['key1']]['short'],
            'dim2_label' => $this->dimensions[$mapping['key2']]['short'],
            'dim1_indicators' => $this->dimensions[$mapping['key1']]['indicators'],
            'dim2_indicators' => $this->dimensions[$mapping['key2']]['indicators'],
            
            // Cube B fields
            'dim1_key_b' => $mappingB['key1'],
            'dim2_key_b' => $mappingB['key2'],
            'dim1_label_b' => $this->dimensions[$mappingB['key1']]['short'],
            'dim2_label_b' => $this->dimensions[$mappingB['key2']]['short'],
            
            'heatmapRows' => $heatmapRows,
            'activeMapping' => $mapping,
            'activeMappingB' => $mappingB
        ]);
    }
}
