<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */        
    public function up()
    {
        Schema::create('pluviometros', function (Blueprint $table) {
            $table->id('id_pluviometro');
            $table->string('numero_serie')->unique();
            $table->string('nome');
            $table->string('endereco');
            $table->string('numero');
            $table->string('cidade');
            $table->string('cep');
            $table->string('estado');
            $table->decimal('latitude', 10, 7);   // precisão GPS padrão
            $table->decimal('longitude', 10, 7);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pluviometros');
    }
};
