<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

/**
 * Model FeatureMl — Camada 3 (ML Pipeline)
 *
 * Representa o vetor de features normalizado de um município em um ano específico.
 * Esta tabela é o ponto de integração entre o backend Laravel e os modelos de
 * Machine Learning em Python (PyTorch, TensorFlow, scikit-learn).
 *
 * Fluxo:
 *   dados_cubo → Command gerar:features-ml → features_ml → /api/features-ml/export → Python
 *
 * @property int         $id
 * @property int         $municipio_id
 * @property string      $ano
 * @property float|null  $feat_desmatamento_norm
 * @property float|null  $feat_focos_calor_norm
 * @property float|null  $feat_populacao_norm
 * @property float|null  $feat_densidade_norm
 * @property float|null  $feat_pib_norm
 * @property float|null  $feat_dengue_taxa_norm
 * @property float|null  $feat_malaria_taxa_norm
 * @property float|null  $feat_chagas_taxa_norm
 * @property float|null  $feat_leishmaniose_norm
 * @property int|null    $label_risco_quartil
 * @property int|null    $label_risco_proximo_ano
 */
class FeatureMl extends Model
{
    protected $table = 'features_ml';

    protected $fillable = [
        'municipio_id',
        'ano',
        'feat_desmatamento_norm',
        'feat_focos_calor_norm',
        'feat_populacao_norm',
        'feat_densidade_norm',
        'feat_pib_norm',
        'feat_dengue_taxa_norm',
        'feat_malaria_taxa_norm',
        'feat_chagas_taxa_norm',
        'feat_leishmaniose_norm',
        'emb_grafo_1',
        'emb_grafo_2',
        'emb_grafo_3',
        'emb_grafo_4',
        'emb_grafo_5',
        'emb_grafo_6',
        'emb_grafo_7',
        'emb_grafo_8',
        'label_risco_quartil',
        'label_risco_proximo_ano',
        'versao_pipeline',
        'metadados_normalizacao',
    ];

    protected $casts = [
        'feat_desmatamento_norm'  => 'float',
        'feat_focos_calor_norm'   => 'float',
        'feat_populacao_norm'     => 'float',
        'feat_densidade_norm'     => 'float',
        'feat_pib_norm'           => 'float',
        'feat_dengue_taxa_norm'   => 'float',
        'feat_malaria_taxa_norm'  => 'float',
        'feat_chagas_taxa_norm'   => 'float',
        'feat_leishmaniose_norm'  => 'float',
        'emb_grafo_1'             => 'float',
        'emb_grafo_2'             => 'float',
        'emb_grafo_3'             => 'float',
        'emb_grafo_4'             => 'float',
        'emb_grafo_5'             => 'float',
        'emb_grafo_6'             => 'float',
        'emb_grafo_7'             => 'float',
        'emb_grafo_8'             => 'float',
        'label_risco_quartil'     => 'integer',
        'label_risco_proximo_ano' => 'integer',
        'metadados_normalizacao'  => 'array',
    ];

    // ── Relacionamentos ───────────────────────────────────────────────────────

    public function municipio(): BelongsTo
    {
        return $this->belongsTo(Municipio::class, 'municipio_id');
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    /** Filtra por ano específico. */
    public function scopeDoAno(Builder $query, string $ano): Builder
    {
        return $query->where('ano', $ano);
    }

    /** Filtra por nível de risco (quartil). */
    public function scopeComRisco(Builder $query, int $quartil): Builder
    {
        return $query->where('label_risco_quartil', $quartil);
    }

    /** Apenas registros com embeddings de grafo preenchidos. */
    public function scopeComEmbedding(Builder $query): Builder
    {
        return $query->whereNotNull('emb_grafo_1');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Retorna o vetor de features como array simples (tensor de entrada para a rede neural).
     * Ordem das features é fixa e documentada para garantir reprodutibilidade.
     *
     * @return float[] Array com 9 features normalizadas [0.0–1.0]
     */
    public function toFeatureVector(): array
    {
        return [
            $this->feat_desmatamento_norm ?? 0.0,
            $this->feat_focos_calor_norm  ?? 0.0,
            $this->feat_populacao_norm    ?? 0.0,
            $this->feat_densidade_norm    ?? 0.0,
            $this->feat_pib_norm          ?? 0.0,
            $this->feat_dengue_taxa_norm  ?? 0.0,
            $this->feat_malaria_taxa_norm ?? 0.0,
            $this->feat_chagas_taxa_norm  ?? 0.0,
            $this->feat_leishmaniose_norm ?? 0.0,
        ];
    }

    /**
     * Retorna o embedding do grafo como array (para GNN).
     *
     * @return float[] Array com 8 dimensões de embedding
     */
    public function toGraphEmbedding(): array
    {
        return [
            $this->emb_grafo_1 ?? 0.0,
            $this->emb_grafo_2 ?? 0.0,
            $this->emb_grafo_3 ?? 0.0,
            $this->emb_grafo_4 ?? 0.0,
            $this->emb_grafo_5 ?? 0.0,
            $this->emb_grafo_6 ?? 0.0,
            $this->emb_grafo_7 ?? 0.0,
            $this->emb_grafo_8 ?? 0.0,
        ];
    }

    /**
     * Representação completa para exportação CSV/JSON ao pipeline Python.
     */
    public function toExportRow(): array
    {
        return [
            'municipio_id'    => $this->municipio_id,
            'municipio_nome'  => $this->municipio?->nome,
            'ano'             => $this->ano,
            'features'        => $this->toFeatureVector(),
            'graph_embedding' => $this->toGraphEmbedding(),
            'label'           => $this->label_risco_quartil,
            'label_target'    => $this->label_risco_proximo_ano,
        ];
    }

    /**
     * Nomes das features na mesma ordem de toFeatureVector().
     * Indispensável para interpretabilidade do modelo.
     */
    public static function featureNames(): array
    {
        return [
            'desmatamento_km2_norm',
            'focos_calor_norm',
            'populacao_norm',
            'densidade_hab_km2_norm',
            'pib_per_capita_norm',
            'dengue_taxa_100k_norm',
            'malaria_taxa_100k_norm',
            'chagas_taxa_100k_norm',
            'leishmaniose_taxa_100k_norm',
        ];
    }
}
