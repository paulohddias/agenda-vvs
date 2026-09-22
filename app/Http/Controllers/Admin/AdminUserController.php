<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * Gerencia quem tem acesso ao painel administrativo. Diferente da tela de Perfil
 * (onde cada um troca os próprios dados), aqui um admin cria e edita outros acessos
 * sem precisar saber a senha atual de ninguém.
 */
class AdminUserController extends Controller
{
    public function index(): View
    {
        return view('admin.users.index', [
            'users' => User::where('role', User::ROLE_ADMIN)->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.users.form', ['user' => new User]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => User::ROLE_ADMIN,
            'email_verified_at' => now(),
        ]);

        return redirect()->route('admin.users.index')->with('status', 'Usuário criado.');
    }

    public function edit(User $user): View
    {
        abort_unless($user->isAdmin(), 404);

        return view('admin.users.form', ['user' => $user]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->isAdmin(), 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user)],
            // Trocar a senha aqui é opcional: deixando em branco, mantém a atual.
            'password' => ['nullable', 'confirmed', Password::defaults()],
        ]);

        $user->name = $data['name'];
        $user->email = $data['email'];

        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        $user->save();

        return redirect()->route('admin.users.index')->with('status', 'Usuário atualizado.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->isAdmin(), 404);

        if ($user->is($request->user())) {
            return back()->with('error', 'Você não pode excluir o próprio usuário logado.');
        }

        if (User::where('role', User::ROLE_ADMIN)->count() <= 1) {
            return back()->with('error', 'Tem que sobrar pelo menos um usuário do painel.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')->with('status', 'Usuário excluído.');
    }
}
