<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Municipio;
use App\Models\Variavel;
use App\Models\DadoCubo;
use App\Models\CorrelacaoVariavel;
use App\Models\FeatureMl;

/**
 * Artisan Command: gerar:features-ml
 *
 * Popula a tabela `features_ml` (Camada 3 — ML Pipeline) com vetores de features
 * normalizados, labels de risco por quartil e embeddings do grafo de correlações.
 *
 * Pré-requisito: executar `php artisan calcular:correlacoes` antes deste comando.
 *
 * Uso:
 *   php artisan gerar:features-ml
 *   php artisan gerar:features-ml --ano=2022
 *   php artisan gerar:features-ml --municipio=1
 *   php artisan gerar:features-ml --versao=1.1
 *   php artisan gerar:features-ml --export-csv
 */
class GerarFeaturesMl extends Command
{
    protected $signature = 'gerar:features-ml
                            {--ano=        : Processa apenas um ano específico (padrão: todos)}
                            {--municipio=  : ID do município específico (padrão: todos)}
                            {--versao=1.0  : Tag de versão do pipeline para rastreabilidade}
                            {--export-csv  : Exporta o dataset para storage/app/features_ml.csv após gerar}';

    protected $description = 'Gera o vetor de features normalizado por município/ano e popula o ML Pipeline (tabela features_ml)';

    /** Mapa de slug de variável → coluna de feature na tabela features_ml */
    private const MAPA_FEATURES = [
        'desmatamento'  => 'feat_desmatamento_norm',
        'focos_calor'   => 'feat_focos_calor_norm',
        'populacao'     => 'feat_populacao_norm',
        'densidade'     => 'feat_densidade_norm',
        'pib'           => 'feat_pib_norm',
        'dengue'        => 'feat_dengue_taxa_norm',
        'malaria'       => 'feat_malaria_taxa_norm',
        'chagas'        => 'feat_chagas_taxa_norm',
        'leishmaniose'  => 'feat_leishmaniose_norm',
    ];

    public function handle(): int
    {
        $versao    = $this->option('versao');
        $anoFiltro = $this->option('ano');
        $munFiltro = $this->option('municipio') ? (int) $this->option('municipio') : null;
        $exportCsv = $this->option('export-csv');

        $this->info('🧠 Projeto Trajetórias — Geração do Pipeline de ML');
        $this->info("   Versão do pipeline: {$versao}");
        $this->newLine();

        // 1. Valida pré-requisito: correlações calculadas
        $totalCorrelacoes = CorrelacaoVariavel::count();
        if ($totalCorrelacoes === 0) {
            $this->warn('⚠️  Tabela correlacoes_variaveis está vazia.');
            $this->warn('   Execute primeiro: php artisan calcular:correlacoes');
            $this->warn('   Os embeddings de grafo ficarão zerados nesta execução.');
        } else {
            $this->info("🔗 Correlações no grafo: {$totalCorrelacoes}");
        }

        // 2. Carrega variáveis
        $variaveis = Variavel::all()->keyBy(fn($v) => $this->slugVariavel($v->nome));

        // 3. Determina todos os anos disponíveis no cubo
        $query = DadoCubo::query()->distinct()->pluck('ano_periodo')->sort()->values();
        $anos  = $anoFiltro ? collect([$anoFiltro]) : $query->filter(fn($a) => strlen($a) === 4);

        // 4. Carrega municípios
        $municipios = $munFiltro
            ? Municipio::where('id', $munFiltro)->get()
            : Municipio::all();

        $this->info("📅 Anos: " . $anos->implode(', '));
        $this->info("📍 Municípios: {$municipios->count()}");
        $this->newLine();

        // 5. Calcula min-max globais por variável para normalização consistente
        $this->info('📐 Calculando min-max para normalização...');
        $normalizadores = $this->calcularNormalizadores($variaveis);

        // 6. Calcula quartis de risco epidemiológico global
        $this->info('⚠️  Calculando quartis de risco...');
        $quartisRisco = $this->calcularQuartisRisco();

        $this->newLine();
        $bar = $this->output->createProgressBar($municipios->count() * $anos->count());
        $bar->start();

        $salvos = 0;

        foreach ($municipios as $municipio) {
            // Embedding do grafo para este município
            $embedding = $this->calcularEmbeddingGrafo($municipio->id, $variaveis);

            foreach ($anos as $ano) {
                $bar->advance();

                // 7. Busca dados do cubo para este município/ano
                $dados = DadoCubo::where('municipio_id', $municipio->id)
                    ->where('ano_periodo', $ano)
                    ->with('variavel')
                    ->get()
                    ->keyBy(fn($d) => $this->slugVariavel($d->variavel->nome));

                // 8. Monta vetor de features normalizado
                $features = [];
                $metaNorm = [];

                foreach (self::MAPA_FEATURES as $slug => $coluna) {
                    $norm = $normalizadores[$slug] ?? null;
                    $valRaw = $this->extrairValorBruto($slug, $dados, $municipio);

                    if ($valRaw !== null && $norm && ($norm['max'] - $norm['min']) > 0) {
                        $features[$coluna] = ($valRaw - $norm['min']) / ($norm['max'] - $norm['min']);
                    } else {
                        $features[$coluna] = 0.0; // Padrão 0.0 se não houver dados
                    }

                    if ($norm) {
                        $metaNorm[$slug] = ['min' => $norm['min'], 'max' => $norm['max']];
                    }
                }

                // 9. Calcula label de risco por quartil (baseado em score epidemiológico)
                $scoreEpi = $this->calcularScoreEpidemiologico($features);
                $labelAtual = $this->quartilParaScore($scoreEpi, $quartisRisco);

                // 10. Label do próximo ano (target da rede neural)
                $dadosProxAno = DadoCubo::where('municipio_id', $municipio->id)
                    ->where('ano_periodo', (string)((int)$ano + 1))
                    ->with('variavel')
                    ->get()
                    ->keyBy(fn($d) => $this->slugVariavel($d->variavel->nome));

                $featuresProxAno = [];
                foreach (['dengue', 'malaria', 'chagas', 'leishmaniose'] as $slug) {
                    $coluna = self::MAPA_FEATURES[$slug];
                    $norm   = $normalizadores[$slug] ?? null;
                    $valRaw = $this->extrairValorBruto($slug, $dadosProxAno, $municipio);
                    if ($valRaw !== null && $norm && ($norm['max'] - $norm['min']) > 0) {
                        $featuresProxAno[$coluna] = ($valRaw - $norm['min']) / ($norm['max'] - $norm['min']);
                    }
                }
                $scoreEpiProx   = $this->calcularScoreEpidemiologico($featuresProxAno);
                $labelProxAno   = empty($featuresProxAno) ? null : $this->quartilParaScore($scoreEpiProx, $quartisRisco);

                // 11. Persiste no banco
                FeatureMl::updateOrCreate(
                    ['municipio_id' => $municipio->id, 'ano' => $ano],
                    array_merge($features, [
                        'emb_grafo_1'             => $embedding[0] ?? null,
                        'emb_grafo_2'             => $embedding[1] ?? null,
                        'emb_grafo_3'             => $embedding[2] ?? null,
                        'emb_grafo_4'             => $embedding[3] ?? null,
                        'emb_grafo_5'             => $embedding[4] ?? null,
                        'emb_grafo_6'             => $embedding[5] ?? null,
                        'emb_grafo_7'             => $embedding[6] ?? null,
                        'emb_grafo_8'             => $embedding[7] ?? null,
                        'label_risco_quartil'     => $labelAtual,
                        'label_risco_proximo_ano' => $labelProxAno,
                        'versao_pipeline'         => $versao,
                        'metadados_normalizacao'  => $metaNorm,
                    ])
                );

                $salvos++;
            }
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("✅ {$salvos} vetores de features gerados com sucesso!");

        // 12. Exporta CSV se solicitado
        if ($exportCsv) {
            $this->exportarCsv();
        }

        $this->newLine();
        $this->info('📡 Endpoint de exportação disponível em: GET /api/features-ml/export');
        $this->info('🐍 Use o CSV ou endpoint JSON para alimentar seu modelo Python.');

        return self::SUCCESS;
    }

    // ── Helpers de normalização ───────────────────────────────────────────────

    /**
     * Calcula min e max globais de cada variável para normalização min-max consistente.
     */
    private function calcularNormalizadores($variaveis): array
    {
        $norm = [];
        $municipios = Municipio::all();
        $anos = DadoCubo::distinct()->pluck('ano_periodo')->filter(fn($a) => strlen($a) === 4)->values();

        foreach (self::MAPA_FEATURES as $slug => $coluna) {
            $valores = [];
            foreach ($municipios as $m) {
                foreach ($anos as $ano) {
                    $dados = DadoCubo::where('municipio_id', $m->id)
                        ->where('ano_periodo', $ano)
                        ->with('variavel')
                        ->get()
                        ->keyBy(fn($d) => $this->slugVariavel($d->variavel->nome));
                    
                    $val = $this->extrairValorBruto($slug, $dados, $m);
                    if ($val !== null) {
                        $valores[] = $val;
                    }
                }
            }

            if (!empty($valores)) {
                $norm[$slug] = ['min' => min($valores), 'max' => max($valores)];
            } else {
                $norm[$slug] = ['min' => 0.0, 'max' => 1.0];
            }
        }

        return $norm;
    }

    /**
     * Extrai o valor bruto para uma determinada feature com base nos dados do cubo.
     * Consolida e soma taxas epidemiológicas de sub-doenças.
     */
    private function extrairValorBruto(string $featureSlug, $dados, Municipio $municipio): ?float
    {
        $areas = [
            '1501208' => 3758.0, // Baião
            '1502107' => 3081.0, // Cametá
            '1504707' => 1908.0, // Mocajuba
        ];

        switch ($featureSlug) {
            case 'desmatamento':
                $d = $dados->get('incremento_desflorestamento') ?? $dados->get('desmatamento_remanescente') ?? $dados->get('desmatamento');
                return $d ? $d->valor : null;

            case 'focos_calor':
                $d = $dados->get('focos_de_calor') ?? $dados->get('focos_de_calor_queimadas') ?? $dados->get('focos_calor');
                return $d ? $d->valor : null;

            case 'populacao':
                $d = $dados->get('populacao_total') ?? $dados->get('populacao');
                return $d ? $d->valor : null;

            case 'densidade':
                $pop = $dados->get('populacao_total') ?? $dados->get('populacao');
                if (!$pop) return null;
                $area = $areas[$municipio->codigo_ibge] ?? 3000.0;
                return $pop->valor / $area;

            case 'pib':
                $d = $dados->get('pib_per_capita') ?? $dados->get('pib');
                if ($d) return $d->valor;
                $ipm = $dados->get('indice_de_pobreza_multidimensional_ipm') ?? $dados->get('ipm');
                return $ipm ? (1.0 - $ipm->valor) * 15000.0 + 3000.0 : null;

            case 'dengue':
                $d = $dados->get('dengue');
                return $d ? ($d->taxa ?? $d->valor) : null;

            case 'malaria':
                $soma = 0.0;
                $encontrou = false;
                foreach (['malaria_vivax', 'malaria_falciparum', 'malaria_mista_vivax_falci', 'malaria'] as $key) {
                    $d = $dados->get($key);
                    if ($d) {
                        $soma += ($d->taxa ?? $d->valor);
                        $encontrou = true;
                    }
                }
                return $encontrou ? $soma : null;

            case 'chagas':
                $d = $dados->get('doenca_de_chagas') ?? $dados->get('chagas');
                return $d ? ($d->taxa ?? $d->valor) : null;

            case 'leishmaniose':
                $soma = 0.0;
                $encontrou = false;
                foreach (['leishmaniose_cutanea_lta', 'leishmaniose_visceral_calazar', 'leishmaniose'] as $key) {
                    $d = $dados->get($key);
                    if ($d) {
                        $soma += ($d->taxa ?? $d->valor);
                        $encontrou = true;
                    }
                }
                return $encontrou ? $soma : null;
        }

        return null;
    }

    /**
     * Calcula os quartis (Q1, Q2, Q3) do score epidemiológico médio para todos
     * os registros do cubo. Esses limiares são usados para classificar o risco (1–4).
     */
    private function calcularQuartisRisco(): array
    {
        $scores = DadoCubo::whereHas('variavel.eixo', fn($q) => $q->where('slug', 'epidemiologico'))
            ->whereNotNull('taxa')
            ->selectRaw('municipio_id, ano_periodo, AVG(taxa) as score_medio')
            ->groupBy('municipio_id', 'ano_periodo')
            ->pluck('score_medio')
            ->sort()
            ->values();

        if ($scores->isEmpty()) {
            return ['q1' => 25.0, 'q2' => 50.0, 'q3' => 75.0];
        }

        $n = $scores->count();
        return [
            'q1' => (float) $scores->get((int)($n * 0.25)),
            'q2' => (float) $scores->get((int)($n * 0.50)),
            'q3' => (float) $scores->get((int)($n * 0.75)),
        ];
    }

    /**
     * Calcula um score epidemiológico composto como média ponderada das features normalizadas.
     */
    private function calcularScoreEpidemiologico(array $features): float
    {
        $pesos = [
            'feat_dengue_taxa_norm'       => 0.35,
            'feat_malaria_taxa_norm'      => 0.35,
            'feat_chagas_taxa_norm'       => 0.15,
            'feat_leishmaniose_norm'      => 0.15,
        ];

        $score = 0.0;
        $pesoTotal = 0.0;

        foreach ($pesos as $coluna => $peso) {
            if (isset($features[$coluna]) && $features[$coluna] !== null) {
                $score     += $features[$coluna] * $peso;
                $pesoTotal += $peso;
            }
        }

        return $pesoTotal > 0 ? ($score / $pesoTotal) * 100 : 0.0;
    }

    /** Converte um score numérico em quartil de risco (1–4). */
    private function quartilParaScore(float $score, array $quartis): int
    {
        if ($score <= $quartis['q1']) return 1; // Baixo risco
        if ($score <= $quartis['q2']) return 2; // Moderado
        if ($score <= $quartis['q3']) return 3; // Alto
        return 4;                                // Crítico
    }

    // ── Embedding do grafo ────────────────────────────────────────────────────

    /**
     * Calcula um embedding do nó no grafo de correlações.
     */
    private function calcularEmbeddingGrafo(int $municipioId, $variaveis): array
    {
        $correlacoes = CorrelacaoVariavel::where('municipio_id', $municipioId)
            ->orWhereNull('municipio_id')
            ->significativas()
            ->with(['variavelA.eixo', 'variavelB.eixo'])
            ->get();

        if ($correlacoes->isEmpty()) {
            return array_fill(0, 8, 0.0);
        }

        $eixos = ['ambiental', 'social', 'economica', 'epidemiologica'];
        $embedding = [];

        // Dimensões 1–4: força média de correlação com cada eixo (sem lag)
        foreach ($eixos as $eixo) {
            $corrsEixo = $correlacoes->filter(fn($c) =>
                $c->lag_anos === 0 && (
                    ($c->variavelA?->eixo?->slug === $eixo) ||
                    ($c->variavelB?->eixo?->slug === $eixo)
                )
            );
            $embedding[] = $corrsEixo->isNotEmpty()
                ? round($corrsEixo->avg('peso_grafo'), 6)
                : 0.0;
        }

        // Dimensões 5–8: força média por eixo com lag=1 (efeitos defasados)
        foreach ($eixos as $eixo) {
            $corrsLag = $correlacoes->filter(fn($c) =>
                $c->lag_anos === 1 && (
                    ($c->variavelA?->eixo?->slug === $eixo) ||
                    ($c->variavelB?->eixo?->slug === $eixo)
                )
            );
            $embedding[] = $corrsLag->isNotEmpty()
                ? round($corrsLag->avg('peso_grafo'), 6)
                : 0.0;
        }

        return $embedding;
    }

    // ── Exportação CSV ────────────────────────────────────────────────────────

    private function exportarCsv(): void
    {
        $this->info('📄 Exportando dataset para CSV...');

        $path = storage_path('app/features_ml.csv');
        $handle = fopen($path, 'w');

        // Cabeçalho
        $cabecalho = array_merge(
            ['municipio_id', 'municipio_nome', 'ano'],
            FeatureMl::featureNames(),
            ['emb_grafo_1', 'emb_grafo_2', 'emb_grafo_3', 'emb_grafo_4',
             'emb_grafo_5', 'emb_grafo_6', 'emb_grafo_7', 'emb_grafo_8'],
            ['label_risco', 'label_target']
        );
        fputcsv($handle, $cabecalho);

        // Dados
        FeatureMl::with('municipio')->chunk(200, function ($registros) use ($handle) {
            foreach ($registros as $r) {
                $linha = array_merge(
                    [$r->municipio_id, $r->municipio?->nome, $r->ano],
                    $r->toFeatureVector(),
                    $r->toGraphEmbedding(),
                    [$r->label_risco_quartil, $r->label_risco_proximo_ano]
                );
                fputcsv($handle, $linha);
            }
        });

        fclose($handle);
        $this->info("   ✅ CSV exportado para: {$path}");
    }

    /** Converte o nome da variável em um slug padronizado para o mapa de features. */
    private function slugVariavel(string $nome): string
    {
        return str()->of($nome)
            ->lower()
            ->ascii()
            ->replaceMatches('/[^a-z0-9]+/', '_')
            ->trim('_')
            ->toString();
    }
}
