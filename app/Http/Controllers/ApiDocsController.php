<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ApiDocsController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        // Buscar tokens ativos do usuário
        $tokens = $user->tokens()->latest()->get()->map(fn ($token) => [
            'id' => $token->id,
            'name' => $token->name,
            'last_used_at' => $token->last_used_at?->toISOString(),
            'created_at' => $token->created_at->toISOString(),
        ]);

        // Definir endpoints da API
        $endpoints = $this->getEndpoints();

        return Inertia::render('ApiDocs', [
            'endpoints' => $endpoints,
            'tokens' => $tokens,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'baseUrl' => config('app.url').'/api',
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getEndpoints(): array
    {
        return [
            [
                'group' => 'Autenticação',
                'endpoints' => [
                    [
                        'name' => 'Login',
                        'method' => 'POST',
                        'path' => '/login',
                        'description' => 'Autentica um usuário e retorna um token de acesso.',
                        'auth' => false,
                        'parameters' => [
                            [
                                'name' => 'email',
                                'type' => 'string',
                                'required' => true,
                                'description' => 'E-mail do usuário',
                            ],
                            [
                                'name' => 'password',
                                'type' => 'string',
                                'required' => true,
                                'description' => 'Senha do usuário',
                            ],
                            [
                                'name' => 'device_name',
                                'type' => 'string',
                                'required' => false,
                                'description' => 'Nome do dispositivo (opcional)',
                            ],
                        ],
                        'response' => [
                            'success' => [
                                'code' => 200,
                                'example' => [
                                    'token' => 'seu-token-aqui',
                                    'user' => [
                                        'id' => 1,
                                        'name' => 'Nome do Usuário',
                                        'email' => 'email@exemplo.com',
                                    ],
                                ],
                            ],
                            'error' => [
                                'code' => 422,
                                'example' => [
                                    'message' => 'As credenciais informadas estão incorretas.',
                                ],
                            ],
                        ],
                    ],
                    [
                        'name' => 'Obter Usuário Autenticado',
                        'method' => 'GET',
                        'path' => '/user',
                        'description' => 'Retorna os dados do usuário autenticado.',
                        'auth' => true,
                        'parameters' => [],
                        'response' => [
                            'success' => [
                                'code' => 200,
                                'example' => [
                                    'id' => 1,
                                    'name' => 'Nome do Usuário',
                                    'email' => 'email@exemplo.com',
                                    'email_verified_at' => '2025-01-01T00:00:00.000000Z',
                                    'created_at' => '2025-01-01T00:00:00.000000Z',
                                    'updated_at' => '2025-01-01T00:00:00.000000Z',
                                ],
                            ],
                            'error' => [
                                'code' => 401,
                                'example' => [
                                    'message' => 'Unauthenticated.',
                                ],
                            ],
                        ],
                    ],
                    [
                        'name' => 'Logout',
                        'method' => 'POST',
                        'path' => '/logout',
                        'description' => 'Revoga o token atual do usuário.',
                        'auth' => true,
                        'parameters' => [],
                        'response' => [
                            'success' => [
                                'code' => 200,
                                'example' => [
                                    'message' => 'Logout realizado com sucesso.',
                                ],
                            ],
                        ],
                    ],
                    [
                        'name' => 'Logout de Todos os Dispositivos',
                        'method' => 'POST',
                        'path' => '/logout-all',
                        'description' => 'Revoga todos os tokens do usuário.',
                        'auth' => true,
                        'parameters' => [],
                        'response' => [
                            'success' => [
                                'code' => 200,
                                'example' => [
                                    'message' => 'Todos os tokens foram revogados.',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'group' => 'Produtos (WinThor)',
                'endpoints' => [
                    [
                        'name' => 'Consulta Cadastro',
                        'method' => 'GET',
                        'path' => '/produtos/consulta-cadastro',
                        'description' => 'Consulta o cadastro de um produto pelo código auxiliar (EAN).',
                        'auth' => true,
                        'parameters' => [
                            [
                                'name' => 'codauxiliar',
                                'type' => 'string',
                                'required' => true,
                                'description' => 'Código auxiliar (EAN) do produto',
                            ],
                        ],
                        'response' => [
                            'success' => [
                                'code' => 200,
                                'example' => [
                                    'data' => [
                                        'codprod' => 12345,
                                        'codauxiliar' => '7896647027882',
                                        'descricao' => 'PRODUTO EXEMPLO',
                                        'departamento' => 'MERCEARIA',
                                        'qt_unitario' => 1,
                                        'qt_multiplo_venda' => 1,
                                        'qt_unit_caixa_master' => 12,
                                        'marca' => 'MARCA EXEMPLO',
                                        'unidade' => 'UN',
                                        'unidademaster' => 'CX',
                                    ],
                                ],
                            ],
                            'error' => [
                                'code' => 404,
                                'example' => [
                                    'message' => 'Produto não encontrado.',
                                ],
                            ],
                        ],
                    ],
                    [
                        'name' => 'Consulta Estoque',
                        'method' => 'GET',
                        'path' => '/produtos/consulta-estoque',
                        'description' => 'Consulta o estoque de um produto pelo código auxiliar (EAN). Se codfilial não for informado, busca da filial 1. Se codfilial = -1, retorna estoque de todas as filiais.',
                        'auth' => true,
                        'parameters' => [
                            [
                                'name' => 'codauxiliar',
                                'type' => 'string',
                                'required' => true,
                                'description' => 'Código auxiliar (EAN) do produto',
                            ],
                            [
                                'name' => 'codfilial',
                                'type' => 'integer',
                                'required' => false,
                                'description' => 'Código da filial. Padrão: 1. Use -1 para retornar estoque de todas as filiais.',
                            ],
                        ],
                        'response' => [
                            'success' => [
                                'code' => 200,
                                'example' => [
                                    'data' => [
                                        'codprod' => 12345,
                                        'codauxiliar' => '7896647027882',
                                        'codfilial' => 1,
                                        'qt_disponivel' => 150,
                                    ],
                                ],
                            ],
                            'error' => [
                                'code' => 404,
                                'example' => [
                                    'message' => 'Produto não encontrado.',
                                ],
                            ],
                        ],
                    ],
                    [
                        'name' => 'Consulta Preço',
                        'method' => 'GET',
                        'path' => '/produtos/consulta-preco',
                        'description' => 'Consulta o preço de um produto pelo código auxiliar (EAN). Prioridade: 1) Se passar cpf, busca região do cliente; 2) Se passar numregiao, busca pela região; 3) Se não passar nenhum, usa região 1; 4) Se numregiao = -1, retorna preços de todas as regiões.',
                        'auth' => true,
                        'parameters' => [
                            [
                                'name' => 'codauxiliar',
                                'type' => 'string',
                                'required' => true,
                                'description' => 'Código auxiliar (EAN) do produto',
                            ],
                            [
                                'name' => 'cpf',
                                'type' => 'string',
                                'required' => false,
                                'description' => 'CPF/CNPJ do cliente (tem prioridade sobre numregiao)',
                            ],
                            [
                                'name' => 'numregiao',
                                'type' => 'integer',
                                'required' => false,
                                'description' => 'Número da região. Padrão: 1. Use -1 para retornar preços de todas as regiões.',
                            ],
                        ],
                        'response' => [
                            'success' => [
                                'code' => 200,
                                'example' => [
                                    'data' => [
                                        'codprod' => 12345,
                                        'codauxiliar' => '7896647027882',
                                        'numregiao' => 1,
                                        'pvenda' => 29.90,
                                    ],
                                ],
                            ],
                            'error' => [
                                'code' => 404,
                                'example' => [
                                    'message' => 'Produto não encontrado.',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
