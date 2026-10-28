<header class="site-header">
    <nav aria-label="Основные разделы">
        <a href="{{ route('home') }}">Главная</a>
        <a href="{{ route('category') }}">Категории</a>
        @if (auth()->user()?->hasRole('Журналист', 'Администратор'))
            <a href="{{ route('journalist') }}">Журналист</a>
        @endif
        @if (auth()->user()?->hasRole('Администратор'))
            <a href="{{ route('admin') }}">Админ</a>
        @endif
    </nav>
    <div class="account">
        @auth
            <span>{{ auth()->user()->login }} ({{ auth()->user()->role->name }})</span>
            <form method="POST" action="{{ route('logout') }}" class="logout-form">
                @csrf
                <button type="submit">Выйти</button>
            </form>
        @else
            <a href="{{ route('login') }}">Вход</a>
            <a href="{{ route('register') }}">Регистрация</a>
        @endauth
    </div>
</header>
