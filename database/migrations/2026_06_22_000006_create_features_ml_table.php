<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Camada 3 da Arquitetura Híbrida: ML Pipeline
 *
 * Esta tabela armazena o vetor de features normalizado de cada município por ano,
 * pronto para alimentar modelos de Machine Learning (GNN, LSTM, Random Forest).
 *
 * Fluxo de dados:
 *   dados_cubo → [Command: gerar:features-ml] → features_ml → export CSV/JSON → Python/TensorFlow
 *
 * Estrutura de features:
 *   - Valores normalizados (min-max por variável, range 0–1)
 *   - Labels de risco para treinamento supervisionado (quartil 1–4)
 *   - Embeddings do grafo de correlações (produzidos pelo Command calcular:correlacoes)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('features_ml', function (Blueprint $table) {
            $table->id();

            // ── Contexto espaço-temporal ──────────────────────────────────────
            $table->foreignId('municipio_id')
                  ->constrained('municipios')
                  ->cascadeOnDelete()
                  ->comment('Município ao qual o vetor de features pertence');

            $table->string('ano', 4)
                  ->comment('Ano de referência das features (ex: 2020)');

            // ── Features do Eixo Ambiental (normalizadas 0–1) ─────────────────
            $table->double('feat_desmatamento_norm')
                  ->nullable()
                  ->comment('Incremento de desmatamento km² — normalizado min-max');

            $table->double('feat_focos_calor_norm')
                  ->nullable()
                  ->comment('Número de focos de calor — normalizado min-max');

            // ── Features do Eixo Social/Demográfico (normalizadas 0–1) ─────────
            $table->double('feat_populacao_norm')
                  ->nullable()
                  ->comment('População total — normalizado min-max');

            $table->double('feat_densidade_norm')
                  ->nullable()
                  ->comment('Densidade demográfica (hab/km²) — normalizado min-max');

            // ── Features do Eixo Econômico (normalizadas 0–1) ─────────────────
            $table->double('feat_pib_norm')
                  ->nullable()
                  ->comment('PIB per capita (R$) — normalizado min-max');

            // ── Features do Eixo Epidemiológico (normalizadas 0–1) ────────────
            $table->double('feat_dengue_taxa_norm')
                  ->nullable()
                  ->comment('Taxa de incidência de Dengue por 100k hab — normalizado');

            $table->double('feat_malaria_taxa_norm')
                  ->nullable()
                  ->comment('Taxa de incidência de Malária por 100k hab — normalizado');

            $table->double('feat_chagas_taxa_norm')
                  ->nullable()
                  ->comment('Taxa de incidência de Doença de Chagas por 100k hab — normalizado');

            $table->double('feat_leishmaniose_norm')
                  ->nullable()
                  ->comment('Taxa de incidência de Leishmaniose por 100k hab — normalizado');

            // ── Embeddings do Grafo (Camada 2) ────────────────────────────────
            // Representação vetorial da posição do município no grafo de correlações.
            // Calculado via Node2Vec ou Graph2Vec sobre correlacoes_variaveis.
            // Dimensionalidade configurável (padrão: 8 dims para sqlite, 16+ para postgres).
            $table->double('emb_grafo_1')->nullable()->comment('Dimensão 1 do embedding do nó no grafo de correlações');
            $table->double('emb_grafo_2')->nullable()->comment('Dimensão 2 do embedding do nó no grafo de correlações');
            $table->double('emb_grafo_3')->nullable()->comment('Dimensão 3 do embedding do nó no grafo de correlações');
            $table->double('emb_grafo_4')->nullable()->comment('Dimensão 4 do embedding do nó no grafo de correlações');
            $table->double('emb_grafo_5')->nullable()->comment('Dimensão 5 do embedding do nó no grafo de correlações');
            $table->double('emb_grafo_6')->nullable()->comment('Dimensão 6 do embedding do nó no grafo de correlações');
            $table->double('emb_grafo_7')->nullable()->comment('Dimensão 7 do embedding do nó no grafo de correlações');
            $table->double('emb_grafo_8')->nullable()->comment('Dimensão 8 do embedding do nó no grafo de correlações');

            // ── Labels para Aprendizado Supervisionado ────────────────────────
            $table->tinyInteger('label_risco_quartil')
                  ->nullable()
                  ->comment('Nível de risco atual (1=baixo, 2=moderado, 3=alto, 4=crítico) — calculado por quartil');

            $table->tinyInteger('label_risco_proximo_ano')
                  ->nullable()
                  ->comment('TARGET de predição: nível de risco do próximo ano (para treinamento supervisionado)');

            // ── Metadados de rastreabilidade do pipeline ──────────────────────
            $table->string('versao_pipeline', 20)
                  ->default('1.0')
                  ->comment('Versão do comando que gerou este vetor (para reprodutibilidade)');

            $table->json('metadados_normalizacao')
                  ->nullable()
                  ->comment('Min/max originais usados na normalização — necessário para inverter a transformação');

            $table->timestamps();

            // ── Índices ───────────────────────────────────────────────────────
            $table->unique(['municipio_id', 'ano'], 'idx_features_municipio_ano');
            $table->index(['ano'], 'idx_features_ano');
            $table->index(['label_risco_quartil'], 'idx_features_risco');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('features_ml');
    }
};
