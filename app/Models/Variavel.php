<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Variavel extends Model
{
    protected $table = 'variaveis';

    protected $fillable = [
        'eixo_id',
        'nome',
        'unidade',
        'descricao',
    ];

    /**
     * O eixo/dimensão ao qual esta variável pertence.
     */
    public function eixo(): BelongsTo
    {
        return $this->belongsTo(Eixo::class, 'eixo_id');
    }

    /**
     * Todos os registros de fatos associados a esta variável.
     */
    public function dados(): HasMany
    {
        return $this->hasMany(DadoCubo::class, 'variavel_id');
    }
}
