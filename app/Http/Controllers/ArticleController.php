<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ArticleController extends Controller
{
    /** показывает главную страницу со статьями от новых к старым */
    public function index(): View
    {
        $news = Article::where('is_blocked', false)->with(['user', 'category'])->latest()->orderByDesc('id')->paginate(5);

        return view('index', ['news' => $news]);
    }

    /** показывает статьи выбранной категории */
    public function category(?Category $category = null): View
    {
        $category ??= Category::where('name', 'Программирование')->firstOrFail();
        $news = $category->articles()->where('is_blocked', false)->with(['user', 'category'])->latest()->orderByDesc('id')->paginate(5);

        return view('category', ['category' => $category, 'news' => $news]);
    }

    /** показывает форму новой статьи и список своих статей */
    public function create(Request $request): View
    {
        return view('journalist', [
            'article' => null,
            'categories' => Category::orderBy('id')->get(),
            'ownArticles' => $request->user()->articles()->with('category')->latest()->orderByDesc('id')->paginate(5),
        ]);
    }

    /** проверяет и сохраняет новую статью от имени вошедшего пользователя */
    public function store(Request $request): RedirectResponse
    {
        $request->user()->articles()->create($this->validateArticle($request));

        return redirect()->route('home')->with('status', 'Статья опубликована.');
    }

    /** показывает форму редактирования автору статьи или администратору */
    public function edit(Request $request, Article $article): View
    {
        abort_unless($request->user()->hasRole('Администратор') || $article->user_id === $request->user()->id, 403);

        return view('journalist', [
            'article' => $article,
            'categories' => Category::orderBy('id')->get(),
            'ownArticles' => $request->user()->articles()->with('category')->latest()->orderByDesc('id')->paginate(5),
        ]);
    }

    /** сохраняет изменения статьи от автора или администратора */
    public function update(Request $request, Article $article): RedirectResponse
    {
        abort_unless($request->user()->hasRole('Администратор') || $article->user_id === $request->user()->id, 403);
        $article->update($this->validateArticle($request));

        return redirect()->route('home')->with('status', 'Изменения сохранены.');
    }

    /**
     * проверяет поля формы статьи и возвращает проверенные данные
     *
     * @return array{heading: string, category_id: int|string, text: string}
     */
    private function validateArticle(Request $request): array
    {
        return $request->validate([
            'heading' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'text' => ['required', 'string', 'max:50000'],
        ], [
            'heading.required' => 'Введите заголовок статьи.',
            'heading.max' => 'Заголовок должен быть не длиннее 255 символов.',
            'category_id.required' => 'Выберите категорию.',
            'category_id.integer' => 'Выберите категорию из списка.',
            'category_id.exists' => 'Такой категории нет.',
            'text.required' => 'Введите текст статьи.',
            'text.max' => 'Текст должен быть не длиннее 50 000 символов.',
        ]);
    }
}
