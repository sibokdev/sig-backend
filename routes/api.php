<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\LayerController;
use App\Http\Controllers\Api\StateController;
use App\Http\Controllers\Api\MunicipalityController;

Route::get('/states',[StateController::class,'index']);
Route::get('/states/{id}',[StateController::class,'show']);

Route::get('/municipalities',[MunicipalityController::class,'index']);
Route::get('/municipalities/{id}',[MunicipalityController::class,'show']);

Route::get('/layers',[LayerController::class,'index']);
Route::post('/layers',[LayerController::class,'store']);
Route::get('/layers/{id}',[LayerController::class,'show']);
Route::delete('/layers/{id}',[LayerController::class,'destroy']);
Route::get('/layers/{id}/download',[LayerController::class,'downloadFile']);
