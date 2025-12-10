<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PedidoController;
use App\Http\Controllers\Api\ProdutoController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rotas Públicas
|--------------------------------------------------------------------------
*/

Route::post('/login', [AuthController::class, 'login'])->name('api.login');

/*
|--------------------------------------------------------------------------
| Rotas Protegidas (requerem autenticação via token)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum', 'active'])->group(function () {
    // Autenticação
    Route::get('/user', [AuthController::class, 'user'])->name('api.user');
    Route::post('/logout', [AuthController::class, 'logout'])->name('api.logout');
    Route::post('/logout-all', [AuthController::class, 'logoutAll'])->name('api.logout-all');

    // Produtos (WinThor)
    Route::get('/produtos/consulta-cadastro', [ProdutoController::class, 'consultaCadastro'])
        ->name('api.produtos.consulta-cadastro');
    Route::get('/produtos/consulta-estoque', [ProdutoController::class, 'consultaEstoque'])
        ->name('api.produtos.consulta-estoque');
    Route::get('/produtos/consulta-preco', [ProdutoController::class, 'consultaPreco'])
        ->name('api.produtos.consulta-preco');

    // Pedidos (WinThor)
    Route::post('/pedidos', [PedidoController::class, 'criar'])
        ->name('api.pedidos.criar');
});
