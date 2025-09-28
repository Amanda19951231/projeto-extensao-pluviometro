<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModelDadosPluviometros extends Model
{
    //
    protected $table = 'dados_pluviometros'; // Nome da tabela no banco de dados

    protected $primaryKey = 'id_dados'; // Chave primária da tabela

    protected $fillable = [
        'umidade', 
        'chuva', 
        'temperatura', 
        'id_pluviometro',
        'data_hora',
    ];
}
