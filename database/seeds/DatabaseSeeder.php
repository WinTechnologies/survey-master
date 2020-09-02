<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        DB::table('users')->insert(
            [
                'name' => "admin",
                'email' => "admin@survey-master.com",
                'password' => Hash::make('admin')
            ]
        );

        $this->call([
            SettingSeeder::class,
            ThemeSeeder::class
        ]);
    }
}
