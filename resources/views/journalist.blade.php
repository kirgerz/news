<x-layout title="Журналист">
    <x-header />
    <main>
        <h2>Раздел журналиста</h2>
        <h3>{{ $article ? 'Редактирование статьи' : 'Новая статья' }}</h3>
        @if ($article?->is_blocked)
            <p>Статья заблокирована администратором и не отображается в ленте.</p>
        @endif
        <form method="POST" action="{{ $article ? route('articles.update', $article) : route('articles.store') }}" class="article-form">
            @csrf
            @if ($article)
                @method('PUT')
            @endif
            <div class="editor-top">
            <p>
                <label for="heading">Заголовок</label>
                <input id="heading" name="heading" type="text" value="{{ old('heading', $article?->heading) }}" maxlength="255" required>
            </p>
            <p>
                <label for="category_id">Категория</label>
                <select id="category_id" name="category_id" required>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected(old('category_id', $article?->category_id) == $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </p>
            </div>
            <p>
                <label for="text">Текст статьи</label>
                <textarea id="text" name="text" rows="9" maxlength="50000" required>{{ old('text', $article?->text) }}</textarea>
            </p>
            <div class="editor-actions">
                <button type="submit">{{ $article ? 'Сохранить изменения' : 'Опубликовать' }}</button>
            </div>
        </form>
        @if ($article)
            <p><a href="{{ route('journalist') }}">Написать новую статью</a></p>
        @endif
        <h3>Мои статьи</h3>
        <ul>
            @forelse ($ownArticles as $ownArticle)
                <li><a href="{{ route('articles.edit', $ownArticle) }}">{{ $ownArticle->heading }}</a> — {{ $ownArticle->category->name }} @if ($ownArticle->is_blocked) (заблокирована) @endif</li>
            @empty
                <li>Вы ещё не опубликовали ни одной статьи.</li>
            @endforelse
        </ul>
        @if ($ownArticles->hasPages())
            <nav aria-label="Страницы моих статей" class="pagination">
                @if (! $ownArticles->onFirstPage())
                    <a href="{{ $ownArticles->previousPageUrl() }}">Назад</a>
                @endif
                <span>Страница {{ $ownArticles->currentPage() }} из {{ $ownArticles->lastPage() }}</span>
                @if ($ownArticles->hasMorePages())
                    <a href="{{ $ownArticles->nextPageUrl() }}">Далее</a>
                @endif
            </nav>
        @endif
    </main>
    <x-footer />
</x-layout>
