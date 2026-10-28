<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\Category;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ArticleSeeder extends Seeder
{
    public function run(): void
    {
        $author = User::firstOrCreate(['login' => 'redactor'], ['password' => Str::random(40), 'role_id' => Role::where('name', 'Журналист')->firstOrFail()->id]);
        $news = [
            ['heading' => 'В колледже начались занятия по Laravel', 'category' => 'Программирование', 'text' => 'На занятиях студенты будут создавать новостной сайт. Первая работа посвящена маршрутам, шаблонам Blade и вёрстке страниц.'],
            ['heading' => 'Как выбрать ноутбук для учёбы', 'category' => 'Компьютеры', 'text' => 'При выборе ноутбука стоит обратить внимание на объём оперативной памяти, наличие SSD и время работы от аккумулятора. Для учёбы также важны удобная клавиатура и небольшой вес.'],
            ['heading' => 'Студенты создали свои первые сайты', 'category' => 'Программирование', 'text' => 'На практическом занятии студенты подготовили простые страницы на HTML и CSS. В проектах использовали заголовки, списки, таблицы и формы.'],
        ];

        foreach ($news as $index => $item) {
            $category = Category::where('name', $item['category'])->firstOrFail();
            Article::firstOrCreate(['heading' => $item['heading'], 'user_id' => $author->id], [
                'category_id' => $category->id,
                'text' => $item['text'],
                'created_at' => now()->subDays($index),
            ]);
        }
    }
}
