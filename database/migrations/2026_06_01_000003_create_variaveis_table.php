<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('variaveis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('eixo_id')
                  ->constrained('eixos')
                  ->cascadeOnDelete();
            $table->string('nome', 200)->comment('Ex: Focos de Calor, PIB per capita, Vivax');
            $table->string('unidade', 60)->nullable()->comment('Ex: km², R$, Ocorrencias, Habitantes');
            $table->text('descricao')->nullable();
            $table->timestamps();

            // Um eixo não pode ter duas variáveis com o mesmo nome
            $table->unique(['eixo_id', 'nome']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('variaveis');
    }
};
