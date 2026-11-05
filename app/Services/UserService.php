<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class UserService extends BaseService
{
    public function __construct(private ImageService $images)
    {
    }

    public function paginate(array $filters)
    {
        return User::rememberCache($this->listKey($filters), function () use ($filters) {
            $query = User::withCount('menuPermissions')->latest('id');
            $this->applyStatusFilter($query, $filters);

            if (! empty($filters['search'])) {
                $search = '%' . $filters['search'] . '%';
                $query->where(fn ($q) => $q->where('name', 'like', $search)->orWhere('email', 'like', $search)->orWhere('phone', 'like', $search));
            }
            if (! empty($filters['role'])) {
                $query->where('role', $filters['role']);
            }

            return $query->paginate(static::PER_PAGE)->withQueryString();
        });
    }

    public function create(array $data): User
    {
        $data['password'] = Hash::make($data['password']);

        return User::create($this->images->resolve($data, null, 'users'));
    }

    public function update(User $user, array $data): User
    {
        if ($user->id === auth()->id() && (($data['role'] ?? $user->role) !== $user->role || empty($data['status']))) {
            throw ValidationException::withMessages(['role' => 'You cannot change your own role or deactivate your own account.']);
        }

        if ($user->isAdmin() && (($data['role'] ?? $user->role) !== User::ROLE_ADMIN || empty($data['status']))) {
            $this->assertAnotherActiveAdmin($user);
        }

        if (empty($data['password'])) {
            unset($data['password']);
        } else {
            $data['password'] = Hash::make($data['password']);
        }

        $user->update($this->images->resolve($data, $user, 'users'));

        return $user;
    }

    public function delete(User $user): void
    {
        if ($user->id === auth()->id()) {
            throw ValidationException::withMessages(['user' => 'You cannot delete your own account.']);
        }
        if ($user->isAdmin()) {
            $this->assertAnotherActiveAdmin($user);
        }

        $user->delete();
        $this->images->delete($user->image);
    }

    public function updateProfile(User $user, array $data): User
    {
        if (empty($data['password'])) {
            unset($data['password']);
        } else {
            $data['password'] = Hash::make($data['password']);
        }
        unset($data['current_password'], $data['password_confirmation']);

        $user->update($this->images->resolve($data, $user, 'users'));

        return $user;
    }

    private function assertAnotherActiveAdmin(User $user): void
    {
        $others = User::where('role', User::ROLE_ADMIN)->where('status', true)->whereKeyNot($user->id)->exists();

        if (! $others) {
            throw ValidationException::withMessages(['user' => 'The system must keep at least one active admin.']);
        }
    }
}
