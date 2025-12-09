<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ConsultaCadastroRequest;
use App\Http\Requests\Api\ConsultaEstoqueRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProdutoController extends Controller
{
    /**
     * Consulta o cadastro de produto pelo código auxiliar (EAN).
     */
    public function consultaCadastro(ConsultaCadastroRequest $request): JsonResponse
    {
        $codauxiliar = $request->validated('codauxiliar');

        try {
            $produto = DB::connection('oracle')
                ->table('pcprodut as p')
                ->join('pcdepto as d', 'p.codepto', '=', 'd.codepto')
                ->where('p.codauxiliar', $codauxiliar)
                ->selectRaw('
                    p.codprod,
                    p.codauxiliar,
                    p.descricao,
                    d.descricao as departamento,
                    nvl(p.qtunit, 1) as qt_unitario,
                    nvl(p.multiplo, 1) as qt_multiplo_venda,
                    nvl(p.qtunitcx, 0) as qt_unit_caixa_master,
                    p.marca,
                    p.unidade,
                    p.unidademaster
                ')
                ->first();
        } catch (\Exception $e) {
            Log::error('Erro ao consultar produto no Oracle', [
                'codauxiliar' => $codauxiliar,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Erro ao consultar produto. Tente novamente mais tarde.',
            ], 503);
        }

        if (! $produto) {
            return response()->json([
                'message' => 'Produto não encontrado.',
            ], 404);
        }

        return response()->json([
            'data' => $produto,
        ]);
    }

    /**
     * Consulta o estoque de produto pelo código auxiliar (EAN).
     *
     * - codfilial não informado ou null: busca da filial 1
     * - codfilial = -1: retorna array com estoque de todas as filiais
     * - codfilial = N: busca da filial N específica
     */
    public function consultaEstoque(ConsultaEstoqueRequest $request): JsonResponse
    {
        $codauxiliar = $request->validated('codauxiliar');
        $codfilial = $request->validated('codfilial');

        // Se não informou codfilial, assume filial 1
        if ($codfilial === null || $codfilial === '') {
            $codfilial = 1;
        } else {
            $codfilial = (int) $codfilial;
        }

        try {
            // Buscar todas as filiais (-1) ou filial específica
            if ($codfilial === -1) {
                $estoques = $this->buscarEstoqueTodasFiliais($codauxiliar);

                if (empty($estoques)) {
                    return response()->json([
                        'message' => 'Produto não encontrado.',
                    ], 404);
                }

                return response()->json([
                    'data' => $estoques,
                ]);
            }

            // Verificar se a filial existe
            $filialExiste = DB::connection('oracle')
                ->table('pcfilial')
                ->where('codigo', $codfilial)
                ->exists();

            if (! $filialExiste) {
                return response()->json([
                    'message' => "Filial {$codfilial} não encontrada.",
                ], 404);
            }

            // Buscar estoque da filial específica
            $estoque = $this->buscarEstoqueFilial($codauxiliar, $codfilial);

            if (! $estoque) {
                return response()->json([
                    'message' => 'Produto não encontrado.',
                ], 404);
            }

            return response()->json([
                'data' => $estoque,
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao consultar estoque no Oracle', [
                'codauxiliar' => $codauxiliar,
                'codfilial' => $codfilial,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Erro ao consultar estoque. Tente novamente mais tarde.',
            ], 503);
        }
    }

    /**
     * Busca estoque de uma filial específica.
     */
    private function buscarEstoqueFilial(string $codauxiliar, int $codfilial): ?object
    {
        return DB::connection('oracle')
            ->table('pcest as e')
            ->join('pcprodut as p', 'e.codprod', '=', 'p.codprod')
            ->where('e.codfilial', $codfilial)
            ->where('p.codauxiliar', $codauxiliar)
            ->selectRaw('
                e.codprod,
                p.codauxiliar,
                e.codfilial,
                ((e.qtestger - e.qtbloqueada) - e.qtindeniz) as qt_disponivel
            ')
            ->first();
    }

    /**
     * Busca estoque de todas as filiais.
     *
     * @return array<int, object>
     */
    private function buscarEstoqueTodasFiliais(string $codauxiliar): array
    {
        return DB::connection('oracle')
            ->table('pcest as e')
            ->join('pcprodut as p', 'e.codprod', '=', 'p.codprod')
            ->where('p.codauxiliar', $codauxiliar)
            ->selectRaw('
                e.codprod,
                p.codauxiliar,
                e.codfilial,
                ((e.qtestger - e.qtbloqueada) - e.qtindeniz) as qt_disponivel
            ')
            ->orderBy('e.codfilial')
            ->get()
            ->all();
    }
}
