<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Users\CreateUser;
use App\Actions\Users\DeleteUser;
use App\Actions\Users\RestoreUser;
use App\Actions\Users\ToggleUser;
use App\Actions\Users\UpdateUser;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Store a newly created user.
     */
    public function store(Request $request, CreateUser $createUser): RedirectResponse
    {
        $this->authorize('create', User::class);

        $createUser->create($request->only(['name', 'email', 'password', 'password_confirmation']));

        return redirect()->route('admin.users.index')->with('status', __('User created.'));
    }

    /**
     * Update the given user.
     */
    public function update(Request $request, User $user, UpdateUser $updateUser): RedirectResponse
    {
        $this->authorize('update', $user);

        $updateUser->update($user, $request->only(['name', 'email']));

        return redirect()->route('admin.users.edit', $user)->with('status', __('User updated.'));
    }

    /**
     * Toggle the given user's active status.
     */
    public function toggle(User $user, ToggleUser $toggleUser): RedirectResponse
    {
        $this->authorize('disable', $user);

        $toggleUser->toggle($user);

        return redirect()->route('admin.users.index')->with('status', __('User status updated.'));
    }

    /**
     * Soft delete the given user.
     */
    public function destroy(User $user, DeleteUser $deleteUser): RedirectResponse
    {
        $this->authorize('delete', $user);

        $deleteUser->delete($user);

        return redirect()->route('admin.users.index')->with('status', __('User deleted.'));
    }

    /**
     * Restore the given user.
     */
    public function restore(User $user, RestoreUser $restoreUser): RedirectResponse
    {
        $this->authorize('restore', $user);

        $restoreUser->restore($user);

        return redirect()->route('admin.users.trash')->with('status', __('User restored.'));
    }
}
