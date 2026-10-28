<x-layout title="Вход">
    <x-header />
    <main>
        <h2>Вход</h2>
        <form method="POST" action="{{ route('login.store') }}">
            @csrf
            <p>
                <label for="login">Логин</label>
                <input id="login" name="login" value="{{ old('login') }}" maxlength="30" autocomplete="username" required>
            </p>
            <p>
                <label for="password">Пароль</label>
                <input id="password" name="password" type="password" autocomplete="current-password" required>
            </p>
            <button type="submit">Войти</button>
        </form>
        <p>Нет аккаунта? <a href="{{ route('register') }}">Зарегистрироваться</a></p>
    </main>
    <x-footer />
</x-layout>
