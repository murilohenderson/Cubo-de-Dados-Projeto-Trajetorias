<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Municipio extends Model
{
    protected $table = 'municipios';

    protected $fillable = [
        'codigo_ibge',
        'nome',
        'uf',
        'estado',
    ];

    /**
     * Todos os fatos (dados do cubo) pertencentes a este município.
     */
    public function dados(): HasMany
    {
        return $this->hasMany(DadoCubo::class, 'municipio_id');
    }

    /**
     * Arestas do grafo de correlações associadas a este município.
     */
    public function correlacoes(): HasMany
    {
        return $this->hasMany(CorrelacaoVariavel::class, 'municipio_id');
    }

    /**
     * Vetores de features de ML gerados para este município.
     */
    public function featuresMl(): HasMany
    {
        return $this->hasMany(FeatureMl::class, 'municipio_id');
    }
}
