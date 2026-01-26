<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\GoogleAuthController;
use Illuminate\Support\Collection;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use App\Http\Controllers\AvaliacaoController;


Route::get('/', function () {
    return redirect('/admin');
});

Route::get('/test', function () {
    return view('test');
});

Route::get('/oauth/redirect/google', [GoogleAuthController::class, 'redirect'])->name('google.redirect');
Route::get('/oauth/callback/google', [GoogleAuthController::class, 'callback'])->name('google.callback');


Route::post('/avaliacao/salvar-resposta', [AvaliacaoController::class, 'salvarResposta'])
    ->name('avaliacao.salvar-resposta')
    ->middleware('auth');
