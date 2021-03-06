<?php

use Illuminate\Database\Seeder;

class ThemeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('themes')->insert([
            'name' => 'Default',
            'img_url' => '',
            'size_ans' => 24,
            'size_ques' => 24,
            'text_color' => '#37404a',
            'border_color' => 'rgba(92,92,92,0.6)',
            'size_ans_img' => 24,
            'background_color' => '#FFFFFF',
            'button_color' => '#37404a',
            'footer_color' => '#ededed',
            'font_family' => 'Karla'
        ]);

        DB::table('themes')->insert([
            'name' => 'Banana',
            'img_url' => '',
            'size_ans' => 24,
            'size_ques' => 24,
            'text_color' => '#000000',
            'border_color' => '#000000',
            'size_ans_img' => 24,
            'background_color' => '#E4FF00',
            'button_color' => '#000000',
            'footer_color' => '#000000',
            'font_family' => 'Roboto'
        ]);
    }
}
