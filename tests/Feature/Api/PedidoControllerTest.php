<?php

use App\Models\User;

use function Pest\Laravel\postJson;

describe('POST /api/pedidos (autenticação)', function () {
    it('retorna 401 quando não autenticado', function () {
        postJson('/api/pedidos', [
            'cpf' => '12345678901',
            'codtransp' => 1,
            'itens' => [
                ['codauxiliar' => '7896647027882', 'quantidade' => 10],
            ],
        ])->assertUnauthorized();
    });

    it('retorna 401 quando usuário está inativo', function () {
        /** @var User $user */
        $user = User::factory()->inactive()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        postJson('/api/pedidos', [
            'cpf' => '12345678901',
            'codtransp' => 1,
            'itens' => [
                ['codauxiliar' => '7896647027882', 'quantidade' => 10],
            ],
        ], [
            'Authorization' => "Bearer {$token}",
        ])->assertUnauthorized()
            ->assertJson(['message' => 'Sua conta está desativada.']);
    });
});

describe('POST /api/pedidos (validação)', function () {
    it('retorna 422 quando cpf não é informado', function () {
        /** @var User $user */
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        postJson('/api/pedidos', [
            'codtransp' => 1,
            'itens' => [
                ['codauxiliar' => '7896647027882', 'quantidade' => 10],
            ],
        ], [
            'Authorization' => "Bearer {$token}",
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['cpf']);
    });

    it('retorna 422 quando codtransp não é informado', function () {
        /** @var User $user */
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        postJson('/api/pedidos', [
            'cpf' => '12345678901',
            'itens' => [
                ['codauxiliar' => '7896647027882', 'quantidade' => 10],
            ],
        ], [
            'Authorization' => "Bearer {$token}",
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['codtransp']);
    });

    it('retorna 422 quando itens não é informado', function () {
        /** @var User $user */
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        postJson('/api/pedidos', [
            'cpf' => '12345678901',
            'codtransp' => 1,
        ], [
            'Authorization' => "Bearer {$token}",
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['itens']);
    });

    it('retorna 422 quando itens está vazio', function () {
        /** @var User $user */
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        postJson('/api/pedidos', [
            'cpf' => '12345678901',
            'codtransp' => 1,
            'itens' => [],
        ], [
            'Authorization' => "Bearer {$token}",
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['itens']);
    });

    it('retorna 422 quando item não tem codauxiliar', function () {
        /** @var User $user */
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        postJson('/api/pedidos', [
            'cpf' => '12345678901',
            'codtransp' => 1,
            'itens' => [
                ['quantidade' => 10],
            ],
        ], [
            'Authorization' => "Bearer {$token}",
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['itens.0.codauxiliar']);
    });

    it('retorna 422 quando item não tem quantidade', function () {
        /** @var User $user */
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        postJson('/api/pedidos', [
            'cpf' => '12345678901',
            'codtransp' => 1,
            'itens' => [
                ['codauxiliar' => '7896647027882'],
            ],
        ], [
            'Authorization' => "Bearer {$token}",
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['itens.0.quantidade']);
    });

    it('retorna 422 quando quantidade é zero', function () {
        /** @var User $user */
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        postJson('/api/pedidos', [
            'cpf' => '12345678901',
            'codtransp' => 1,
            'itens' => [
                ['codauxiliar' => '7896647027882', 'quantidade' => 0],
            ],
        ], [
            'Authorization' => "Bearer {$token}",
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['itens.0.quantidade']);
    });

    it('retorna 422 quando quantidade é negativa', function () {
        /** @var User $user */
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        postJson('/api/pedidos', [
            'cpf' => '12345678901',
            'codtransp' => 1,
            'itens' => [
                ['codauxiliar' => '7896647027882', 'quantidade' => -5],
            ],
        ], [
            'Authorization' => "Bearer {$token}",
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['itens.0.quantidade']);
    });

    it('aceita cpf formatado com pontos e traços', function () {
        /** @var User $user */
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        postJson('/api/pedidos', [
            'cpf' => '123.456.789-01',
            'codtransp' => 1,
            'itens' => [
                ['codauxiliar' => '7896647027882', 'quantidade' => 10],
            ],
        ], [
            'Authorization' => "Bearer {$token}",
        ])->assertJsonMissingValidationErrors(['cpf']);
    });

    it('aceita cnpj formatado', function () {
        /** @var User $user */
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        postJson('/api/pedidos', [
            'cpf' => '12.345.678/0001-90',
            'codtransp' => 1,
            'itens' => [
                ['codauxiliar' => '7896647027882', 'quantidade' => 10],
            ],
        ], [
            'Authorization' => "Bearer {$token}",
        ])->assertJsonMissingValidationErrors(['cpf']);
    });

    it('aceita múltiplos itens', function () {
        /** @var User $user */
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        postJson('/api/pedidos', [
            'cpf' => '12345678901',
            'codtransp' => 1,
            'itens' => [
                ['codauxiliar' => '7896647027882', 'quantidade' => 10],
                ['codauxiliar' => '7896647027883', 'quantidade' => 5],
                ['codauxiliar' => '7896647027884', 'quantidade' => 15],
            ],
        ], [
            'Authorization' => "Bearer {$token}",
        ])->assertJsonMissingValidationErrors(['itens']);
    });

    it('aceita parâmetros opcionais', function () {
        /** @var User $user */
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        postJson('/api/pedidos', [
            'cpf' => '12345678901',
            'codtransp' => 1,
            'codfilial' => 2,
            'numregiao' => 3,
            'obs' => 'Observação do pedido',
            'obs_entrega' => 'Deixar na portaria',
            'itens' => [
                ['codauxiliar' => '7896647027882', 'quantidade' => 10],
            ],
        ], [
            'Authorization' => "Bearer {$token}",
        ])->assertJsonMissingValidationErrors(['codfilial', 'numregiao', 'obs', 'obs_entrega']);
    });
});

describe('POST /api/pedidos (consulta Oracle)', function () {
    it('retorna 503 quando conexão Oracle falha', function () {
        /** @var User $user */
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        postJson('/api/pedidos', [
            'cpf' => '12345678901',
            'codtransp' => 1,
            'itens' => [
                ['codauxiliar' => '7896647027882', 'quantidade' => 10],
            ],
        ], [
            'Authorization' => "Bearer {$token}",
        ])->assertServiceUnavailable();
    });
});
