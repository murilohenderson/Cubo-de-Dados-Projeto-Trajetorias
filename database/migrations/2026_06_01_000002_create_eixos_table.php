<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eixos', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 120)->unique()->comment('Ex: Ambiental, Socioeconômico, Demográfico, Epidemiológico');
            $table->string('slug', 80)->unique()->comment('Identificador interno: ambiental, economico, demografico, epidemiologico');
            $table->string('icone', 60)->nullable()->comment('Emoji ou classe de ícone para a UI');
            $table->string('cor_primaria', 20)->nullable()->comment('Cor HEX para a face do cubo');
            $table->text('descricao')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eixos');
    }
};
