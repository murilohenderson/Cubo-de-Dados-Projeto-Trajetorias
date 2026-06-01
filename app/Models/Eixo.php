<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Eixo extends Model
{
    protected $table = 'eixos';

    protected $fillable = [
        'nome',
        'slug',
        'icone',
        'cor_primaria',
        'descricao',
    ];

    /**
     * Variáveis (métricas/indicadores) que pertencem a este eixo.
     */
    public function variaveis(): HasMany
    {
        return $this->hasMany(Variavel::class, 'eixo_id');
    }

    /**
     * Retorna os nomes das variáveis deste eixo como array simples.
     */
    public function nomesVariaveis(): array
    {
        return $this->variaveis()->pluck('nome')->toArray();
    }
}
