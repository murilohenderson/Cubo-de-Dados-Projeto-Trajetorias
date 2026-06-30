<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\GrafoCorrelacaoController;

/*
|--------------------------------------------------------------------------
| API Routes — Projeto Trajetórias (INPE/Fiocruz)
|--------------------------------------------------------------------------
|
| Camada 2 (Graph Layer): Grafo de correlações entre variáveis
| Camada 3 (ML Pipeline): Vetores de features para rede neural
|
| Todos os endpoints retornam JSON e são consumidos pelo frontend
| (D3.js force-directed graph) e pelo pipeline Python (PyTorch/TensorFlow).
|
*/

Route::prefix('v1')->group(function () {
    /* 
    |--------------------------------------------------------------------------
    | Endpoints de correlações (Grafo) e ML temporariamente desabilitados.
    | Serão reativados em uma branch separada.
    |--------------------------------------------------------------------------
    
    // ── Camada 2: Graph Layer ─────────────────────────────────────────────────

    Route::prefix('grafo')->group(function () {
        Route::get('/', [GrafoCorrelacaoController::class, 'grafoCompleto'])
             ->name('api.grafo.completo');

        Route::get('/cruzamento/{tipo}', [GrafoCorrelacaoController::class, 'grafoPorCruzamento'])
             ->name('api.grafo.cruzamento');

        Route::get('/{municipio_id}', [GrafoCorrelacaoController::class, 'grafoPorMunicipio'])
             ->name('api.grafo.municipio')
             ->whereNumber('municipio_id');
    });

    // ── Camada 3: ML Pipeline ─────────────────────────────────────────────────

    Route::prefix('features-ml')->group(function () {
        Route::get('/schema', [GrafoCorrelacaoController::class, 'schema'])
             ->name('api.features.schema');

        Route::get('/export', [GrafoCorrelacaoController::class, 'exportarFeatures'])
             ->name('api.features.export');

        Route::get('/{municipio_id}', [GrafoCorrelacaoController::class, 'featuresPorMunicipio'])
             ->name('api.features.municipio')
             ->whereNumber('municipio_id');
    });
    */
});
