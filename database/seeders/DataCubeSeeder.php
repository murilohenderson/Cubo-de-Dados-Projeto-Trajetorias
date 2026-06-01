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
        // 1. Limpar tabelas para evitar duplicidade
        DB::statement('PRAGMA foreign_keys = OFF;');
        DadoCubo::truncate();
        Variavel::truncate();
        Eixo::truncate();
        Municipio::truncate();
        DB::statement('PRAGMA foreign_keys = ON;');

        // 2. Criar municípios oficiais
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

        // 3. Criar eixos do Hipercubo
        $eixosMap = [
            'ambiental' => Eixo::create([
                'slug' => 'ambiental',
                'nome' => 'Ambiental',
                'icone' => '🌱',
                'cor_primaria' => '#10B981',
                'descricao' => 'Dimensão Ambiental - Monitoramento florestal e focos de calor.'
            ]),
            'social' => Eixo::create([
                'slug' => 'social',
                'nome' => 'Social',
                'icone' => '👥',
                'cor_primaria' => '#3B82F6',
                'descricao' => 'Dimensão Social e Demográfica - Dinâmica populacional e infraestrutura.'
            ]),
            'economica' => Eixo::create([
                'slug' => 'economica',
                'nome' => 'Econômico',
                'icone' => '💼',
                'cor_primaria' => '#F59E0B',
                'descricao' => 'Dimensão Econômica - PIB e finanças regionais.'
            ]),
            'epidemiologica' => Eixo::create([
                'slug' => 'epidemiologica',
                'nome' => 'Epidemiológico',
                'icone' => '🏥',
                'cor_primaria' => '#EF4444',
                'descricao' => 'Dimensão Epidemiológica - Incidência de doenças endêmicas e surtos.'
            ]),
        ];

        // 4. Função de normalização de IBGE
        $normalizeIbge = function ($code) {
            $code = trim($code);
            $mapping = [
                '1501204' => '1501208', // Baião
                '1502103' => '1502107', // Cametá
                '1504604' => '1504707', // Mocajuba
            ];
            return $mapping[$code] ?? $code;
        };

        // 5. Importar CSVs dimensionais (Ambiental, Populacional, Socioeconômico)
        $dimFiles = [
            'public/tables/atualizado_dimensao_ambiental (1).csv' => 'ambiental',
            'public/tables/atualizado_dimensao_populacional (1).csv' => 'social',
            'public/tables/atualizado_dimensao_socioeconomica (1).csv' => 'economica',
        ];

        foreach ($dimFiles as $filePath => $eixoSlug) {
            $this->command->info("Processando: {$filePath} para o eixo {$eixoSlug}");
            if (!file_exists($filePath)) {
                $this->command->error("Arquivo não encontrado: {$filePath}");
                continue;
            }

            $file = fopen($filePath, 'r');
            // Ler cabeçalho: Municipio,codigo_ibge,Eixo,Variavel,Valor,Unidade,Ano
            $header = fgetcsv($file);

            $eixo = $eixosMap[$eixoSlug];

            while (($row = fgetcsv($file)) !== false) {
                if (count($row) < 7) continue;

                $ibgeRaw = trim($row[1]);
                $ibge = $normalizeIbge($ibgeRaw);
                $varNome = trim($row[3]);
                $valor = floatval(str_replace(',', '.', trim($row[4])));
                $unidade = trim($row[5]);
                $ano = trim($row[6]);

                $municipio = $municipiosMap[$ibge] ?? null;
                if (!$municipio) {
                    $this->command->warn("Município com IBGE {$ibge} não encontrado. Pulando linha.");
                    continue;
                }

                // Garantir variável criada
                $variavel = Variavel::firstOrCreate([
                    'eixo_id' => $eixo->id,
                    'nome' => $varNome,
                ], [
                    'unidade' => $unidade,
                    'descricao' => "Indicador de {$varNome} do eixo {$eixo->nome}."
                ]);

                // Inserir fato
                DadoCubo::create([
                    'municipio_id' => $municipio->id,
                    'variavel_id' => $variavel->id,
                    'ano_periodo' => $ano,
                    'valor' => $valor,
                    'taxa' => null,
                    'detalhes' => null,
                ]);
            }
            fclose($file);
        }

        // 6. Importar CSV Epidemiológico
        $epidemiologicoPath = 'public/tables/Indicadores_Dimensao_Epidemiologica_Limpos.csv';
        $this->command->info("Processando: {$epidemiologicoPath}");

        if (file_exists($epidemiologicoPath)) {
            $file = fopen($epidemiologicoPath, 'r');
            // Ler cabeçalho: uf,estado,municipio,codigo_ibge,periodo,zona_residencial,doenca,numero_casos,taxa_incidencia
            $header = fgetcsv($file);

            $eixo = $eixosMap['epidemiologica'];

            while (($row = fgetcsv($file)) !== false) {
                if (count($row) < 9) continue;

                $ibgeRaw = trim($row[3]);
                $ibge = $normalizeIbge($ibgeRaw);
                $periodo = trim($row[4]);
                $zona = trim($row[5]); // rural, urban, total
                $doenca = trim($row[6]); // chagas, CL, VL, Dengue, Falciparum, Vivax, Vivax+Falciparum
                $casos = floatval(str_replace(',', '.', trim($row[7])));
                $taxa = floatval(str_replace(',', '.', trim($row[8])));

                $municipio = $municipiosMap[$ibge] ?? null;
                if (!$municipio) {
                    $this->command->warn("Município com IBGE {$ibge} não encontrado no epidemiológico. Pulando.");
                    continue;
                }

                // Mapear nomes de doenças para exibição amigável
                $nomesDoencasMap = [
                    'chagas'           => 'Doença de Chagas',
                    'CL'               => 'Leishmaniose Cutânea (LTA)',
                    'VL'               => 'Leishmaniose Visceral (Calazar)',
                    'Dengue'           => 'Dengue',
                    'Falciparum'       => 'Malária Falciparum',
                    'Vivax'            => 'Malária Vivax',
                    'Vivax+Falciparum' => 'Malária Mista (Vivax+Falci)',
                ];
                $varNome = $nomesDoencasMap[$doenca] ?? $doenca;

                $variavel = Variavel::firstOrCreate([
                    'eixo_id' => $eixo->id,
                    'nome' => $varNome,
                ], [
                    'unidade' => 'Casos / Taxa',
                    'descricao' => "Casos e taxa de incidência para {$varNome}."
                ]);

                // Inserir fato com detalhes extras (zona residencial)
                DadoCubo::create([
                    'municipio_id' => $municipio->id,
                    'variavel_id' => $variavel->id,
                    'ano_periodo' => $periodo,
                    'valor' => $casos,
                    'taxa' => $taxa,
                    'detalhes' => [
                        'zona_residencial' => $zona,
                        'doenca_original'  => $doenca,
                    ],
                ]);
            }
            fclose($file);
        } else {
            $this->command->error("Arquivo epidemiológico não encontrado!");
        }

        $this->command->info("Seed completo! Todos os eixos e variáveis populados.");
    }
}
