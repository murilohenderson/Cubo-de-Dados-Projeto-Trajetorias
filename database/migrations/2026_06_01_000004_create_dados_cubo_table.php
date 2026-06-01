<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dados_cubo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('municipio_id')
                  ->constrained('municipios')
                  ->cascadeOnDelete();
            $table->foreignId('variavel_id')
                  ->constrained('variaveis')
                  ->cascadeOnDelete();
            // Armazena ano simples ("2020") ou período ("2015-2019")
            $table->string('ano_periodo', 20)->comment('Ex: 2020 ou 2015-2019');
            // Valor principal da variável (desmatamento km², população, PIB, nº de casos)
            $table->double('valor')->default(0);
            // Taxa de incidência (por 100mil hab) — usado pelo eixo epidemiológico
            $table->double('taxa')->nullable();
            // Metadados extras em JSON: zona_residencial, fonte, etc.
            $table->json('detalhes')->nullable();
            $table->timestamps();

            // Índices para performance nas consultas de séries históricas
            $table->index(['variavel_id', 'municipio_id']);
            $table->index(['variavel_id', 'ano_periodo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dados_cubo');
    }
};
