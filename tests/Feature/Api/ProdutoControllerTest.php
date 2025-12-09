<?php

use App\Models\User;

use function Pest\Laravel\getJson;

describe('consulta-cadastro', function () {
    it('returns validation error for missing codauxiliar', function () {
        /** @var User $user */
        $user = User::factory()->create();
        $token = $user->createToken('test-device')->plainTextToken;

        getJson(route('api.produtos.consulta-cadastro'), [
            'Authorization' => "Bearer {$token}",
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['codauxiliar']);
    });

    it('returns validation error for non-numeric codauxiliar', function () {
        /** @var User $user */
        $user = User::factory()->create();
        $token = $user->createToken('test-device')->plainTextToken;

        getJson(route('api.produtos.consulta-cadastro', ['codauxiliar' => 'abc123']), [
            'Authorization' => "Bearer {$token}",
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['codauxiliar']);
    });

    it('returns validation error for codauxiliar exceeding max digits', function () {
        /** @var User $user */
        $user = User::factory()->create();
        $token = $user->createToken('test-device')->plainTextToken;

        getJson(route('api.produtos.consulta-cadastro', ['codauxiliar' => '123456789012345678901']), [ // 21 digits
            'Authorization' => "Bearer {$token}",
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['codauxiliar']);
    });

    it('returns unauthorized for unauthenticated users', function () {
        getJson(route('api.produtos.consulta-cadastro', ['codauxiliar' => '7896647027882']))
            ->assertUnauthorized();
    });

    it('returns unauthorized for inactive users', function () {
        /** @var User $user */
        $user = User::factory()->inactive()->create();
        $token = $user->createToken('test-device')->plainTextToken;

        getJson(route('api.produtos.consulta-cadastro', ['codauxiliar' => '7896647027882']), [
            'Authorization' => "Bearer {$token}",
        ])
            ->assertUnauthorized()
            ->assertJson(['message' => 'Sua conta está desativada.']);
    });
});
