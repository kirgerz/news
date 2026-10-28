<x-layout title="Админ">
    <x-header />
    <main>
        <h2>Раздел администратора</h2>
        <h3>Статьи</h3>
        <div class="admin-list">
            @forelse ($news as $article)
                <div class="admin-row">
                    <div class="admin-details">
                        <p class="admin-heading">{{ $article->heading }}</p>
                        <p class="admin-meta">
                            №{{ $article->id }} · {{ $article->user->login }} · {{ $article->category->name }}
                        </p>
                        <span class="article-status {{ $article->is_blocked ? 'blocked' : '' }}">{{ $article->is_blocked ? 'Заблокирована' : 'Опубликована' }}</span>
                    </div>
                    <div class="admin-controls">
                        <a href="{{ route('articles.edit', $article) }}">Редактировать</a>
                        <form method="POST" action="{{ route('admin.articles.block', $article) }}" class="action-form">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="is_blocked" value="{{ $article->is_blocked ? 0 : 1 }}">
                            <button type="submit">{{ $article->is_blocked ? 'Разблокировать' : 'Блокировать' }}</button>
                        </form>
                        <form method="POST" action="{{ route('admin.articles.destroy', $article) }}" class="action-form" onsubmit="return confirm('Удалить статью?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="delete-button">Удалить</button>
                        </form>
                    </div>
                </div>
            @empty
                <p>Пока нет статей.</p>
            @endforelse
        </div>
        @if ($news->hasPages())
            <nav aria-label="Страницы статей" class="pagination">
                @if (! $news->onFirstPage())
                    <a href="{{ $news->previousPageUrl() }}">Назад</a>
                @endif
                <span>Страница {{ $news->currentPage() }} из {{ $news->lastPage() }}</span>
                @if ($news->hasMorePages())
                    <a href="{{ $news->nextPageUrl() }}">Далее</a>
                @endif
            </nav>
        @endif
        <h3>Изменение роли пользователя</h3>
        <form method="POST" action="{{ route('admin.users.role') }}">
            @csrf
            @method('PATCH')
            <p>
                <label for="user_id">Пользователь</label>
                <select id="user_id" name="user_id" required>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}" @selected(old('user_id') == $user->id)>{{ $user->login }} ({{ $user->role->name }})</option>
                    @endforeach
                </select>
            </p>
            <p>
                <label for="role_id">Новая роль</label>
                <select id="role_id" name="role_id" required>
                    @foreach ($roles as $role)
                        <option value="{{ $role->id }}" @selected(old('role_id') == $role->id)>{{ $role->name }}</option>
                    @endforeach
                </select>
            </p>
            <button type="submit">Изменить роль</button>
        </form>
    </main>
    <x-footer />
</x-layout>
