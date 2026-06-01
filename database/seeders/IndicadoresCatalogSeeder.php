<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use DB;

/**
 * verificado = true  → clave confirmed against the live INEGI API
 * verificado = false → clave is an estimate; may return ErrorCode 100
 *                      User can look up the real clave at:
 *                      https://www.inegi.org.mx/app/indicadores/
 */
class IndicadoresCatalogSeeder extends Seeder
{
    public function run()
    {
        DB::table('indicadores_catalog')->truncate();

        $indicadores = [
            // ── Demografía ── confirmed ───────────────────────────────────
            ['clave'=>'1002000001','nombre'=>'Población total',             'categoria'=>'Demografía','unidad'=>'Personas',         'fuente'=>'BISE','verificado'=>true, 'descripcion'=>'Número total de personas en el territorio'],
            ['clave'=>'1002000002','nombre'=>'Población masculina',         'categoria'=>'Demografía','unidad'=>'Personas',         'fuente'=>'BISE','verificado'=>false,'descripcion'=>'Total de hombres'],
            ['clave'=>'1002000003','nombre'=>'Población femenina',          'categoria'=>'Demografía','unidad'=>'Personas',         'fuente'=>'BISE','verificado'=>false,'descripcion'=>'Total de mujeres'],
            ['clave'=>'1002000004','nombre'=>'Densidad de población',       'categoria'=>'Demografía','unidad'=>'Hab/km²',          'fuente'=>'BISE','verificado'=>false,'descripcion'=>'Habitantes por kilómetro cuadrado'],
            ['clave'=>'1002000007','nombre'=>'Tasa de crecimiento',         'categoria'=>'Demografía','unidad'=>'Porcentaje',       'fuente'=>'BISE','verificado'=>false,'descripcion'=>'Variación porcentual anual de la población'],

            // ── Vivienda ─────────────────────────────────────────────────
            ['clave'=>'1003000001','nombre'=>'Viviendas particulares',      'categoria'=>'Vivienda',  'unidad'=>'Viviendas',        'fuente'=>'BISE','verificado'=>false,'descripcion'=>'Total de viviendas con al menos una persona residente'],
            ['clave'=>'3003001006','nombre'=>'Viviendas con agua entubada', 'categoria'=>'Vivienda',  'unidad'=>'Viviendas',        'fuente'=>'BISE','verificado'=>false,'descripcion'=>'Viviendas con agua entubada dentro o fuera del hogar'],
            ['clave'=>'3003001010','nombre'=>'Viviendas con drenaje',       'categoria'=>'Vivienda',  'unidad'=>'Viviendas',        'fuente'=>'BISE','verificado'=>false,'descripcion'=>'Viviendas conectadas a la red pública de drenaje'],
            ['clave'=>'3003001007','nombre'=>'Viviendas con electricidad',  'categoria'=>'Vivienda',  'unidad'=>'Viviendas',        'fuente'=>'BISE','verificado'=>false,'descripcion'=>'Viviendas con servicio de energía eléctrica'],

            // ── Educación ─────────────────────────────────────────────────
            ['clave'=>'6207019014','nombre'=>'Grado promedio de escolaridad','categoria'=>'Educación','unidad'=>'Años',             'fuente'=>'BISE','verificado'=>false,'descripcion'=>'Promedio de años de escolaridad, población 15+'],
            ['clave'=>'1006000012','nombre'=>'Tasa de analfabetismo',       'categoria'=>'Educación', 'unidad'=>'Porcentaje',       'fuente'=>'BISE','verificado'=>false,'descripcion'=>'Porcentaje de personas de 15+ que no saben leer ni escribir'],

            // ── Salud ─────────────────────────────────────────────────────
            ['clave'=>'1004000002','nombre'=>'Mortalidad infantil',         'categoria'=>'Salud',     'unidad'=>'Por 1,000 nac.v.', 'fuente'=>'BISE','verificado'=>false,'descripcion'=>'Defunciones de menores de 1 año por cada 1,000 nacidos vivos'],
            ['clave'=>'1004000001','nombre'=>'Esperanza de vida al nacer',  'categoria'=>'Salud',     'unidad'=>'Años',             'fuente'=>'BISE','verificado'=>false,'descripcion'=>'Número promedio de años de vida esperados al nacer'],

            // ── Empleo ── confirmed ───────────────────────────────────────
            ['clave'=>'444612',    'nombre'=>'Tasa de desocupación',        'categoria'=>'Empleo',    'unidad'=>'Porcentaje',       'fuente'=>'BIE', 'verificado'=>true, 'descripcion'=>'Porcentaje de la PEA sin empleo que busca activamente'],
            ['clave'=>'1005000001','nombre'=>'Población económicamente activa','categoria'=>'Empleo', 'unidad'=>'Personas',         'fuente'=>'BISE','verificado'=>false,'descripcion'=>'Personas de 15+ que trabajaron o buscaron trabajo'],

            // ── Economía ── confirmed ─────────────────────────────────────
            ['clave'=>'381016',    'nombre'=>'PIB trimestral',              'categoria'=>'Economía',  'unidad'=>'Millones de pesos','fuente'=>'BIE', 'verificado'=>true, 'descripcion'=>'Producto Interno Bruto a precios constantes, base 2013'],
            ['clave'=>'216064',    'nombre'=>'Inflación (INPC)',            'categoria'=>'Economía',  'unidad'=>'Índice',           'fuente'=>'BIE', 'verificado'=>false,'descripcion'=>'Índice Nacional de Precios al Consumidor'],
            ['clave'=>'6016000001','nombre'=>'PIB por entidad',             'categoria'=>'Economía',  'unidad'=>'Millones de pesos','fuente'=>'BIE', 'verificado'=>false,'descripcion'=>'PIB a precios corrientes por entidad federativa'],
        ];

        DB::table('indicadores_catalog')->insert($indicadores);
    }
}
