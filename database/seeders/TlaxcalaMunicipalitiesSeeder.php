<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use DB;

class TlaxcalaMunicipalitiesSeeder extends Seeder
{
    public function run()
    {
        $municipalities = [
            ['idmunicipality'=>29001,'name'=>'Amaxac de Guerrero','states_idstates'=>29],
            ['idmunicipality'=>29002,'name'=>'Apetatitlán de Antonio Carvajal','states_idstates'=>29],
            ['idmunicipality'=>29003,'name'=>'Apizaco','states_idstates'=>29],
            ['idmunicipality'=>29004,'name'=>'Atlangatepec','states_idstates'=>29],
            ['idmunicipality'=>29005,'name'=>'Benito Juárez','states_idstates'=>29],
            ['idmunicipality'=>29006,'name'=>'Calpulalpan','states_idstates'=>29],
            ['idmunicipality'=>29007,'name'=>'Chiautempan','states_idstates'=>29],
            ['idmunicipality'=>29008,'name'=>'Contla de Juan Cuamatzi','states_idstates'=>29],
            ['idmunicipality'=>29009,'name'=>'Cuapiaxtla','states_idstates'=>29],
            ['idmunicipality'=>29010,'name'=>'Cuaxomulco','states_idstates'=>29],
            ['idmunicipality'=>29011,'name'=>'El Carmen Tequexquitla','states_idstates'=>29],
            ['idmunicipality'=>29012,'name'=>'Emiliano Zapata','states_idstates'=>29],
            ['idmunicipality'=>29013,'name'=>'Españita','states_idstates'=>29],
            ['idmunicipality'=>29014,'name'=>'Huamantla','states_idstates'=>29],
            ['idmunicipality'=>29015,'name'=>'Hueyotlipan','states_idstates'=>29],
            ['idmunicipality'=>29016,'name'=>'Ixtacuixtla de Mariano Matamoros','states_idstates'=>29],
            ['idmunicipality'=>29017,'name'=>'Ixtenco','states_idstates'=>29],
            ['idmunicipality'=>29018,'name'=>'La Magdalena Tlaltelulco','states_idstates'=>29],
            ['idmunicipality'=>29019,'name'=>'Lázaro Cárdenas','states_idstates'=>29],
            ['idmunicipality'=>29020,'name'=>'Mazatecochco de José María Morelos','states_idstates'=>29],
            ['idmunicipality'=>29021,'name'=>'Muñoz de Domingo Arenas','states_idstates'=>29],
            ['idmunicipality'=>29022,'name'=>'Nativitas','states_idstates'=>29],
            ['idmunicipality'=>29023,'name'=>'Panotla','states_idstates'=>29],
            ['idmunicipality'=>29024,'name'=>'Papalotla de Xicohténcatl','states_idstates'=>29],
            ['idmunicipality'=>29025,'name'=>'San Damián Texóloc','states_idstates'=>29],
            ['idmunicipality'=>29026,'name'=>'San Francisco Tetlanohcan','states_idstates'=>29],
            ['idmunicipality'=>29027,'name'=>'San Jerónimo Zacualpan','states_idstates'=>29],
            ['idmunicipality'=>29028,'name'=>'San José Teacalco','states_idstates'=>29],
            ['idmunicipality'=>29029,'name'=>'San Juan Huactzinco','states_idstates'=>29],
            ['idmunicipality'=>29030,'name'=>'San Lorenzo Axocomanitla','states_idstates'=>29],
            ['idmunicipality'=>29031,'name'=>'San Lucas Tecopilco','states_idstates'=>29],
            ['idmunicipality'=>29032,'name'=>'San Pablo del Monte','states_idstates'=>29],
            ['idmunicipality'=>29033,'name'=>'Santa Ana Nopalucan','states_idstates'=>29],
            ['idmunicipality'=>29034,'name'=>'Santa Apolonia Teacalco','states_idstates'=>29],
            ['idmunicipality'=>29035,'name'=>'Santa Catarina Ayometla','states_idstates'=>29],
            ['idmunicipality'=>29036,'name'=>'Santa Cruz Quilehtla','states_idstates'=>29],
            ['idmunicipality'=>29037,'name'=>'Santa Cruz Tlaxcala','states_idstates'=>29],
            ['idmunicipality'=>29038,'name'=>'Santa Isabel Xiloxoxtla','states_idstates'=>29],
            ['idmunicipality'=>29039,'name'=>'Tenancingo','states_idstates'=>29],
            ['idmunicipality'=>29040,'name'=>'Teolocholco','states_idstates'=>29],
            ['idmunicipality'=>29041,'name'=>'Tepetitla de Lardizábal','states_idstates'=>29],
            ['idmunicipality'=>29042,'name'=>'Tepeyanco','states_idstates'=>29],
            ['idmunicipality'=>29043,'name'=>'Terrenate','states_idstates'=>29],
            ['idmunicipality'=>29044,'name'=>'Tetla de la Solidaridad','states_idstates'=>29],
            ['idmunicipality'=>29045,'name'=>'Tetlatlahuca','states_idstates'=>29],
            ['idmunicipality'=>29046,'name'=>'Tlaxcala','states_idstates'=>29],
            ['idmunicipality'=>29047,'name'=>'Tlaxco','states_idstates'=>29],
            ['idmunicipality'=>29048,'name'=>'Tocatlán','states_idstates'=>29],
            ['idmunicipality'=>29049,'name'=>'Totolac','states_idstates'=>29],
            ['idmunicipality'=>29050,'name'=>'Xaloztoc','states_idstates'=>29],
            ['idmunicipality'=>29051,'name'=>'Xaltocan','states_idstates'=>29],
            ['idmunicipality'=>29052,'name'=>'Xicohtzinco','states_idstates'=>29],
            ['idmunicipality'=>29053,'name'=>'Yauhquemehcan','states_idstates'=>29],
            ['idmunicipality'=>29054,'name'=>'Zacatelco','states_idstates'=>29],
            ['idmunicipality'=>29055,'name'=>'Zitlaltepec de Trinidad Sánchez Santos','states_idstates'=>29]
        ];
        DB::table('municipality')->insert($municipalities);
    }
}
