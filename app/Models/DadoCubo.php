<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DadoCubo extends Model
{
    protected $table = 'dados_cubo';

    protected $fillable = [
        'municipio_id',
        'variavel_id',
        'ano_periodo',
        'valor',
        'taxa',
        'detalhes',
    ];

    /**
     * Cast automático da coluna JSON para array PHP.
     */
    protected $casts = [
        'detalhes' => 'array',
        'valor'    => 'float',
        'taxa'     => 'float',
    ];

    /**
     * Município ao qual este dado pertence.
     */
    public function municipio(): BelongsTo
    {
        return $this->belongsTo(Municipio::class, 'municipio_id');
    }

    /**
     * Variável/indicador ao qual este dado pertence.
     */
    public function variavel(): BelongsTo
    {
        return $this->belongsTo(Variavel::class, 'variavel_id');
    }

    /**
     * Scope para filtrar por zona residencial (epidemiológica).
     * Exemplo de uso: DadoCubo::zonaResidencial('total')->get()
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $zona  'rural' | 'urban' | 'total'
     */
    public function scopeZonaResidencial($query, string $zona)
    {
        return $query->whereJsonContains('detalhes->zona_residencial', $zona);
    }

    /**
     * Scope para filtrar por variável (id ou nome).
     */
    public function scopeParaVariavel($query, int $variavelId)
    {
        return $query->where('variavel_id', $variavelId);
    }
}
