<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;
use Illuminate\Foundation\Application;
use Carbon\Carbon;
use App\Models\ModelPluviometros;

class WelcomeController extends Controller
{
    private $obj_pluviometro;
    private $obj_dados_pluviometro;

    public function __construct()
    {
        $this->obj_pluviometro = new ModelPluviometros();
    }

    public function index()
    {
        $hojeMeiaNoite = Carbon::today(); // pega a data de hoje 00:00:00

        $dados_pluviometros = $this->obj_pluviometro
            ::leftJoin('dados_pluviometros', 'pluviometros.id_pluviometro', '=', 'dados_pluviometros.id_pluviometro')
            ->select('pluviometros.*', 'dados_pluviometros.umidade', 'dados_pluviometros.temperatura', 'dados_pluviometros.chuva', 'dados_pluviometros.data_hora')
            ->where('dados_pluviometros.data_hora', '>=', $hojeMeiaNoite)
            ->get()
            ->groupBy('id_pluviometro')
            ->map(function ($group) {
                $pluviometro = $group->first()->toArray();

                unset($pluviometro['umidade'], $pluviometro['temperatura'], $pluviometro['chuva'], $pluviometro['data_hora']);

                $pluviometro['dados'] = $group->map(function ($item) {
                    return [
                        'umidade' => $item->umidade,
                        'temperatura' => $item->temperatura,
                        'chuva' => $item->chuva,
                        'data_hora' => $item->data_hora,
                    ];
                })->values();

                return $pluviometro;
            })->values();

        // dd($dados_pluviometros);

        // Renderiza via Inertia, com todas as props
        return Inertia::render('Welcome', [
            'dados_pluviometros' => $dados_pluviometros,
            'canLogin'          => Route::has('login'),
            'canRegister'       => Route::has('register'),
            'laravelVersion'    => Application::VERSION,
            'phpVersion'        => PHP_VERSION,
        ]);
    }
}
