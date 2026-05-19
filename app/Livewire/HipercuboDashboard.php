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
     * Contextual territory axis.
     *
     * @var array
     */
    public array $territories = [
        'Arco do Desmatamento',
        'Amazônia Preservada',
        'Fronteira Agrícola',
        'Unidades de Conservação'
    ];

    /**
     * Mapping of 3D cube faces to their corresponding primary dimension pair (forces).
     *
     * @var array
     */
    public array $faceMappings = [
        'front' => [
            'key1' => 'ambiental',
            'key2' => 'epidemiologica',
            'label' => 'Ambiental × Epidemiológica',
            'desc' => 'Cruzamento de dinâmicas climáticas/ambientais com agravos epidemiológicos da Fiocruz.'
        ],
        'back' => [
            'key1' => 'economica',
            'key2' => 'social',
            'label' => 'Econômica × Social',
            'desc' => 'Correlações entre expansão de crédito/produção e indicadores sociais locais.'
        ],
        'left' => [
            'key1' => 'ambiental',
            'key2' => 'social',
            'label' => 'Ambiental × Social',
            'desc' => 'Efeitos de dinâmicas de uso da terra e quebras florestais na vulnerabilidade social.'
        ],
        'right' => [
            'key1' => 'economica',
            'key2' => 'epidemiologica',
            'label' => 'Econômica × Epidemiológica',
            'desc' => 'Pressões econômicas de exploração e incentivos à incidência de endemias.'
        ],
        'top' => [
            'key1' => 'ambiental',
            'key2' => 'economica',
            'label' => 'Ambiental × Econômica',
            'desc' => 'Efeitos de vetores e incentivos de produção econômica sobre o desmatamento.'
        ],
        'bottom' => [
            'key1' => 'social',
            'key2' => 'epidemiologica',
            'label' => 'Social × Epidemiológica',
            'desc' => 'Dinâmica entre barreiras de saneamento, vulnerabilidade e propagação de infecções.'
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
        }
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
     * Computes risk and text for the selected cell.
     *
     * @param string $territory
     * @param string $rowIndicator
     */
    public function selectCell(string $territory, string $rowIndicator)
    {
        $mapping = $this->faceMappings[$this->activeFace];
        
        // Calculate deterministic risk level based on selected indicators, territory and row indicator
        $hash = crc32($territory . $rowIndicator . $this->selectedInd1 . $this->selectedInd2);
        $riskLevel = abs($hash % 4) + 1; // 1 to 4

        // Generate scientific synthesis text
        $evidenceText = $this->generateEvidence($territory, $rowIndicator, $riskLevel);

        $this->selectedCell = [
            'territory' => $territory,
            'row_indicator' => $rowIndicator,
            'risk_level' => $riskLevel,
            'evidence_text' => $evidenceText,
            'indicator_1' => $this->selectedInd1,
            'indicator_2' => $this->selectedInd2,
            'dim_1_label' => $this->dimensions[$mapping['key1']]['short'],
            'dim_2_label' => $this->dimensions[$mapping['key2']]['short'],
            'timestamp' => now()->format('H:i:s')
        ];
    }

    /**
     * Helper to generate realistic scientific synthesis.
     */
    private function generateEvidence(string $territory, string $rowIndicator, int $riskLevel): string
    {
        $ind1 = $this->selectedInd1;
        $ind2 = $this->selectedInd2;

        switch ($riskLevel) {
            case 4:
                return "ALERTA CIENTÍFICO CRÍTICO: No território '{$territory}', a modelagem espacial revela sinergia destrutiva. O indicador '{$rowIndicator}' atingiu limites de segurança em decorrência direta dos nexos de causalidade acelerados entre '{$ind1}' e '{$ind2}'. A correlação espacial sugere que a desestruturação ecológica regional atua como um super-amplificador epidemiológico e de estresse socioambiental. Recomenda-se o acionamento imediato dos sistemas locais de contenção e ações conjuntas de regulação florestal e sanitária.";
            case 3:
                return "RISCO ALTO: No território '{$territory}', observa-se forte nexo estatístico. O indicador '{$rowIndicator}' comporta-se como catalisador de vulnerabilidades para a perturbação ambiental ou endêmica disparada por '{$ind1}' e '{$ind2}'. Os dados históricos apontam para um comportamento não linear, com possibilidade de transbordamento de surtos ou perda ecológica irreversível nas próximas janelas sazonais de seca.";
            case 2:
                return "ALERTA MODERADO: Registra-se correlação de nível intermediário no território '{$territory}'. A dinâmica combinada de '{$ind1}' e '{$ind2}' afeta o parâmetro '{$rowIndicator}' dentro de margens manejáveis, muito associadas ao ciclo pluvial amazônico. Medidas preventivas locais e monitoramento contínuo por sensoriamento remoto são suficientes para o controle do cenário.";
            default:
                return "BASELINE CIENTÍFICO: No território '{$territory}', a interação entre '{$ind1}', '{$ind2}' e o indicador '{$rowIndicator}' encontra-se estável, operando dentro do desvio padrão ecológico e social histórico. Não há evidência estatística de desequilíbrio sistêmico na série temporal analisada, constituindo uma zona ativa de controle ecológico.";
        }
    }

    /**
     * Render the Livewire component.
     */
    public function render()
    {
        // Find which two dimensions are NOT in the active face mapping (to render as rows)
        $mapping = $this->faceMappings[$this->activeFace];
        $activeKeys = [$mapping['key1'], $mapping['key2']];
        
        $heatmapRows = [];
        foreach ($this->dimensions as $key => $dim) {
            if (!in_array($key, $activeKeys)) {
                foreach ($dim['indicators'] as $indicator) {
                    $heatmapRows[] = [
                        'dimension' => $dim['short'],
                        'indicator' => $indicator
                    ];
                }
            }
        }

        return view('livewire.hipercubo-dashboard', [
            'dim1_key' => $mapping['key1'],
            'dim2_key' => $mapping['key2'],
            'dim1_label' => $this->dimensions[$mapping['key1']]['short'],
            'dim2_label' => $this->dimensions[$mapping['key2']]['short'],
            'dim1_indicators' => $this->dimensions[$mapping['key1']]['indicators'],
            'dim2_indicators' => $this->dimensions[$mapping['key2']]['indicators'],
            'heatmapRows' => $heatmapRows,
            'activeMapping' => $mapping
        ]);
    }
}
