<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Models\User;
use App\Services\AccessService;
use App\Services\UserService;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(private UserService $service)
    {
        $this->middleware('admin');
    }

    public function index(Request $request)
    {
        return view('users.index', ['users' => $this->service->paginate($request->only('search', 'status', 'role'))]);
    }

    public function create()
    {
        return view('users.form', ['user' => new User(['status' => true, 'role' => User::ROLE_USER])]);
    }

    public function store(UserRequest $request)
    {
        $user = $this->service->create($request->validated());

        if ($user->isAdmin()) {
            return redirect()->route('users.index')->with('success', 'Admin user created.');
        }

        return redirect()->route('users.access', $user)->with('success', 'User created. Now choose the menus and permissions.');
    }

    public function edit(User $user)
    {
        return view('users.form', compact('user'));
    }

    public function update(UserRequest $request, User $user)
    {
        $this->service->update($user, $request->validated());

        return redirect()->route('users.index')->with('success', 'User updated.');
    }

    public function destroy(User $user)
    {
        $this->service->delete($user);

        return back()->with('success', 'User deleted.');
    }

    /** The "Manage Access" screen: menus, submenus and actions for one user. */
    public function access(User $user, AccessService $access)
    {
        return view('users.access', [
            'user' => $user,
            'tree' => $access->assignableTree(),
            'granted' => $user->menuPermissions()->with('menu')->get()->mapWithKeys(fn ($permission) => [$permission->menu->unique_id => $permission->actions])->all(),
        ]);
    }

    public function updateAccess(Request $request, User $user, AccessService $access)
    {
        $data = $request->validate([
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['array'],
            'permissions.*.*' => ['string', 'in:view,create,edit,delete'],
        ]);

        $access->sync($user, $data['permissions'] ?? []);

        return redirect()->route('users.index')->with('success', "Access updated for {$user->name}.");
    }
}
