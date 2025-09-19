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

Route::get('/categories',[MunicipalityController::class,'index']);
Route::get('/categories/{id}',[MunicipalityController::class,'show']);
Route::get('/categories/{id}',[MunicipalityController::class,'show']);
Route::get('/categories/{id}/subcatgories',[MunicipalityController::class,'show']);

Route::get('/layers',[LayerController::class,'index']);
Route::get('/layers/minimal/nacional',[LayerController::class,'getMinimalNational']);
Route::get('/layers/minimal/states/{stateid}',[LayerController::class,'getMinimalStateById']);
Route::get('/layers/minimal/municipality/{municipalityid}',[LayerController::class,'getMinimalMunicipalityById']);

Route::get('/layers/countries',[LayerController::class,'getAllCountryLayers']);
Route::get('/layers/countries/{countryId}',[LayerController::class,'getLayerByCountry']);
//Route::get('/layers/countries/{countryId}/states',[LayerController::class,'getAllStatesLayers']);
Route::get('/layers/states',[LayerController::class,'getAllStatesLayers']);
//Route::get('/layers/countries/{countryId}/states/{stateId}',[LayerController::class,'getLayersByState']);
Route::get('/layers/states/{stateId}',[LayerController::class,'getLayersByState']);
Route::get('/states/countries/{countryId}/municipalities',[LayerController::class,'getAllMunicipalitiesLayers']);
//Route::get('/countries/{countryId}/states/{stateId}/municipalities/{municipalityId}',[LayerController::class,'getLayersByMunicipality']);
Route::get('/layers/states/{stateId}/municipalities/{municipalityId}',[LayerController::class,'getLayersByMunicipality']);
//Route::get('/countries/{countryId}/states/{stateId}/municipalities/{municipalityId}/sections',[LayerController::class,'getAllsectionsLayers']);
Route::get('/countries/{countryId}/states/{stateId}/municipalities/{municipalityId}/sections/{sectionId}',[LayerController::class,'getLayersBySection']);

Route::post('/layers',[LayerController::class,'store']);
Route::get('/layers/{id}',[LayerController::class,'show']);
Route::delete('/layers/{id}',[LayerController::class,'destroy']);
Route::get('/layers/{id}/download',[LayerController::class,'downloadFile']);
Route::get('/layers/{id}/config',[LayerController::class,'getConfigById']);

