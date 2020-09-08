<?php

use Illuminate\Database\Seeder;

class PopulationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('populations')->insert(
            [
                'group_name' => "Group 1",
                'parent_set'    => 0,
                'size_set'      => 10,
                'type'          =>  'normal',
                'utm'           =>  ''
            ]
        );

        DB::table('populations')->insert(
            [
                'group_name' => "Area A",
                'parent_set'    => 1,
                'size_set'      => 50,
                'type'          =>  'normal',
                'utm'           =>  'NfV3OTuK'
            ]
        );

        DB::table('populations')->insert(
            [
                'group_name' => "Area B",
                'parent_set'    => 1,
                'size_set'      => 30,
                'type'          =>  'normal',
                'utm'           =>  'xuUuTSanC3'
            ]
        );

        DB::table('populations')->insert(
            [
                'group_name' => "Area C",
                'parent_set'    => 1,
                'size_set'      => 10,
                'type'          =>  'normal',
                'utm'           =>  'Fc9epxujSp'
            ]
        );
    }
}
