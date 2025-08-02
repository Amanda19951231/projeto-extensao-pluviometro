<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class TempoController extends Controller
{

    public function index()
    {
        $response = Http::get('https://api.open-meteo.com/v1/forecast', [
            'latitude'  => '-23.5505',
            'longitude' => '-46.6333',
            'current_weather' => true,
            'daily' => 'temperature_2m_max,temperature_2m_min,weathercode',
            'timezone' => 'America/Sao_Paulo',
        ]);

        $data = $response->json();

        return view('welcome', [
            'current' => $data['current_weather'],
            'daily'   => $data['daily'], // Adiciona dados dos próximos dias
        ]);
    }
}
