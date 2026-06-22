<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CorrelacaoVariavel;
use App\Models\FeatureMl;
use App\Models\Municipio;
use App\Models\Variavel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Controller de API para a Camada 2 (Graph Layer) e Camada 3 (ML Pipeline).
 *
 * Endpoints:
 *   GET /api/grafo                     → Grafo completo (todos os municípios)
 *   GET /api/grafo/{municipio_id}      → Grafo de um município
 *   GET /api/grafo/cruzamento/{tipo}   → Correlações de um cruzamento de eixos
 *   GET /api/features-ml/export        → Dataset completo para Python (JSON)
 *   GET /api/features-ml/{municipio}   → Vetores de um município
 *   GET /api/features-ml/schema        → Schema e nomes das features
 */
class GrafoCorrelacaoController extends Controller
{
    /**
     * GET /api/grafo
     *
     * Retorna o grafo completo de correlações em formato de lista de adjacência
     * (nós + arestas), compatível com D3.js force-directed graph e NetworkX.
     *
     * Query params:
     *   ?lag=0         → Filtra por lag temporal (padrão: todos)
     *   ?p_max=0.05    → P-valor máximo (padrão: 0.05)
     *   ?municipio_id= → Filtra por município (opcional)
     */
    public function grafoCompleto(Request $request): JsonResponse
    {
        $lag         = $request->query('lag');
        $pMax        = (float) $request->query('p_max', 0.05);
        $municipioId = $request->query('municipio_id');

        $cacheKey = "grafo_completo:{$lag}:{$pMax}:{$municipioId}";

        $dados = Cache::remember($cacheKey, now()->addMinutes(60), function () use ($lag, $pMax, $municipioId) {
            $query = CorrelacaoVariavel::with([
                'variavelA:id,nome,eixo_id',
                'variavelA.eixo:id,nome,slug,cor_primaria',
                'variavelB:id,nome,eixo_id',
                'variavelB.eixo:id,nome,slug,cor_primaria',
                'municipio:id,nome',
            ])
            ->where('p_valor', '<', $pMax);

            if ($lag !== null) {
                $query->where('lag_anos', (int)$lag);
            }

            if ($municipioId) {
                $query->where(function ($q) use ($municipioId) {
                    $q->where('municipio_id', (int)$municipioId)
                      ->orWhereNull('municipio_id');
                });
            }

            $arestas = $query->get();

            // Constrói lista de nós únicos
            $nos = collect();
            foreach ($arestas as $a) {
                if ($a->variavelA) {
                    $nos->put($a->variavel_a_id, [
                        'id'          => $a->variavel_a_id,
                        'label'       => $a->variavelA->nome,
                        'eixo'        => $a->variavelA->eixo?->slug,
                        'eixo_nome'   => $a->variavelA->eixo?->nome,
                        'cor'         => $a->variavelA->eixo?->cor_primaria ?? '#6B7280',
                        'grupo'       => $a->variavelA->eixo_id,
                    ]);
                }
                if ($a->variavelB) {
                    $nos->put($a->variavel_b_id, [
                        'id'          => $a->variavel_b_id,
                        'label'       => $a->variavelB->nome,
                        'eixo'        => $a->variavelB->eixo?->slug,
                        'eixo_nome'   => $a->variavelB->eixo?->nome,
                        'cor'         => $a->variavelB->eixo?->cor_primaria ?? '#6B7280',
                        'grupo'       => $a->variavelB->eixo_id,
                    ]);
                }
            }

            return [
                'nos'    => $nos->values(),
                'arestas' => $arestas->map(fn($a) => $a->toEdge()),
                'meta'   => [
                    'total_nos'    => $nos->count(),
                    'total_arestas' => $arestas->count(),
                    'p_max'        => $pMax,
                    'lag_filtro'   => $lag,
                ],
            ];
        });

        return response()->json($dados);
    }

    /**
     * GET /api/grafo/{municipio_id}
     *
     * Retorna o grafo de correlações específico de um município,
     * incluindo as correlações regionais (municipio_id = null).
     */
    public function grafoPorMunicipio(int $municipioId): JsonResponse
    {
        $municipio = Municipio::findOrFail($municipioId);

        $cacheKey = "grafo_municipio:{$municipioId}";

        $dados = Cache::remember($cacheKey, now()->addMinutes(30), function () use ($municipioId, $municipio) {
            $arestas = CorrelacaoVariavel::with([
                'variavelA:id,nome,eixo_id',
                'variavelA.eixo:id,nome,slug,cor_primaria,icone',
                'variavelB:id,nome,eixo_id',
                'variavelB.eixo:id,nome,slug,cor_primaria,icone',
            ])
            ->significativas()
            ->where(function ($q) use ($municipioId) {
                $q->where('municipio_id', $municipioId)
                  ->orWhereNull('municipio_id');
            })
            ->orderByDesc('peso_grafo')
            ->get();

            // Top 5 correlações mais fortes (para destaque no dashboard)
            $topCorrelacoes = $arestas->sortByDesc('peso_grafo')->take(5)->map(fn($a) => [
                'variavel_a'  => $a->variavelA?->nome,
                'variavel_b'  => $a->variavelB?->nome,
                'pearson'     => $a->coef_pearson,
                'spearman'    => $a->coef_spearman,
                'p_valor'     => $a->p_valor,
                'peso'        => $a->peso_grafo,
                'lag'         => $a->lag_anos,
                'direcao'     => $a->direcao,
                'tipo'        => $a->tipo_relacao,
                'interpretacao' => $this->interpretar($a),
            ]);

            // Nós únicos
            $nos = collect();
            foreach ($arestas as $a) {
                if ($a->variavelA) {
                    $nos->put($a->variavel_a_id, [
                        'id'    => $a->variavel_a_id,
                        'label' => $a->variavelA->nome,
                        'eixo'  => $a->variavelA->eixo?->slug,
                        'cor'   => $a->variavelA->eixo?->cor_primaria ?? '#6B7280',
                        'icone' => $a->variavelA->eixo?->icone,
                        'grupo' => $a->variavelA->eixo_id,
                    ]);
                }
                if ($a->variavelB) {
                    $nos->put($a->variavel_b_id, [
                        'id'    => $a->variavel_b_id,
                        'label' => $a->variavelB->nome,
                        'eixo'  => $a->variavelB->eixo?->slug,
                        'cor'   => $a->variavelB->eixo?->cor_primaria ?? '#6B7280',
                        'icone' => $a->variavelB->eixo?->icone,
                        'grupo' => $a->variavelB->eixo_id,
                    ]);
                }
            }

            return [
                'municipio'      => ['id' => $municipio->id, 'nome' => $municipio->nome],
                'nos'            => $nos->values(),
                'arestas'        => $arestas->map(fn($a) => $a->toEdge()),
                'top_correlacoes' => $topCorrelacoes->values(),
                'meta'           => [
                    'total_nos'    => $nos->count(),
                    'total_arestas' => $arestas->count(),
                    'lags_disponiveis' => $arestas->pluck('lag_anos')->unique()->sort()->values(),
                ],
            ];
        });

        return response()->json($dados);
    }

    /**
     * GET /api/grafo/cruzamento/{tipo}
     *
     * Retorna correlações de um cruzamento específico de eixos.
     * Exemplo: /api/grafo/cruzamento/ambiental-epidemiologico
     */
    public function grafoPorCruzamento(string $tipo): JsonResponse
    {
        // Normaliza: "ambiental-epidemiologico" → "ambiental→epidemiologico"
        $tipoNorm = str_replace('-', '→', $tipo);

        $arestas = CorrelacaoVariavel::with([
            'variavelA:id,nome',
            'variavelB:id,nome',
            'municipio:id,nome',
        ])
        ->significativas()
        ->where('tipo_relacao', $tipoNorm)
        ->orderByDesc('peso_grafo')
        ->get();

        return response()->json([
            'cruzamento' => $tipoNorm,
            'arestas'    => $arestas->map(fn($a) => array_merge($a->toEdge(), [
                'municipio_nome' => $a->municipio?->nome ?? 'Regional',
            ])),
            'resumo'     => [
                'total'         => $arestas->count(),
                'pearson_medio' => round($arestas->avg('coef_pearson'), 4),
                'maior_peso'    => round($arestas->max('peso_grafo'), 4),
                'lags'          => $arestas->pluck('lag_anos')->unique()->sort()->values(),
            ],
        ]);
    }

    /**
     * GET /api/features-ml/export
     *
     * Exporta o dataset completo em formato JSON pronto para consumo em Python.
     * Inclui nomes das features, embeddings e labels.
     *
     * Query params:
     *   ?ano=2022          → Filtra por ano
     *   ?formato=pytorch   → Formato otimizado para PyTorch (lista de tensores)
     */
    public function exportarFeatures(Request $request): JsonResponse
    {
        $ano     = $request->query('ano');
        $formato = $request->query('formato', 'padrao');

        $query = FeatureMl::with('municipio:id,nome,codigo_ibge');

        if ($ano) {
            $query->where('ano', $ano);
        }

        $registros = $query->orderBy('municipio_id')->orderBy('ano')->get();

        $dataset = $registros->map(fn($r) => $r->toExportRow());

        if ($formato === 'pytorch') {
            // Formato otimizado: separar X (features + embedding), y (labels)
            return response()->json([
                'feature_names'  => array_merge(FeatureMl::featureNames(), [
                    'emb_grafo_1','emb_grafo_2','emb_grafo_3','emb_grafo_4',
                    'emb_grafo_5','emb_grafo_6','emb_grafo_7','emb_grafo_8',
                ]),
                'X' => $dataset->map(fn($r) => array_merge($r['features'], $r['graph_embedding'])),
                'y' => $dataset->pluck('label'),
                'y_next' => $dataset->pluck('label_target'),
                'meta'  => $dataset->map(fn($r) => [
                    'municipio_id'   => $r['municipio_id'],
                    'municipio_nome' => $r['municipio_nome'],
                    'ano'            => $r['ano'],
                ]),
            ]);
        }

        return response()->json([
            'feature_names' => FeatureMl::featureNames(),
            'total'         => $dataset->count(),
            'dataset'       => $dataset,
        ]);
    }

    /**
     * GET /api/features-ml/{municipio_id}
     *
     * Retorna a série histórica de vetores de features de um município.
     */
    public function featuresPorMunicipio(int $municipioId): JsonResponse
    {
        $municipio = Municipio::findOrFail($municipioId);

        $features = FeatureMl::where('municipio_id', $municipioId)
            ->orderBy('ano')
            ->get();

        return response()->json([
            'municipio'     => ['id' => $municipio->id, 'nome' => $municipio->nome],
            'feature_names' => FeatureMl::featureNames(),
            'serie'         => $features->map(fn($f) => [
                'ano'             => $f->ano,
                'features'        => $f->toFeatureVector(),
                'graph_embedding' => $f->toGraphEmbedding(),
                'label_risco'     => $f->label_risco_quartil,
                'label_target'    => $f->label_risco_proximo_ano,
            ]),
        ]);
    }

    /**
     * GET /api/features-ml/schema
     *
     * Retorna o schema completo do vetor de features para documentação do modelo ML.
     */
    public function schema(): JsonResponse
    {
        return response()->json([
            'versao'        => '1.0',
            'feature_names' => FeatureMl::featureNames(),
            'descricao'     => [
                'desmatamento_km2_norm'       => 'Incremento de desmatamento (km²) — normalizado min-max global',
                'focos_calor_norm'            => 'Focos de calor (INPE/PRODES) — normalizado min-max global',
                'populacao_norm'              => 'População total (IBGE) — normalizado min-max global',
                'densidade_hab_km2_norm'      => 'Densidade demográfica (hab/km²) — normalizado min-max global',
                'pib_per_capita_norm'         => 'PIB per capita (R$) — normalizado min-max global',
                'dengue_taxa_100k_norm'       => 'Taxa de incidência de Dengue por 100k hab — normalizado',
                'malaria_taxa_100k_norm'      => 'Taxa de incidência de Malária por 100k hab — normalizado',
                'chagas_taxa_100k_norm'       => 'Taxa de incidência de Doença de Chagas por 100k hab — normalizado',
                'leishmaniose_taxa_100k_norm' => 'Taxa de incidência de Leishmaniose por 100k hab — normalizado',
            ],
            'embedding_dim'  => 8,
            'embedding_desc' => 'Representação do município no grafo de correlações (Node2Vec simplificado, 4 eixos × 2 lags)',
            'label_classes'  => [
                1 => 'Baixo risco (Q1)',
                2 => 'Risco moderado (Q2)',
                3 => 'Alto risco (Q3)',
                4 => 'Risco crítico (Q4)',
            ],
        ]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /** Gera uma interpretação textual automática da correlação para o dashboard. */
    private function interpretar(CorrelacaoVariavel $c): string
    {
        $nomeA = $c->variavelA?->nome ?? 'Variável A';
        $nomeB = $c->variavelB?->nome ?? 'Variável B';
        $dir   = $c->direcao === 'positiva' ? 'aumenta' : 'diminui';
        $lag   = $c->lag_anos > 0 ? " (com defasagem de {$c->lag_anos} ano(s))" : '';
        $force = $c->coef_pearson ? abs(round($c->coef_pearson, 2)) : '?';

        return "Quando {$nomeA} aumenta, {$nomeB} {$dir} (r={$force}){$lag}.";
    }
}
