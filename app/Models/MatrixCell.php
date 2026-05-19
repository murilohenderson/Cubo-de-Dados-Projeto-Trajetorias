<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MatrixCell extends Model
{
    protected $fillable = ['indicator_id', 'region_id', 'density_level', 'correlation_text'];

    public function indicator()
    {
        return $this->belongsTo(Indicator::class);
    }

    public function region()
    {
        return $this->belongsTo(Region::class);
    }
}
