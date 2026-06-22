<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Camada 2 da Arquitetura Híbrida: Graph Layer
 *
 * Cada linha representa uma ARESTA no grafo de correlações entre variáveis.
 * O conjunto completo desta tabela forma o "Cubo de Grafos" embutido no SQL,
 * capturando relações causais e temporais entre os 4 eixos do Projeto Trajetórias.
 *
 * Estrutura do grafo:
 *   Nós     → variaveis (ex: "Focos de Calor", "Casos de Dengue")
 *   Arestas → correlacoes_variaveis (ex: Focos ↔ Dengue, lag 2 anos)
 *   Peso    → peso_grafo = |coef_pearson| × (1 - p_valor)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('correlacoes_variaveis', function (Blueprint $table) {
            $table->id();

            // ── Nós da aresta (variáveis correlacionadas) ────────────────────
            $table->foreignId('variavel_a_id')
                  ->constrained('variaveis')
                  ->cascadeOnDelete()
                  ->comment('Variável de origem da correlação (ex: Focos de Calor)');

            $table->foreignId('variavel_b_id')
                  ->constrained('variaveis')
                  ->cascadeOnDelete()
                  ->comment('Variável de destino da correlação (ex: Casos de Dengue)');

            // ── Contexto espacial ─────────────────────────────────────────────
            $table->foreignId('municipio_id')
                  ->nullable()
                  ->constrained('municipios')
                  ->nullOnDelete()
                  ->comment('NULL = correlação agregada regional; preenchido = correlação local');

            // ── Contexto temporal ─────────────────────────────────────────────
            $table->tinyInteger('lag_anos')
                  ->default(0)
                  ->comment('Defasagem temporal em anos: 0=contemporânea, 1=T+1, 2=T+2...');

            $table->string('periodo_inicio', 4)
                  ->nullable()
                  ->comment('Ano inicial da janela temporal analisada (ex: 2010)');

            $table->string('periodo_fim', 4)
                  ->nullable()
                  ->comment('Ano final da janela temporal analisada (ex: 2023)');

            // ── Coeficientes estatísticos ─────────────────────────────────────
            $table->double('coef_pearson')
                  ->nullable()
                  ->comment('Correlação de Pearson (-1 a +1). Para dados lineares normais.');

            $table->double('coef_spearman')
                  ->nullable()
                  ->comment('Correlação de Spearman (-1 a +1). Robusta para dados não-paramétricos.');

            $table->double('p_valor')
                  ->nullable()
                  ->comment('P-valor do teste de significância. < 0.05 = estatisticamente significativo.');

            $table->integer('n_amostras')
                  ->nullable()
                  ->comment('Número de pares de observações usados no cálculo (anos × municípios).');

            // ── Semântica da relação ──────────────────────────────────────────
            $table->string('direcao', 20)
                  ->default('nao_determinada')
                  ->comment('positiva | negativa | nao_linear | nao_determinada');

            $table->double('peso_grafo')
                  ->nullable()
                  ->comment('Peso da aresta para algoritmos de grafo: |coef_pearson| × (1 - p_valor). Maior = relação mais forte e significativa.');

            $table->string('tipo_relacao', 80)
                  ->nullable()
                  ->comment('Cruzamento de eixos: ex: ambiental→epidemiologico');

            // ── Metadados extras ──────────────────────────────────────────────
            $table->json('metadados')
                  ->nullable()
                  ->comment('Dados adicionais: método de cálculo, versão, notas científicas.');

            $table->timestamps();

            // ── Índices de performance ────────────────────────────────────────
            // Busca de todas as correlações de um par de variáveis
            $table->index(['variavel_a_id', 'variavel_b_id'], 'idx_correlacao_par');
            // Busca de correlações por município
            $table->index(['municipio_id'], 'idx_correlacao_mun');
            // Filtro por lag temporal (ex: todos os efeitos com 2 anos de defasagem)
            $table->index(['lag_anos'], 'idx_correlacao_lag');
            // Busca de correlações significativas (p_valor < 0.05)
            $table->index(['p_valor'], 'idx_correlacao_p');
            // Grafo completo de um município em um período
            $table->index(['municipio_id', 'periodo_inicio', 'periodo_fim'], 'idx_correlacao_municipio_periodo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('correlacoes_variaveis');
    }
};
