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

    // ── Camada 2: Graph Layer ─────────────────────────────────────────────────

    Route::prefix('grafo')->group(function () {
        /**
         * GET /api/v1/grafo
         * Grafo completo de correlações (todos os municípios).
         * Params: ?lag=0, ?p_max=0.05, ?municipio_id=
         */
        Route::get('/', [GrafoCorrelacaoController::class, 'grafoCompleto'])
             ->name('api.grafo.completo');

        /**
         * GET /api/v1/grafo/cruzamento/{tipo}
         * Correlações de um cruzamento específico de eixos.
         * Exemplo: /api/v1/grafo/cruzamento/ambiental-epidemiologico
         * IMPORTANTE: deve vir ANTES de /{municipio_id} para evitar colisão de rota.
         */
        Route::get('/cruzamento/{tipo}', [GrafoCorrelacaoController::class, 'grafoPorCruzamento'])
             ->name('api.grafo.cruzamento');

        /**
         * GET /api/v1/grafo/{municipio_id}
         * Grafo de correlações de um município específico.
         */
        Route::get('/{municipio_id}', [GrafoCorrelacaoController::class, 'grafoPorMunicipio'])
             ->name('api.grafo.municipio')
             ->whereNumber('municipio_id');
    });

    // ── Camada 3: ML Pipeline ─────────────────────────────────────────────────

    Route::prefix('features-ml')->group(function () {
        /**
         * GET /api/v1/features-ml/schema
         * Schema e documentação do vetor de features (para reprodutibilidade do modelo).
         * IMPORTANTE: deve vir ANTES de /{municipio_id}.
         */
        Route::get('/schema', [GrafoCorrelacaoController::class, 'schema'])
             ->name('api.features.schema');

        /**
         * GET /api/v1/features-ml/export
         * Exporta o dataset completo para Python.
         * Params: ?ano=2022, ?formato=pytorch
         */
        Route::get('/export', [GrafoCorrelacaoController::class, 'exportarFeatures'])
             ->name('api.features.export');

        /**
         * GET /api/v1/features-ml/{municipio_id}
         * Série histórica de vetores de features de um município.
         */
        Route::get('/{municipio_id}', [GrafoCorrelacaoController::class, 'featuresPorMunicipio'])
             ->name('api.features.municipio')
             ->whereNumber('municipio_id');
    });

});
