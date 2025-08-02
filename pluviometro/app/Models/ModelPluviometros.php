<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModelPluviometros extends Model
{
    //
    protected $table = 'pluviometros'; // Nome da tabela no banco de dados

    protected $primaryKey = 'id_pluviometro'; // Chave primária da tabela

    protected $fillable = [
        'numero_serie', 
        'nome', 
        'endereco',
        'numero',
        'cidade',
        'cep',
        'estado',
        'latitude', 
        'longitude',
    ];
}
