<x-layout title="Категория">
    <x-header />
    <main>
        <h2>Категория: {{ $category->name }}</h2>
        @forelse ($news as $article)
            <article class="news-item">
                <h3>{{ $article->heading }}</h3>
                <p class="article-text">{{ $article->text }}</p>
                <p class="news-meta">
                    Категория: <a href="{{ route('category', $article->category) }}">{{ $article->category->name }}</a>;
                    автор: {{ $article->user->login }};
                    <time datetime="{{ $article->created_at->toIso8601String() }}">{{ $article->created_at->format('d.m.Y H:i') }}</time>
                </p>
                @if (auth()->user()?->hasRole('Администратор') || (auth()->user()?->hasRole('Журналист') && auth()->id() === $article->user_id))
                    <a href="{{ route('articles.edit', $article) }}">Редактировать</a>
                @endif
            </article>
        @empty
            <p>В этой категории пока нет статей.</p>
        @endforelse
        @if ($news->hasPages())
            <nav aria-label="Страницы категории" class="pagination">
                @if (! $news->onFirstPage())
                    <a href="{{ $news->previousPageUrl() }}">Назад</a>
                @endif
                <span>Страница {{ $news->currentPage() }} из {{ $news->lastPage() }}</span>
                @if ($news->hasMorePages())
                    <a href="{{ $news->nextPageUrl() }}">Далее</a>
                @endif
            </nav>
        @endif
    </main>
    <x-footer />
</x-layout>
