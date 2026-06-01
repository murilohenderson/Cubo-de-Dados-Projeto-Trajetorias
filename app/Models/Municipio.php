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
}
