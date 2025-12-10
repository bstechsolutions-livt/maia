<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CriarPedidoRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PedidoController extends Controller
{
    /**
     * Cria um pedido na integradora do WinThor.
     *
     * Fluxo:
     * 1. Busca dados do cliente pelo CPF (codcli, codusur, codcob, codplpag)
     * 2. Valida todos os itens (produto existe, múltiplo válido, busca preço)
     * 3. Gera próximo número do pedido
     * 4. Insere cabeçalho do pedido (pcpedcfv)
     * 5. Insere itens do pedido (pcpedifv)
     */
    public function criar(CriarPedidoRequest $request): JsonResponse
    {
        $cpf = preg_replace('/[^0-9]/', '', $request->validated('cpf'));
        $codtransp = $request->validated('codtransp');
        $codfilial = $request->validated('codfilial') ?? 1;
        $numregiao = $request->validated('numregiao');
        $obs = $request->validated('obs') ?? '';
        $obsEntrega = $request->validated('obs_entrega') ?? '';
        $itens = $request->validated('itens');

        try {
            // 1. Buscar dados do cliente
            $cliente = $this->buscarCliente($cpf);

            if (! $cliente) {
                return response()->json([
                    'message' => 'Cliente não encontrado.',
                ], 404);
            }

            // Buscar cobrança e plano de pagamento do cliente
            $dadosPagamento = $this->buscarDadosPagamento($cliente->codcli);

            if (! $dadosPagamento) {
                return response()->json([
                    'message' => 'Dados de cobrança/plano de pagamento do cliente não encontrados.',
                ], 404);
            }

            // Determinar região para busca de preço
            // Prioridade: numregiao informado > região do cliente > 1
            $regiaoPreco = $numregiao;
            if ($regiaoPreco === null) {
                $regiaoCliente = $this->buscarRegiaoCliente($cpf);
                $regiaoPreco = $regiaoCliente ?? 1;
            }

            // 2. Validar todos os itens
            $itensValidados = [];
            foreach ($itens as $index => $item) {
                $codauxiliar = $item['codauxiliar'];
                $quantidade = $item['quantidade'];

                // Buscar produto
                $produto = $this->buscarProduto($codauxiliar);

                if (! $produto) {
                    return response()->json([
                        'message' => "Item {$index}: Produto com código {$codauxiliar} não encontrado.",
                    ], 422);
                }

                // Validar múltiplo
                $multiplo = $produto->multiplo ?? 1;
                if ($multiplo > 0 && fmod($quantidade, $multiplo) != 0) {
                    return response()->json([
                        'message' => "Item {$index}: Quantidade {$quantidade} não é múltiplo de {$multiplo} para o produto {$codauxiliar}.",
                    ], 422);
                }

                // Buscar preço
                $preco = $this->buscarPreco($codauxiliar, $regiaoPreco);

                if (! $preco) {
                    return response()->json([
                        'message' => "Item {$index}: Preço não encontrado para o produto {$codauxiliar} na região {$regiaoPreco}.",
                    ], 422);
                }

                $itensValidados[] = [
                    'codprod' => $produto->codprod,
                    'codauxiliar' => $codauxiliar,
                    'quantidade' => $quantidade,
                    'pvenda' => $preco->pvenda,
                    'multiplo' => $multiplo,
                ];
            }

            // 3. Iniciar transação para garantir atomicidade
            DB::connection('oracle')->beginTransaction();

            try {
                // Buscar e incrementar próximo número do pedido
                $numped = $this->gerarProximoNumeroPedido($cliente->codusur1);

                // 4. Inserir cabeçalho do pedido
                $this->inserirCabecalhoPedido([
                    'numped' => $numped,
                    'codusur' => $cliente->codusur1,
                    'cpf' => $cpf,
                    'codcli' => $cliente->codcli,
                    'codfilial' => $codfilial,
                    'codcob' => $dadosPagamento->codcob,
                    'codplpag' => $dadosPagamento->codplpag,
                    'codtransp' => $codtransp,
                    'obs' => $obs,
                    'obs_entrega' => $obsEntrega,
                ]);

                // 5. Inserir itens do pedido
                foreach ($itensValidados as $seq => $itemValidado) {
                    $this->inserirItemPedido([
                        'numped' => $numped,
                        'cpf' => $cpf,
                        'codusur' => $cliente->codusur1,
                        'codprod' => $itemValidado['codprod'],
                        'codauxiliar' => $itemValidado['codauxiliar'],
                        'quantidade' => $itemValidado['quantidade'],
                        'pvenda' => $itemValidado['pvenda'],
                        'numseq' => $seq + 1,
                    ]);
                }

                DB::connection('oracle')->commit();

                // Retornar dados do pedido criado
                return response()->json([
                    'message' => 'Pedido criado com sucesso.',
                    'data' => [
                        'numped' => $numped,
                        'codcli' => $cliente->codcli,
                        'cliente' => $cliente->cliente,
                        'codusur' => $cliente->codusur1,
                        'codfilial' => $codfilial,
                        'codcob' => $dadosPagamento->codcob,
                        'cobranca' => $dadosPagamento->cobranca,
                        'codplpag' => $dadosPagamento->codplpag,
                        'plano_pagamento' => $dadosPagamento->plano_pagamento,
                        'codtransp' => $codtransp,
                        'obs' => $obs,
                        'obs_entrega' => $obsEntrega,
                        'itens' => $itensValidados,
                        'total_itens' => count($itensValidados),
                        'valor_total' => collect($itensValidados)->sum(fn ($i) => $i['quantidade'] * $i['pvenda']),
                    ],
                ], 201);
            } catch (\Exception $e) {
                DB::connection('oracle')->rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            Log::error('Erro ao criar pedido no Oracle', [
                'cpf' => $cpf,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Erro ao criar pedido. Tente novamente mais tarde.',
            ], 503);
        }
    }

    /**
     * Busca dados do cliente pelo CPF/CNPJ.
     */
    private function buscarCliente(string $cpf): ?object
    {
        return DB::connection('oracle')
            ->table('pcclient')
            ->whereRaw("replace(replace(replace(replace(cgcent, ',', ''), '.', ''), '-', ''), '/', '') = ?", [$cpf])
            ->selectRaw('codcli, codusur1, cliente')
            ->first();
    }

    /**
     * Busca dados de cobrança e plano de pagamento do cliente.
     */
    private function buscarDadosPagamento(int $codcli): ?object
    {
        return DB::connection('oracle')
            ->table('pcclient as c')
            ->join('pccob as b', 'c.codcob', '=', 'b.codcob')
            ->join('pcplpag as p', 'c.codplpag', '=', 'p.codplpag')
            ->where('c.codcli', $codcli)
            ->selectRaw('c.codcob, b.cobranca, c.codplpag, p.descricao as plano_pagamento')
            ->first();
    }

    /**
     * Busca a região do cliente pelo CPF/CNPJ.
     */
    private function buscarRegiaoCliente(string $cpf): ?int
    {
        $resultado = DB::connection('oracle')
            ->table('pcclient as c')
            ->join('pcpraca as p', 'c.codpraca', '=', 'p.codpraca')
            ->whereRaw("replace(replace(replace(replace(c.cgcent, ',', ''), '.', ''), '-', ''), '/', '') = ?", [$cpf])
            ->selectRaw('p.numregiao')
            ->first();

        return $resultado?->numregiao;
    }

    /**
     * Busca produto pelo código auxiliar (EAN).
     */
    private function buscarProduto(string $codauxiliar): ?object
    {
        return DB::connection('oracle')
            ->table('pcprodut')
            ->where('codauxiliar', $codauxiliar)
            ->selectRaw('codprod, codauxiliar, descricao, nvl(multiplo, 1) as multiplo')
            ->first();
    }

    /**
     * Busca preço do produto na região.
     */
    private function buscarPreco(string $codauxiliar, int $numregiao): ?object
    {
        return DB::connection('oracle')
            ->table('pctabpr as t')
            ->join('pcprodut as p', 't.codprod', '=', 'p.codprod')
            ->where('t.numregiao', $numregiao)
            ->where('p.codauxiliar', $codauxiliar)
            ->selectRaw('t.pvenda')
            ->first();
    }

    /**
     * Gera o próximo número de pedido e atualiza o contador.
     */
    private function gerarProximoNumeroPedido(int $codusur): int
    {
        // Buscar próximo número
        $resultado = DB::connection('oracle')
            ->table('pcusuari')
            ->where('codusur', $codusur)
            ->selectRaw('(proxnumped + 1) as proxnumped')
            ->first();

        $numped = $resultado->proxnumped;

        // Atualizar contador
        DB::connection('oracle')
            ->table('pcusuari')
            ->where('codusur', $codusur)
            ->update(['proxnumped' => DB::raw('proxnumped + 1')]);

        return $numped;
    }

    /**
     * Insere o cabeçalho do pedido na integradora.
     *
     * @param  array<string, mixed>  $dados
     */
    private function inserirCabecalhoPedido(array $dados): void
    {
        DB::connection('oracle')
            ->table('pcpedcfv')
            ->insert([
                'origemped' => 'T',
                'importado' => 1,
                'numpedrca' => $dados['numped'],
                'codusur' => $dados['codusur'],
                'cgccli' => $dados['cpf'],
                'dtaberturapedpalm' => DB::raw('TRUNC(SYSDATE)'),
                'dtfechamentopedpalm' => DB::raw('TRUNC(SYSDATE)'),
                'codfilial' => $dados['codfilial'],
                'codfilialnf' => $dados['codfilial'],
                'codfilialretira' => $dados['codfilial'],
                'vlfrete' => 0,
                'codcob' => $dados['codcob'],
                'codplpag' => $dados['codplpag'],
                'condvenda' => 1, // Sempre 1 - Venda
                'obs1' => $dados['obs'],
                'obs2' => 'FORCA VENDAS API - EASYTECH',
                'obsentrega1' => $dados['obs_entrega'],
                'obsentrega2' => '',
                'codfornecfrete' => $dados['codtransp'],
                'tipodocumento' => null,
                'codcli' => $dados['codcli'],
                'geracp' => null,
            ]);
    }

    /**
     * Insere um item do pedido na integradora.
     *
     * @param  array<string, mixed>  $dados
     */
    private function inserirItemPedido(array $dados): void
    {
        DB::connection('oracle')
            ->table('pcpedifv')
            ->insert([
                'numpedrca' => $dados['numped'],
                'cgccli' => $dados['cpf'],
                'codusur' => $dados['codusur'],
                'dtaberturapedpalm' => DB::raw('TRUNC(SYSDATE)'),
                'codprod' => $dados['codprod'],
                'qt' => $dados['quantidade'],
                'pvenda' => $dados['pvenda'],
                'codauxiliar' => $dados['codauxiliar'],
                'numseq' => $dados['numseq'],
                'respquestfrete' => 'N',
            ]);
    }
}
