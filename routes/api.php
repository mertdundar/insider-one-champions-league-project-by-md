<?php

use App\Http\Controllers\LeagueController;
use Illuminate\Support\Facades\Route;

Route::get('/state', [LeagueController::class, 'state']);
Route::post('/fixtures/generate', [LeagueController::class, 'generate']);
Route::post('/play-next', [LeagueController::class, 'playNext']);
Route::post('/play-all', [LeagueController::class, 'playAll']);
Route::put('/fixtures/{id}', [LeagueController::class, 'editFixture'])->whereNumber('id');
Route::post('/reset', [LeagueController::class, 'reset']);
