<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Indicator;
use App\Models\Region;
use App\Models\MatrixCell;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Categories
        $inpe = Category::create(['name' => 'INPE - Ambiental']);
        $fiocruz = Category::create(['name' => 'Fiocruz - Saúde']);

        // 2. Indicators
        $inpeIndicators = [
            'Desmatamento Acumulado (PRODES)',
            'Focos de Calor e Queimadas (BDQueimadas)',
            'Anomalia de Precipitação (CPTEC)',
            'Temperatura da Superfície Terrestre (LST)',
            'Perda de Cobertura de Dossel'
        ];

        $fiocruzIndicators = [
            'Incidência de Casos de Malária',
            'Casos Notificados de Dengue',
            'Internações por Doenças Respiratórias (SRAG)',
            'Leishmaniose Tegumentar Americana',
            'Surtos de Doenças Diarreicas Agudas (DDA)'
        ];

        $indicatorsMap = [];

        foreach ($inpeIndicators as $name) {
            $indicatorsMap[] = Indicator::create([
                'category_id' => $inpe->id,
                'name' => $name
            ]);
        }

        foreach ($fiocruzIndicators as $name) {
            $indicatorsMap[] = Indicator::create([
                'category_id' => $fiocruz->id,
                'name' => $name
            ]);
        }

        // 3. Regions
        $regions = [
            'Arco do Desmatamento',
            'Amazônia Preservada (Calha Norte)',
            'Tríplice Fronteira (Solimões)',
            'Metrópole Manaus',
            'Litoral Amazônico (Marajó)'
        ];

        $regionModels = [];
        foreach ($regions as $name) {
            $regionModels[] = Region::create(['name' => $name]);
        }

        // 4. Matrix Cells
        foreach ($indicatorsMap as $indicator) {
            foreach ($regionModels as $region) {
                $density = 1;
                $text = '';

                if ($indicator->category_id === $inpe->id) {
                    // Environmental Indicators
                    if ($region->name === 'Arco do Desmatamento') {
                        if (str_contains($indicator->name, 'Desmatamento')) {
                            $density = 4;
                            $text = 'Alerta máximo. O desmatamento cumulativo nesta fronteira agrícola atinge taxas críticas superiores a 20% da cobertura original, gerando perda drástica de serviços ecossistêmicos locais.';
                        } elseif (str_contains($indicator->name, 'Focos')) {
                            $density = 4;
                            $text = 'Frequência severa de queimadas associadas à abertura de pastagens. Monitoramento por satélite registra picos históricos de emissões de carbono e degradação florestal periférica.';
                        } else {
                            $density = 3;
                            $text = 'Alterações microclimáticas regionais evidenciadas pela elevação da temperatura superficial terrestre em até 2.5°C no entorno imediato das áreas desmatadas.';
                        }
                    } elseif ($region->name === 'Amazônia Preservada (Calha Norte)') {
                        if (str_contains($indicator->name, 'Precipitação') || str_contains($indicator->name, 'Dossel')) {
                            $density = 2;
                            $text = 'Cobertura de dossel mantida acima de 95%. Contudo, anomalias climáticas globais começam a induzir variações sazonais de umidade nas franjas do maciço florestal.';
                        } else {
                            $density = 1;
                            $text = 'Gap de alerta. A integridade florestal e as restrições de acesso mantêm os níveis de impacto ambiental na menor faixa de risco reportada pelo monitoramento.';
                        }
                    } elseif ($region->name === 'Metrópole Manaus') {
                        if (str_contains($indicator->name, 'Temperatura')) {
                            $density = 4;
                            $text = 'Ilha de calor urbana proeminente. A rápida impermeabilização do solo urbano em Manaus gera anomalias térmicas severas se comparada à floresta circundante.';
                        } else {
                            $density = 3;
                            $text = 'Impacto moderado a alto. Pressão periurbana resulta em degradação acelerada de corpos d\'água internos e fragmentação secundária do dossel florestal.';
                        }
                    } else {
                        $density = rand(2, 3);
                        $text = 'Dinâmica regional de transição. Constata-se impacto ambiental de nível intermediário com flutuações sazonais de focos de calor durante a estiagem.';
                    }
                } else {
                    // Health Indicators (Fiocruz)
                    if ($region->name === 'Arco do Desmatamento') {
                        if (str_contains($indicator->name, 'Malária')) {
                            $density = 4;
                            $text = 'Correlação crítica: O padrão epidemiológico da malária acompanha diretamente as bordas de desmatamento recente, onde há proliferação otimizada do mosquito transmissor.';
                        } elseif (str_contains($indicator->name, 'Respiratórias')) {
                            $density = 4;
                            $text = 'Epidemiologia sazonal severa. A fumaça gerada pelas queimadas agrícolas causa picos agudos de internações por bronquite e pneumonia em crianças e idosos.';
                        } else {
                            $density = 3;
                            $text = 'Prevalência constante de endemias devido à mobilidade de trabalhadores temporários atraídos pelo avanço de frentes agrícolas na região.';
                        }
                    } elseif ($region->name === 'Amazônia Preservada (Calha Norte)') {
                        if (str_contains($indicator->name, 'Malária')) {
                            $density = 2;
                            $text = 'Nível de incidência baixa, correlacionada a assentamentos isolados e garimpos ilegais. Monitoramento limitado devido a desafios de acesso e conectividade.';
                        } else {
                            $density = 1;
                            $text = 'Lacuna ou baixíssima incidência. A ausência de frentes intensas de alteração antrópica e o isolamento geográfico impedem o estabelecimento de nexo causal ativo.';
                        }
                    } elseif ($region->name === 'Metrópole Manaus') {
                        if (str_contains($indicator->name, 'Dengue')) {
                            $density = 4;
                            $text = 'Surto recorrente. A elevada densidade populacional associada a bolsões de saneamento precário cria criadouros ideais para o Aedes aegypti sob climas tropicais úmidos.';
                        } elseif (str_contains($indicator->name, 'Diarreicas')) {
                            $density = 3;
                            $text = 'Ocupação urbana desordenada em igarapés metropolitanos resulta em contaminação hídrica recorrente e surtos localizados de doenças de veiculação hídrica.';
                        } else {
                            $density = 3;
                            $text = 'Níveis preocupantes. Internações por insuficiência respiratória agudas sob efeito conjunto de poluição veicular e transporte de plumas de poluição das queimadas vizinhas.';
                        }
                    } else {
                        $density = rand(2, 3);
                        $text = 'Registros endêmicos estáveis. A incidência local flutua de acordo com as cheias dos rios e o período chuvoso anual de cada sub-região.';
                    }
                }

                MatrixCell::create([
                    'indicator_id' => $indicator->id,
                    'region_id' => $region->id,
                    'density_level' => $density,
                    'correlation_text' => $text
                ]);
            }
        }
    }
}
