<?php

use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('settings')->insert([
            'name' => 'Reliability Questions',
            'value' => '1'
        ]);

        DB::table('settings')->insert([
            'name' => 'One response each person',
            'value' => '1'
        ]);

        DB::table('settings')->insert([
            'name' => 'Time Zone',
            'value' => 'Europe/London'
        ]);

        DB::table('settings')->insert([
            'name' => 'Color 1',
            'value' => '#edeef2'
        ]);

        DB::table('settings')->insert([
            'name' => 'Color 2',
            'value' => '#1f23ad'
        ]);
    }
}
