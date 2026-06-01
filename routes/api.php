<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\LayerController;
use App\Http\Controllers\Api\StateController;
use App\Http\Controllers\Api\MunicipalityController;
use App\Http\Controllers\Api\SectionController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Api\TableDataController;
use App\Http\Controllers\Api\InegiController;
use App\Http\Controllers\Api\GisLayerController;
use App\Http\Controllers\Api\WhatsappController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\DashboardController;

// ── Public geographic data ─────────────────────────────────────────────────────
Route::get('/states',[StateController::class,'index']);
Route::get('/states/{id}',[StateController::class,'show']);

Route::get('/municipalities',[MunicipalityController::class,'index']);
Route::get('/municipalities/{id}',[MunicipalityController::class,'show']);

Route::get('/secciones',[SectionController::class,'index']);

Route::get('/layers',[LayerController::class,'index']);
Route::get('/layers/minimal',[LayerController::class,'getMinimalAllLayers']);
Route::get('/layers/minimal/nacional',[LayerController::class,'getMinimalNational']);
Route::get('/layers/minimal/states/{stateid}',[LayerController::class,'getMinimalStateById']);
Route::get('/layers/minimal/municipality/{municipalityid}',[LayerController::class,'getMinimalMunicipalityById']);
Route::get('/layers/minimal/states',[LayerController::class,'getMinimalStates']);
Route::get('/layers/minimal/municipality',[LayerController::class,'getMinimalMunicipality']);
Route::get('/layers/minimal/sections',[LayerController::class,'getMinimalSections']);
Route::get('/layers/countries',[LayerController::class,'getAllCountryLayers']);
Route::get('/layers/countries/{countryId}',[LayerController::class,'getLayerByCountry']);
Route::get('/layers/states',[LayerController::class,'getAllStatesLayers']);
Route::get('/layers/states/{stateId}',[LayerController::class,'getLayersByState']);
Route::get('/states/countries/{countryId}/municipalities',[LayerController::class,'getAllMunicipalitiesLayers']);
Route::get('/layers/states/{stateId}/municipalities/{municipalityId}',[LayerController::class,'getLayersByMunicipality']);
Route::get('/countries/{countryId}/states/{stateId}/municipalities/{municipalityId}/sections/{sectionId}',[LayerController::class,'getLayersBySection']);
Route::post('/layers',[LayerController::class,'store']);
Route::get('/layers/{id}',[LayerController::class,'show']);
Route::delete('/layers/{id}',[LayerController::class,'destroy']);
Route::get('/layers/{id}/download',[LayerController::class,'downloadFile']);
Route::post('/layers/config/{id}',[LayerController::class,'saveLayerConfig']);
Route::put('/layers/config/{id}',[LayerController::class,'updateLayerConfig']);
Route::get('/layers/config/{id}',[LayerController::class,'getConfigById']);

// ── INEGI ──────────────────────────────────────────────────────────────────────
Route::get('/inegi/denue', [InegiController::class, 'denue']);
Route::get('/inegi/indicadores', [InegiController::class, 'indicadores']);
Route::get('/inegi/catalogo', [InegiController::class, 'catalogo']);
Route::post('/inegi/import-catalog', [InegiController::class, 'importCatalog']);
Route::get('/inegi/boundaries/states', [InegiController::class, 'boundariesStates']);

// ── GIS Layer Builder ─────────────────────────────────────────────────────────
Route::prefix('gis')->group(function () {
    Route::get('/layers',               [GisLayerController::class, 'build']);
    Route::get('/geo/states',           [GisLayerController::class, 'geoStates']);
    Route::get('/geo/municipalities',   [GisLayerController::class, 'geoMunicipalities']);
    Route::get('/years',                [GisLayerController::class, 'availableYears']);
    Route::post('/import-indicators',   [GisLayerController::class, 'importIndicators']);
});

// ── Auth ───────────────────────────────────────────────────────────────────────
Route::post('login', [AuthController::class, 'login']);
Route::middleware('auth:api')->group(function () {
    Route::get('me',      [AuthController::class, 'me']);
    Route::post('logout', [AuthController::class, 'logout']);
});

// ── Protected routes (all authenticated users) ────────────────────────────────
Route::middleware('auth:api')->group(function () {

    // Dashboard stats & rankings — all authenticated roles
    Route::get('/dashboard/stats',    [DashboardController::class, 'stats']);
    Route::get('/dashboard/rankings', [DashboardController::class, 'rankings']);

    // Semaforo config — national_admin only
    Route::middleware('role:national_admin')->group(function () {
        Route::put('/dashboard/semaforo-config', [DashboardController::class, 'updateSemaforoConfig']);
    });

    // Table data — all roles can read and submit records (field_workforce included)
    Route::get('/table/configs',             [TableDataController::class, 'listConfigs']);
    Route::get('/table/config/{configId}',   [TableDataController::class, 'getTableConfig']);
    Route::get('/table/data/all',            [TableDataController::class, 'getAllData']);
    Route::get('/table/{config}/data',       [TableDataController::class, 'getData']);
    Route::post('/table/{config}/data',      [TableDataController::class, 'store']);
    Route::get('/table/{config}/csv-template',   [TableDataController::class, 'csvTemplate']);
    Route::post('/table/{config}/upload-media',  [TableDataController::class, 'uploadMedia']);

    // Table config write / delete + CSV import — admin only
    Route::middleware('role:admin')->group(function () {
        Route::post('/table/config',                  [TableDataController::class, 'saveTableConfig']);
        Route::put('/table/config/{configId}',        [TableDataController::class, 'saveTableConfig']);
        Route::delete('/table/config/{configId}',     [TableDataController::class, 'deleteTableConfig']);
        Route::post('/table/{config}/upload-csv',     [TableDataController::class, 'uploadCsv']);
    });

    // WhatsApp — admin and municipal_admin
    Route::middleware('role:admin,municipal_admin')->prefix('whatsapp')->group(function () {
        Route::get('/status',              [WhatsappController::class, 'status']);
        Route::post('/sessions',           [WhatsappController::class, 'createSession']);
        Route::delete('/sessions/{id}',    [WhatsappController::class, 'removeSession']);
        Route::post('/campaign/{config}',  [WhatsappController::class, 'sendCampaign']);
    });

    // Layer assignment management — admin only
    Route::middleware('role:admin')->group(function () {
        Route::get('/layers/{id}/assignments',              [LayerController::class, 'getAssignments']);
        Route::post('/layers/{id}/assignments',             [LayerController::class, 'addAssignment']);
        Route::delete('/layers/{id}/assignments/{userId}',  [LayerController::class, 'removeAssignment']);
    });

    // User management — admin + all roles that manage sub-roles
    Route::middleware('role:admin,national_admin,estatal_admin,municipal_admin,seccion_admin')->prefix('users')->group(function () {
        Route::get('/',                    [UserController::class, 'index']);
        Route::post('/',                   [UserController::class, 'store']);
        Route::put('/{id}',               [UserController::class, 'update']);
        Route::delete('/{id}',            [UserController::class, 'destroy']);
        Route::get('/seccion-admins',      [UserController::class, 'seccionAdmins']);
        Route::get('/national-admins',     [UserController::class, 'nationalAdmins']);
    });
});
