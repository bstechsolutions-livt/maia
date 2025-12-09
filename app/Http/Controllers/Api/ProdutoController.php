<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ConsultaCadastroRequest;
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
}
