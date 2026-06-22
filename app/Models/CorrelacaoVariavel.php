<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

/**
 * Model CorrelacaoVariavel — Camada 2 (Graph Layer)
 *
 * Representa uma ARESTA do grafo de correlações entre variáveis do cubo.
 * O conjunto de todas as instâncias forma o grafo de dependências do Projeto Trajetórias,
 * que pode ser exportado como lista de adjacência para bibliotecas de grafos (NetworkX, igraph)
 * ou para treinamento de GNNs (PyTorch Geometric).
 *
 * @property int         $id
 * @property int         $variavel_a_id
 * @property int         $variavel_b_id
 * @property int|null    $municipio_id
 * @property int         $lag_anos
 * @property string|null $periodo_inicio
 * @property string|null $periodo_fim
 * @property float|null  $coef_pearson
 * @property float|null  $coef_spearman
 * @property float|null  $p_valor
 * @property int|null    $n_amostras
 * @property string      $direcao
 * @property float|null  $peso_grafo
 * @property string|null $tipo_relacao
 * @property array|null  $metadados
 */
class CorrelacaoVariavel extends Model
{
    protected $table = 'correlacoes_variaveis';

    protected $fillable = [
        'variavel_a_id',
        'variavel_b_id',
        'municipio_id',
        'lag_anos',
        'periodo_inicio',
        'periodo_fim',
        'coef_pearson',
        'coef_spearman',
        'p_valor',
        'n_amostras',
        'direcao',
        'peso_grafo',
        'tipo_relacao',
        'metadados',
    ];

    protected $casts = [
        'coef_pearson'  => 'float',
        'coef_spearman' => 'float',
        'p_valor'       => 'float',
        'peso_grafo'    => 'float',
        'lag_anos'      => 'integer',
        'n_amostras'    => 'integer',
        'metadados'     => 'array',
    ];

    // ── Relacionamentos ───────────────────────────────────────────────────────

    /** Variável de origem da aresta. */
    public function variavelA(): BelongsTo
    {
        return $this->belongsTo(Variavel::class, 'variavel_a_id');
    }

    /** Variável de destino da aresta. */
    public function variavelB(): BelongsTo
    {
        return $this->belongsTo(Variavel::class, 'variavel_b_id');
    }

    /** Município ao qual a correlação é restrita (null = regional). */
    public function municipio(): BelongsTo
    {
        return $this->belongsTo(Municipio::class, 'municipio_id');
    }

    // ── Scopes (filtros reutilizáveis) ────────────────────────────────────────

    /**
     * Apenas correlações estatisticamente significativas (p < threshold).
     * Padrão científico: p < 0.05
     */
    public function scopeSignificativas(Builder $query, float $threshold = 0.05): Builder
    {
        return $query->where('p_valor', '<', $threshold);
    }

    /**
     * Filtra por lag temporal específico (ex: efeitos com 2 anos de defasagem).
     */
    public function scopeComLag(Builder $query, int $lag): Builder
    {
        return $query->where('lag_anos', $lag);
    }

    /**
     * Filtra por município específico, incluindo correlações regionais (municipio_id = null).
     */
    public function scopeParaMunicipio(Builder $query, int $municipioId): Builder
    {
        return $query->where(function (Builder $q) use ($municipioId) {
            $q->where('municipio_id', $municipioId)
              ->orWhereNull('municipio_id');
        });
    }

    /**
     * Retorna apenas correlações positivas (coef_pearson > 0).
     */
    public function scopePositivas(Builder $query): Builder
    {
        return $query->where('direcao', 'positiva');
    }

    /**
     * Retorna apenas correlações negativas.
     */
    public function scopeNegativas(Builder $query): Builder
    {
        return $query->where('direcao', 'negativa');
    }

    /**
     * Filtra por cruzamento de eixos (ex: 'ambiental→epidemiologico').
     */
    public function scopeDoTipo(Builder $query, string $tipo): Builder
    {
        return $query->where('tipo_relacao', $tipo);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Retorna true se a correlação é estatisticamente significativa (p < 0.05).
     */
    public function isSignificativa(float $threshold = 0.05): bool
    {
        return $this->p_valor !== null && $this->p_valor < $threshold;
    }

    /**
     * Retorna o coeficiente mais robusto disponível.
     * Prefere Spearman (não paramétrico, ideal para dados ambientais/epidemiológicos).
     */
    public function melhorCoeficiente(): ?float
    {
        return $this->coef_spearman ?? $this->coef_pearson;
    }

    /**
     * Serializa a aresta no formato de lista de adjacência (para exportação a NetworkX/igraph).
     *
     * @return array{source: int, target: int, weight: float, lag: int, p_value: float}
     */
    public function toEdge(): array
    {
        return [
            'source'       => $this->variavel_a_id,
            'target'       => $this->variavel_b_id,
            'weight'       => (float) ($this->peso_grafo ?? 0.0),
            'lag'          => $this->lag_anos,
            'p_value'      => (float) ($this->p_valor ?? 1.0),
            'pearson'      => (float) ($this->coef_pearson ?? 0.0),
            'spearman'     => (float) ($this->coef_spearman ?? 0.0),
            'direcao'      => $this->direcao,
            'tipo_relacao' => $this->tipo_relacao,
            'municipio_id' => $this->municipio_id,
        ];
    }
}
