<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('dados_pluviometros', function (Blueprint $table) {
            $table->id('id_dados');
            $table->unsignedBigInteger('id_pluviometro'); // Chave estrangeira para 'tipos_peneiras'
            $table->foreign('id_pluviometro')->references('id_pluviometro')->on('pluviometros'); // Chave estrangeira referenciando a tabela 'tipos_peneiras'
            $table->decimal('umidade', 5, 2);     // Ex: 75.50%
            $table->decimal('chuva', 6, 2);       // Ex: 12.34 mm
            $table->decimal('temperatura', 6, 2);       // Ex: 12.34 mm
            $table->dateTime('data_hora');  // Aqui, o campo que você pediu
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dados_pluviometros');
    }
};
