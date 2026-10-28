<x-layout title="Регистрация">
    <x-header />
    <main>
        <h2>Регистрация</h2>
        <form method="POST" action="{{ route('register.store') }}">
            @csrf
            <p>
                <label for="login">Логин</label>
                <input id="login" name="login" value="{{ old('login') }}" minlength="3" maxlength="30" autocomplete="username" required>
                <small>От 3 до 30 символов: латинские буквы, цифры, дефис, подчёркивание.</small>
            </p>
            <p>
                <label for="password">Пароль</label>
                <input id="password" name="password" type="password" minlength="6" autocomplete="new-password" required>
                <small>Не меньше 6 символов.</small>
            </p>
            <p>
                <label for="password_confirmation">Подтверждение пароля</label>
                <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required>
            </p>
            <button type="submit">Зарегистрироваться</button>
        </form>
        <p>Уже есть аккаунт? <a href="{{ route('login') }}">Войти</a></p>
    </main>
    <x-footer />
</x-layout>
