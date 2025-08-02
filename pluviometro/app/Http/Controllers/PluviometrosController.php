<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ModelDadosPluviometros;
use App\Models\ModelPluviometros;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Illuminate\Validation\Rule;

class PluviometrosController extends Controller
{
    private $obj_pluviometro;
    private $obj_dados_pluviometro;

    public function __construct()
    {
        // Instanciar o modelo de tipos de peneiras
        $this->obj_dados_pluviometro = new ModelDadosPluviometros();
        $this->obj_pluviometro = new ModelPluviometros();
    }

    public function dados()
    {
        $pluviometros = $this->obj_dados_pluviometro::join('pluviometros', 'dados_pluviometros.id_pluviometro', '=', 'pluviometros.id_pluviometro')
            ->select('pluviometros.*', 'dados_pluviometros.*')
            ->get();

        $dados = [];

        foreach ($pluviometros as $pluviometro) {
            $response = Http::get('https://api.open-meteo.com/v1/forecast', [
                'latitude'        => $pluviometro->latitude,
                'longitude'       => $pluviometro->longitude,
                'current_weather' => true,
                'daily' => 'temperature_2m_max,temperature_2m_min,weathercode',
                'timezone' => 'America/Sao_Paulo',
                'hourly'          => 'temperature_2m,relative_humidity_2m',
                'timezone'        => 'America/Sao_Paulo',
            ]);

            $data = $response->json();

            $dados[] = [
                'id'           => $pluviometro->id_pluviometro,
                'nome'         => $pluviometro->nome,
                'numero_serie' => $pluviometro->numero_serie,
                'latitude'     => $pluviometro->latitude,
                'longitude'    => $pluviometro->longitude,
                'tempo'        => $pluviometro->tempo,
                'chuva'        => $pluviometro->chuva,
                'umidade_api'  => $data['hourly']['relative_humidity_2m'][0] ?? null,
                'temperatura_api' => $data['hourly']['temperature_2m'][0] ?? null,
                'api_bruta'    => $data['current_weather'] ?? [],
                'daily'   => $data['daily'], // Adiciona dados dos próximos dias
            ];
        }

        return response()->json([
            'status' => 'success',
            'data' => $dados
        ]);
    }

    public function pluviometros()
    {
        // Pega todos os pluviometros
        $pluviometros = $this->obj_pluviometro::leftjoin('dados_pluviometros', 'pluviometros.id_pluviometro', '=', 'dados_pluviometros.id_pluviometro')
            ->select('pluviometros.*', 'dados_pluviometros.*')
            ->get();

        // Retornar a view com os tipos de peneiras
        return Inertia::render('Pluviometros/Dados', [
            'pluviometros' => $pluviometros
        ]);
    }


    public function dashboard()
    {
        // Buscar todos os tipos de peneiras
        $pluviometros = $this->obj_pluviometro::all();

        // Retornar a view com os tipos de peneiras
        return Inertia::render('Dashboard', [
            'pluviometros' => $pluviometros
        ]);
    }

    public function index()
    {
        // Buscar todos os tipos de peneiras
        $subquery = $this->obj_dados_pluviometro
            ->select('id_pluviometro')
            ->selectRaw('MAX(data_hora) as ultima_data')
            ->groupBy('id_pluviometro');


        $pluviometros = $this->obj_pluviometro
            ::joinSub($subquery, 'ultimos', function ($join) {
                $join->on('pluviometros.id_pluviometro', '=', 'ultimos.id_pluviometro');
            })
            ->leftJoin('dados_pluviometros', function ($join) {
                $join->on('pluviometros.id_pluviometro', '=', 'dados_pluviometros.id_pluviometro')
                    ->on('dados_pluviometros.data_hora', '=', 'ultimos.ultima_data');
            })
            ->select('pluviometros.*', 'dados_pluviometros.data_hora', 'dados_pluviometros.umidade', 'dados_pluviometros.temperatura') // add o que quiser
            ->get();

        // dd($pluviometros);
        // Retornar a view com os tipos de peneiras
        return Inertia::render('Pluviometros/Index', [
            'pluviometros' => $pluviometros
        ]);
    }

    public function create()
    {
        return Inertia::render('Pluviometros/Create', [
            'pluviometro' => null,
        ]);
    }


    public function store(Request $request)
    {
        // Validar os dados do formulário
        try {

            $validated = $request->validate([
                'nome' => 'required|string|max:255',
                'codigo' => 'required|string|max:100|unique:pluviometros,numero_serie', // aqui
                'latitude' => 'required|numeric',
                'longitude' => 'required|numeric',
                'cidade' => 'required|string|max:255',
                'estado' => 'required|string|size:2',
                'endereco' => 'nullable|string|max:255',
                'numero' => 'nullable|string|max:20',
                'cep' => 'nullable|string|max:20',
            ]);

            // Criar o registro na tabela tipos_peneiras
            $pluviometros = $this->obj_pluviometro->create([
                'numero_serie' => $request->codigo,
                'nome' => $request->nome,
                'endereco' => $request->endereco,
                'numero' => $request->numero,
                'cidade' => $request->cidade,
                'cep' => $request->cep,
                'estado' => $request->estado,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
            ]);

            // Registrar log no arquivo de log
            Log::info('Pluviômetro salvo com sucesso.', [
                'user_id' => auth()->id(),
                'username' => auth()->user()->name ?? 'Guest',
                'tabela' => 'pluviometros',
                'action' => 'criação',
                'dados' => ['tipo_peneira' => $pluviometros],
                'timestamp' => now(),
            ]);

            // Redirecionar com mensagem de sucesso
            return redirect()->route('pluviometros')->with('success', 'Pluviômetro salvo com sucesso.');
        } catch (\Exception $e) {
            // Registrar erro no arquivo de log
            Log::error('Erro ao salvar o pluviômetro.', [
                'user_id' => auth()->id(),
                'username' => auth()->user()->name,
                'tabela' => 'pluviometros',
                'acao' => 'criação',
                'dados' => $request->all(),
                'error_message' => $e->getMessage(),
                'timestamp' => now(),
            ]);

            // Redirecionar com mensagem de erro
            return redirect()->route('pluviometros')->with('error', 'Ocorreu um erro ao salvar o pluviômetro: ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        $pluviometro = $this->obj_pluviometro::findOrFail($id);

        return Inertia::render('Pluviometros/Create', [  // pode usar o mesmo componente Create para editar
            'pluviometro' => $pluviometro
        ]);
    }


    public function update(Request $request, $id)
    {
        try {
            // Buscar o pluviômetro existente
            $pluviometro = $this->obj_pluviometro->findOrFail($id);

            // Validar os dados com regra de unicidade ignorando o ID atual
            $validated = $request->validate([
                'nome' => 'required|string|max:255',
                'codigo' => [
                    'required',
                    'string',
                    'max:100',
                ],
                'latitude' => 'required|numeric',
                'longitude' => 'required|numeric',
                'cidade' => 'required|string|max:255',
                'estado' => 'required|string|size:2',
                'endereco' => 'nullable|string|max:255',
                'numero' => 'nullable|string|max:20',
                'cep' => 'nullable|string|max:20',
            ]);

            // Atualizar os dados
            $pluviometro->update([
                'numero_serie' => $request->codigo,
                'nome' => $request->nome,
                'endereco' => $request->endereco,
                'numero' => $request->numero,
                'cidade' => $request->cidade,
                'cep' => $request->cep,
                'estado' => $request->estado,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
            ]);

            // Log da atualização
            Log::info('Pluviômetro atualizado com sucesso.', [
                'user_id' => auth()->id(),
                'username' => auth()->user()->name ?? 'Guest',
                'tabela' => 'pluviometros',
                'acao' => 'atualização',
                'id' => $pluviometro->id,
                'dados' => $pluviometro,
                'timestamp' => now(),
            ]);

            return redirect()->route('pluviometros')->with('success', 'Pluviômetro atualizado com sucesso.');
        } catch (\Exception $e) {
            Log::error('Erro ao atualizar o pluviômetro.', [
                'user_id' => auth()->id(),
                'username' => auth()->user()->name,
                'tabela' => 'pluviometros',
                'acao' => 'atualização',
                'id' => $id,
                'dados' => $request->all(),
                'error_message' => $e->getMessage(),
                'timestamp' => now(),
            ]);

            return redirect()->route('pluviometros')->with('error', 'Erro ao atualizar pluviômetro: ' . $e->getMessage());
        }
    }

    public function destroy($id_tipo_peneira)
    {
        try {
            // Buscar o tipo de peneira pelo ID
            $tipo_peneira = $this->obj_tipo_peneira->find($id_tipo_peneira);

            // Verificar se o tipo de peneira existe
            if (!$tipo_peneira) {
                // Registrar erro no arquivo de log
                Log::error('Tipo de peneira não encontrado para exclusão.', [
                    'user_id' => auth()->id(),
                    'username' => auth()->user()->name ?? 'Guest',
                    'tabela' => 'tipos_peneiras',
                    'acao' => 'exclusão',
                    'id_tipo_peneira' => $id_tipo_peneira,
                    'timestamp' => now(),
                ]);

                // Redirecionar com mensagem de erro
                return redirect()->route('tipos_peneiras.index')->with('warning', 'Tipo de peneira não encontrado.');
            }

            // Dados antigos para log
            $dados_antigos = $tipo_peneira;

            // Excluir o registro
            $tipo_peneira->delete();

            // Registrar log de exclusão no banco
            ModelPedidosLogs::create([
                'user_id' => auth()->user()->id,
                'user_name' => auth()->user()->name ?? 'Guest',
                'acao' => 'exclusão',
                'dados_anteriores' => array_merge(
                    ['tabela' => 'tipos_peneiras'],
                    $dados_antigos->toArray()
                ),
            ]);

            // Registrar log no arquivo de log
            Log::info('Peneira excluída com sucesso.', [
                'user_id' => auth()->id(),
                'username' => auth()->user()->name ?? 'Guest',
                'tabela' => 'tipos_peneiras',
                'action' => 'exclusão',
                'dados_antigos' => $dados_antigos,
                'timestamp' => now(),
            ]);

            // Redirecionar com mensagem de sucesso
            return redirect()->route('tipos_peneiras.index')->with('success', 'Peneira excluída com sucesso.');
        } catch (\Exception $e) {
            // Registrar erro no arquivo de log
            Log::error('Erro ao excluir a peneira.', [
                'user_id' => auth()->id(),
                'username' => auth()->user()->name ?? 'Guest',
                'tabela' => 'tipos_peneiras',
                'acao' => 'exclusão',
                'id_tipo_peneira' => $id_tipo_peneira,
                'error_message' => $e->getMessage(),
                'timestamp' => now(),
            ]);

            // Redirecionar com mensagem de erro
            return redirect()->route('tipos_peneiras.index')->with('error', 'Ocorreu um erro ao excluir a peneira: ' . $e->getMessage());
        }
    }
}
