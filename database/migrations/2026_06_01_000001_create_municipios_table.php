<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('municipios', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_ibge', 7)->unique()->comment('Código IBGE oficial de 7 dígitos');
            $table->string('nome', 120);
            $table->string('uf', 2)->default('PA');
            $table->string('estado', 80)->default('Pará');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('municipios');
    }
};
