<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    /** показывает форму регистрации */
    public function showRegister(): View
    {
        return view('register');
    }

    /** проверяет данные, создаёт пользователя и выполняет вход */
    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'login' => ['bail', 'required', 'string', 'min:3', 'max:30', 'alpha_dash:ascii', 'unique:users,login'],
            'password' => ['bail', 'required', 'string', 'min:6', 'confirmed'],
        ], [
            'login.required' => 'Введите логин.',
            'login.min' => 'В логине должно быть не меньше 3 символов.',
            'login.max' => 'В логине должно быть не больше 30 символов.',
            'login.alpha_dash' => 'Для логина используйте латинские буквы, цифры, дефис или подчёркивание.',
            'login.unique' => 'Этот логин уже занят.',
            'password.required' => 'Введите пароль.',
            'password.min' => 'В пароле должно быть не меньше 6 символов.',
            'password.confirmed' => 'Пароли не совпадают.',
        ]);

        $user = User::create($data)->refresh();
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('home')->with('status', 'Вы зарегистрированы.');
    }

    /** показывает форму входа */
    public function showLogin(): View
    {
        return view('login');
    }

    /** проверяет логин и пароль и выполняет вход в аккаунт */
    public function login(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'login' => ['required', 'string', 'max:30'],
            'password' => ['required', 'string'],
        ], [
            'login.required' => 'Введите логин.',
            'login.max' => 'В логине должно быть не больше 30 символов.',
            'password.required' => 'Введите пароль.',
        ]);

        if (! Auth::attempt($data)) {
            return back()->withErrors(['login' => 'Неверный логин или пароль.'])->onlyInput('login');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('home'));
    }

    /** завершает сеанс пользователя и возвращает на главную */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('status', 'Вы вышли из аккаунта.');
    }
}
