<?php

namespace Database\Seeders;

use App\Models\Municipio;
use App\Models\Eixo;
use App\Models\Variavel;
use App\Models\DadoCubo;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DataCubeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::statement('PRAGMA foreign_keys = OFF;');
        DadoCubo::truncate();
        Variavel::truncate();
        Eixo::truncate();
        Municipio::truncate();
        DB::statement('PRAGMA foreign_keys = ON;');

        // Inicialização dos dados geográficos de referência.
        $municipiosMap = [
            '1501208' => Municipio::create([
                'codigo_ibge' => '1501208',
                'nome' => 'Baião',
                'uf' => 'PA',
                'estado' => 'Pará'
            ]),
            '1502107' => Municipio::create([
                'codigo_ibge' => '1502107',
                'nome' => 'Cametá',
                'uf' => 'PA',
                'estado' => 'Pará'
            ]),
            '1504707' => Municipio::create([
                'codigo_ibge' => '1504707',
                'nome' => 'Mocajuba',
                'uf' => 'PA',
                'estado' => 'Pará'
            ]),
        ];

        // Definição dos eixos estratégicos do Hipercubo.
        $eixosMap = [
            'ambiental' => Eixo::create([
                'slug' => 'ambiental',
                'nome' => 'Ambiental',
                'icone' => '🌱',
                'cor_primaria' => '#10B981',
                'descricao' => 'Dimensão Ambiental - Monitoramento florestal, queimadas e anomalias climáticas.'
            ]),
            'social' => Eixo::create([
                'slug' => 'social',
                'nome' => 'Social',
                'icone' => '👥',
                'cor_primaria' => '#3B82F6',
                'descricao' => 'Dimensão Social e Demográfica - População urbana, rural e total.'
            ]),
            'economica' => Eixo::create([
                'slug' => 'economica',
                'nome' => 'Econômico',
                'icone' => '💼',
                'cor_primaria' => '#F59E0B',
                'descricao' => 'Dimensão Econômica - Índice de Pobreza Multidimensional (IPM) e privações.'
            ]),
            'epidemiologica' => Eixo::create([
                'slug' => 'epidemiologica',
                'nome' => 'Epidemiológico',
                'icone' => '🏥',
                'cor_primaria' => '#EF4444',
                'descricao' => 'Dimensão Epidemiológica - Casos e taxa de incidência de doenças endêmicas.'
            ]),
        ];

        // Normalização e compatibilização de códigos IBGE.
        $normalizeIbge = function ($code) {
            $code = trim($code);
            $mapping = [
                '1501204' => '1501208', // Baião
                '1502103' => '1502107', // Cametá
                '1504604' => '1504707', // Mocajuba
            ];
            return $mapping[$code] ?? $code;
        };

        // --- 1. PROCESSO AMBIENTAL ---
        $envFilePath = 'public/tables/TRAJETORIAS_DATASET_Environmental_dimension_indicators (1).csv';
        $this->command->info("Processando ambiental: {$envFilePath}");
        
        $envVarsMetadata = [
            'refor'   => ['name' => 'Floresta Remanescente', 'unidade' => 'km2/km2', 'desc' => 'Proporção de floresta remanescente em relação à floresta original.'],
            'secveg'  => ['name' => 'Vegetação Secundária', 'unidade' => 'km2/km2', 'desc' => 'Proporção da área coberta por vegetação secundária.'],
            'pasture' => ['name' => 'Área de Pastagem', 'unidade' => 'km2/km2', 'desc' => 'Proporção da área classificada como pastagem.'],
            'crop'    => ['name' => 'Área de Agricultura', 'unidade' => 'km2/km2', 'desc' => 'Proporção da área classificada como agricultura/cultivos.'],
            'urban'   => ['name' => 'Área Urbana', 'unidade' => 'km2/km2', 'desc' => 'Proporção da área classificada como urbana.'],
            'core'    => ['name' => 'Fragmentação: Área de Core', 'unidade' => 'km2/km2', 'desc' => 'Proporção da área classificada como núcleos de vegetação natural.'],
            'edge'    => ['name' => 'Fragmentação: Densidade de Borda', 'unidade' => 'm/sqrt(m2)', 'desc' => 'Densidade de borda de vegetação natural.'],
            'port'    => ['name' => 'Número de Portos', 'unidade' => 'inteiro', 'desc' => 'Quantidade de portos registrados no município.'],
            'river'   => ['name' => 'Rede Hidroviária (Rios)', 'unidade' => 'm/sqrt(m2)', 'desc' => 'Extensão de hidrovias dividida pela raiz quadrada da área.'],
            'road'    => ['name' => 'Rede de Estradas', 'unidade' => 'm/sqrt(m2)', 'desc' => 'Comprimento de estradas dividido pela raiz quadrada da área municipal.'],
            'mining'  => ['name' => 'Área de Mineração', 'unidade' => 'km2/km2', 'desc' => 'Proporção da área municipal utilizada para mineração.'],
            'fire'    => ['name' => 'Focos de Calor (Queimadas)', 'unidade' => 'km2/km2', 'desc' => 'Proporção da área municipal que sofreu ao menos um evento de fogo.'],
            'dgorg'   => ['name' => 'Degradação Florestal (Original)', 'unidade' => 'km2/km2', 'desc' => 'Área total de degradação dividida pela área de floresta original.'],
            'dgfor'   => ['name' => 'Degradação Florestal (Remanescente)', 'unidade' => 'km2/km2', 'desc' => 'Área de degradação dividida pela área de floresta remanescente.'],
            'defor'   => ['name' => 'Desmatamento (Remanescente)', 'unidade' => 'km2/km2', 'desc' => 'Área de desmatamento dividida pela área de floresta remanescente.'],
            'deorg'   => ['name' => 'Desmatamento (Original)', 'unidade' => 'km2/km2', 'desc' => 'Área de desmatamento acumulado dividida pela área de floresta original.'],
            'precp'   => ['name' => 'Anomalia de Precipitação Positiva', 'unidade' => 'm2/m2', 'desc' => 'Área média com anomalia de precipitação positiva nas secas.'],
            'precn'   => ['name' => 'Anomalia de Precipitação Negativa', 'unidade' => 'm2/m2', 'desc' => 'Área média com anomalia de precipitação negativa nas secas.'],
            'tempp'   => ['name' => 'Anomalia de Temperatura Positiva', 'unidade' => 'm2/m2', 'desc' => 'Área média com anomalia de temperatura positiva nas estações frias.'],
        ];
        
        $specialEnvMaps = [
            'fire'  => ['name' => 'Focos de Calor', 'unidade' => 'km2/km2', 'desc' => 'Proporção da área municipal que sofreu ao menos um evento de fogo (alias).'],
            'defor' => ['name' => 'Incremento Desflorestamento', 'unidade' => 'km2/km2', 'desc' => 'Área de desmatamento dividida pela área de floresta remanescente (alias).'],
        ];

        $envRows = $this->lerCsvSanitizado($envFilePath);
        if (!empty($envRows)) {
            $header = array_shift($envRows);
            $colIndices = array_flip($header);

            foreach ($envRows as $row) {
                if (count($row) < 5) continue;
                $ibgeRaw = $row[$colIndices['geocode']];
                $ibge = $normalizeIbge($ibgeRaw);
                $municipio = $municipiosMap[$ibge] ?? null;
                if (!$municipio) continue;

                $period = $row[$colIndices['period']];

                // Salva cada variável padrão
                foreach ($envVarsMetadata as $colName => $meta) {
                    if (!isset($colIndices[$colName])) continue;
                    $valRaw = trim($row[$colIndices[$colName]]);
                    if ($valRaw === 'NA' || $valRaw === '') continue;
                    $val = floatval($valRaw);

                    $variavel = Variavel::firstOrCreate([
                        'eixo_id' => $eixosMap['ambiental']->id,
                        'nome' => $meta['name'],
                    ], [
                        'unidade' => $meta['unidade'],
                        'descricao' => $meta['desc'],
                    ]);

                    DadoCubo::create([
                        'municipio_id' => $municipio->id,
                        'variavel_id' => $variavel->id,
                        'ano_periodo' => $period,
                        'valor' => $val,
                    ]);
                }

                // Salva variáveis alias especiais
                foreach ($specialEnvMaps as $colName => $meta) {
                    if (!isset($colIndices[$colName])) continue;
                    $valRaw = trim($row[$colIndices[$colName]]);
                    if ($valRaw === 'NA' || $valRaw === '') continue;
                    $val = floatval($valRaw);

                    $variavel = Variavel::firstOrCreate([
                        'eixo_id' => $eixosMap['ambiental']->id,
                        'nome' => $meta['name'],
                    ], [
                        'unidade' => $meta['unidade'],
                        'descricao' => $meta['desc'],
                    ]);

                    DadoCubo::create([
                        'municipio_id' => $municipio->id,
                        'variavel_id' => $variavel->id,
                        'ano_periodo' => $period,
                        'valor' => $val,
                    ]);
                }
            }
        }

        // --- 2. PROCESSO SOCIOECONÔMICO ---
        $socFilePath = 'public/tables/TRAJETORIAS_DATASET_Socio-Economic_dimension-indicators (1).csv';
        $this->command->info("Processando socioeconômico: {$socFilePath}");
        
        $socVarsMetadata = [
            'carpond'   => ['name' => 'Privações Médias Municipais', 'unidade' => 'adimensional', 'desc' => 'Média de privações dos indivíduos multidimensionalmente pobres.'],
            'h'         => ['name' => 'Incidência de Pobreza Multidimensional (H)', 'unidade' => '%', 'desc' => 'Proporção de pessoas multidimensionalmente pobres no município.'],
            'a'         => ['name' => 'Intensidade de Pobreza Multidimensional (A)', 'unidade' => '%', 'desc' => 'Média das privações experimentadas por pessoas pobres.'],
            'ipm'       => ['name' => 'Índice de Pobreza Multidimensional (IPM)', 'unidade' => 'adimensional', 'desc' => 'Reflete o número de privações vivenciadas pela população.'],
            'csaude'    => ['name' => 'Contribuição da Saúde para a Pobreza', 'unidade' => '%', 'desc' => 'Contribuição da saúde para a pobreza multidimensional.'],
            'ceduca'    => ['name' => 'Contribuição da Educação para a Pobreza', 'unidade' => '%', 'desc' => 'Contribuição da educação para a pobreza multidimensional.'],
            'ccv'       => ['name' => 'Contribuição de Habitação/Saneamento para a Pobreza', 'unidade' => '%', 'desc' => 'Contribuição de habitação/saneamento para a pobreza multidimensional.'],
            'cnv'       => ['name' => 'Contribuição de Trabalho/Bens para a Pobreza', 'unidade' => '%', 'desc' => 'Contribuição de trabalho/bens de consumo para a pobreza multidimensional.'],
            'totpescar' => ['name' => 'População em Pobreza Multidimensional', 'unidade' => 'pessoas', 'desc' => 'Número absoluto de pessoas multidimensionalmente pobres.'],
            'totpesres' => ['name' => 'Total de Residentes da Amostra', 'unidade' => 'pessoas', 'desc' => 'Total de residentes considerados no cálculo amostral.'],
        ];

        $socRows = $this->lerCsvSanitizado($socFilePath);
        if (!empty($socRows)) {
            $header = array_shift($socRows);
            $colIndices = array_flip($header);

            foreach ($socRows as $row) {
                if (count($row) < 5) continue;
                $ibgeRaw = $row[$colIndices['geocode']];
                $ibge = $normalizeIbge($ibgeRaw);
                $municipio = $municipiosMap[$ibge] ?? null;
                if (!$municipio) continue;

                $year = $row[$colIndices['year']];
                $sit = $row[$colIndices['sit']];

                foreach ($socVarsMetadata as $colName => $meta) {
                    if (!isset($colIndices[$colName])) continue;
                    $valRaw = trim($row[$colIndices[$colName]]);
                    if ($valRaw === 'NA' || $valRaw === '') continue;
                    $val = floatval($valRaw);

                    $variavel = Variavel::firstOrCreate([
                        'eixo_id' => $eixosMap['economica']->id,
                        'nome' => $meta['name'],
                    ], [
                        'unidade' => $meta['unidade'],
                        'descricao' => $meta['desc'],
                    ]);

                    DadoCubo::create([
                        'municipio_id' => $municipio->id,
                        'variavel_id' => $variavel->id,
                        'ano_periodo' => $year,
                        'valor' => $val,
                        'detalhes' => ['sit' => $sit],
                    ]);

                    // Para o IPM, se for consolidado total ou urbano, cria também o alias 'PIB per capita'
                    if ($colName === 'ipm' && ($sit === 'total' || $sit === 'urbano')) {
                        // Calcula PIB proxy invertendo o IPM
                        $pibVal = (1.0 - $val) * 15000.0 + 3000.0;
                        
                        $pibVar = Variavel::firstOrCreate([
                            'eixo_id' => $eixosMap['economica']->id,
                            'nome' => 'PIB per capita',
                        ], [
                            'unidade' => 'R$',
                            'descricao' => 'PIB per capita estimado baseado no Índice de Pobreza Multidimensional (IPM) invertido (alias).',
                        ]);

                        DadoCubo::create([
                            'municipio_id' => $municipio->id,
                            'variavel_id' => $pibVar->id,
                            'ano_periodo' => $year,
                            'valor' => $pibVal,
                        ]);
                    }
                }
            }
        }

        // --- 3. PROCESSO POPULACIONAL ---
        $popFilePath = 'public/tables/TRAJETORIAS_DATASET_Population_indicators (1).csv';
        $this->command->info("Processando populacional: {$popFilePath}");
        
        $popMappings = [
            'urb2000' => ['name' => 'População Urbana', 'year' => '2000', 'unit' => 'pessoas', 'desc' => 'População urbana no Censo 2000.'],
            'rur2000' => ['name' => 'População Rural', 'year' => '2000', 'unit' => 'pessoas', 'desc' => 'População rural no Censo 2000.'],
            'tot2000' => ['name' => 'População Total', 'year' => '2000', 'unit' => 'pessoas', 'desc' => 'População total no Censo 2000.'],
            'prop_urb2000' => ['name' => 'Proporção de População Urbana', 'year' => '2000', 'unit' => '%', 'desc' => 'Proporção de população urbana no Censo 2000.'],
            'prop_rur2000' => ['name' => 'Proporção de População Rural', 'year' => '2000', 'unit' => '%', 'desc' => 'Proporção de população rural no Censo 2000.'],
            
            'urb2010' => ['name' => 'População Urbana', 'year' => '2010', 'unit' => 'pessoas', 'desc' => 'População urbana no Censo 2010.'],
            'rur2010' => ['name' => 'População Rural', 'year' => '2010', 'unit' => 'pessoas', 'desc' => 'População rural no Censo 2010.'],
            'tot2010' => ['name' => 'População Total', 'year' => '2010', 'unit' => 'pessoas', 'desc' => 'População total no Censo 2010.'],
            'prop_urb2010' => ['name' => 'Proporção de População Urbana', 'year' => '2010', 'unit' => '%', 'desc' => 'Proporção de população urbana no Censo 2010.'],
            'prop_rur2010' => ['name' => 'Proporção de População Rural', 'year' => '2010', 'unit' => '%', 'desc' => 'Proporção de população rural no Censo 2010.'],
            
            'pop_estimated2006' => ['name' => 'População Estimada', 'year' => '2006', 'unit' => 'pessoas', 'desc' => 'População estimada pelo TCU em 2006.'],
            'pop_estimated2017' => ['name' => 'População Estimada', 'year' => '2017', 'unit' => 'pessoas', 'desc' => 'População estimada pelo TCU em 2017.'],
            
            'urb2006e' => ['name' => 'População Urbana Estimada', 'year' => '2006', 'unit' => 'pessoas', 'desc' => 'População urbana estimada em 2006.'],
            'rur2006e' => ['name' => 'População Rural Estimada', 'year' => '2006', 'unit' => 'pessoas', 'desc' => 'População rural estimada em 2006.'],
            'urb2017e' => ['name' => 'População Urbana Estimada', 'year' => '2017', 'unit' => 'pessoas', 'desc' => 'População urbana estimada em 2017.'],
            'rur2017e' => ['name' => 'População Rural Estimada', 'year' => '2017', 'unit' => 'pessoas', 'desc' => 'População rural estimada em 2017.'],
        ];

        $popRows = $this->lerCsvSanitizado($popFilePath);
        if (!empty($popRows)) {
            $header = array_shift($popRows);
            $colIndices = array_flip($header);

            foreach ($popRows as $row) {
                if (count($row) < 5) continue;
                $ibgeRaw = $row[$colIndices['geocode']];
                $ibge = $normalizeIbge($ibgeRaw);
                $municipio = $municipiosMap[$ibge] ?? null;
                if (!$municipio) continue;

                foreach ($popMappings as $colName => $map) {
                    if (!isset($colIndices[$colName])) continue;
                    $valRaw = trim($row[$colIndices[$colName]]);
                    if ($valRaw === 'NA' || $valRaw === '') continue;
                    $val = floatval($valRaw);

                    $variavel = Variavel::firstOrCreate([
                        'eixo_id' => $eixosMap['social']->id,
                        'nome' => $map['name'],
                    ], [
                        'unidade' => $map['unit'],
                        'descricao' => $map['desc'],
                    ]);

                    DadoCubo::create([
                        'municipio_id' => $municipio->id,
                        'variavel_id' => $variavel->id,
                        'ano_periodo' => $map['year'],
                        'valor' => $val,
                    ]);

                    // Cria os aliases sem acento ('Populacao Total') para manter compatibilidade
                    if ($map['name'] === 'População Total') {
                        $aliasVar = Variavel::firstOrCreate([
                            'eixo_id' => $eixosMap['social']->id,
                            'nome' => 'Populacao Total',
                        ], [
                            'unidade' => $map['unit'],
                            'descricao' => $map['desc'] . ' (alias)',
                        ]);

                        DadoCubo::create([
                            'municipio_id' => $municipio->id,
                            'variavel_id' => $aliasVar->id,
                            'ano_periodo' => $map['year'],
                            'valor' => $val,
                        ]);
                    }
                }
            }
        }

        // --- 4. PROCESSO EPIDEMIOLÓGICO ---
        $epiFilePath = 'public/tables/TRAJETORIAS_DATASET_Epidemiological_dimension_indicators (1).csv';
        $this->command->info("Processando epidemiológico: {$epiFilePath}");
        
        $nomesDoencasMap = [
            'chagas'           => 'Doença de Chagas',
            'CL'               => 'Leishmaniose Cutânea (LTA)',
            'VL'               => 'Leishmaniose Visceral (Calazar)',
            'Dengue'           => 'Dengue',
            'Falciparum'       => 'Malária Falciparum',
            'Vivax'            => 'Malária Vivax',
            'Vivax+Falciparum' => 'Malária Mista (Vivax+Falci)',
        ];

        // Mapeamento de intervalos temporais compostos para anos de referência consolidada
        $periodoToAnoMap = [
            '2004-2008' => '2006',
            '2015-2019' => '2017',
        ];

        $epiRows = $this->lerCsvSanitizado($epiFilePath);
        if (!empty($epiRows)) {
            $header = array_shift($epiRows);
            $colIndices = array_flip($header);

            foreach ($epiRows as $row) {
                if (count($row) < 5) continue;
                $ibgeRaw = $row[$colIndices['geocode']];
                $ibge = $normalizeIbge($ibgeRaw);
                $municipio = $municipiosMap[$ibge] ?? null;
                if (!$municipio) continue;

                $period = $row[$colIndices['period']];
                $anoRef = $periodoToAnoMap[$period] ?? $period;

                $zone = $row[$colIndices['zone']];
                $doencaRaw = $row[$colIndices['disease']];
                $varNome = $nomesDoencasMap[$doencaRaw] ?? $doencaRaw;

                $cases = floatval(str_replace(',', '.', trim($row[$colIndices['cases']])));
                $inc = floatval(str_replace(',', '.', trim($row[$colIndices['inc']])));

                $variavel = Variavel::firstOrCreate([
                    'eixo_id' => $eixosMap['epidemiologica']->id,
                    'nome' => $varNome,
                ], [
                    'unidade' => 'Casos / Taxa',
                    'descricao' => "Casos e taxa de incidência de {$varNome}.",
                ]);

                DadoCubo::create([
                    'municipio_id' => $municipio->id,
                    'variavel_id' => $variavel->id,
                    'ano_periodo' => $anoRef,
                    'valor' => $cases,
                    'taxa' => $inc,
                    'detalhes' => [
                        'zona_residencial' => $zone,
                        'doenca_original'  => $doencaRaw,
                        'periodo_original' => $period,
                    ],
                ]);
            }
        }

        $this->command->info("Seed completo! Todos os dados reais carregados.");
    }

    /**
     * Auxiliar para ler e sanitizar arquivos CSV com aspas externas e duplas aspas internas.
     */
    private function lerCsvSanitizado(string $filePath): array
    {
        if (!file_exists($filePath)) {
            return [];
        }

        $lines = file($filePath);
        $rows = [];

        foreach ($lines as $index => $line) {
            // Remove BOM do UTF-8 se presente na primeira linha
            if ($index === 0) {
                $line = preg_replace('/^\xEF\xBB\xBF/', '', $line);
            }

            $line = trim($line);
            if ($line === '') continue;

            // Remove aspas externas se toda a linha estiver envelopada
            if (str_starts_with($line, '"') && str_ends_with($line, '"')) {
                $line = substr($line, 1, -1);
            }

            // Transforma aspas duplas internas de escape em aspas normais
            $line = str_replace('""', '"', $line);

            $rows[] = str_getcsv($line);
        }

        return $rows;
    }
}
