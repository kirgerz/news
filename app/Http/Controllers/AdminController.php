<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AdminController extends Controller
{
    /** показывает все статьи, пользователей и роли в админке */
    public function index(): View
    {
        return view('admin', [
            'news' => Article::with(['user', 'category'])->latest()->orderByDesc('id')->paginate(5),
            'users' => User::with('role')->orderBy('login')->get(),
            'roles' => Role::orderBy('id')->get(),
        ]);
    }

    /** удаляет выбранную статью */
    public function destroy(Article $article): RedirectResponse
    {
        $article->delete();

        return redirect()->route('admin')->with('status', 'Статья удалена.');
    }

    /** блокирует статью или снимает блокировку */
    public function setBlocked(Request $request, Article $article): RedirectResponse
    {
        $data = $request->validate(['is_blocked' => ['required', 'boolean']]);
        $article->is_blocked = $data['is_blocked'];
        $article->save();

        return redirect()->route('admin')->with('status', $article->is_blocked ? 'Статья заблокирована.' : 'Блокировка снята.');
    }

    /** изменяет роль пользователя и не позволяет убрать последнего администратора */
    public function updateRole(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'role_id' => ['required', 'integer', 'exists:roles,id'],
        ], [
            'user_id.required' => 'Выберите пользователя.',
            'user_id.exists' => 'Такого пользователя нет.',
            'role_id.required' => 'Выберите роль.',
            'role_id.exists' => 'Такой роли нет.',
        ]);

        DB::transaction(function () use ($data): void {
            $adminRole = Role::where('name', 'Администратор')->firstOrFail();
            $admins = User::where('role_id', $adminRole->id)->orderBy('id')->lockForUpdate()->get();
            $user = User::lockForUpdate()->findOrFail($data['user_id']);

            if ($user->role_id === $adminRole->id && (int) $data['role_id'] !== $adminRole->id && $admins->count() <= 1) {
                throw ValidationException::withMessages(['role_id' => 'Нельзя убрать роль у последнего администратора.']);
            }

            $user->role_id = $data['role_id'];
            $user->save();
        });

        return redirect()->route($request->user()->refresh()->hasRole('Администратор') ? 'admin' : 'home')->with('status', 'Роль пользователя изменена.');
    }
}
