<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\SetUserPasswordRequest;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use App\Notifications\MobileResetPassword;
use App\Services\Auth\EmployeeEmailVerificationService;
use App\Support\Admin\ActivityLogger;
use App\Support\Admin\UserFormOptions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function __construct(private EmployeeEmailVerificationService $emailVerification) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $users = User::query()
            ->with('department:id,name')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get()
            ->map(fn (User $user) => $this->userPayload($user, $request));

        return Inertia::render('users/Index', [
            'users' => $users,
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', User::class);

        return Inertia::render('users/Create', UserFormOptions::for($request->user()));
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if ($data['role'] !== UserRole::Employee->value) {
            $data['employee_number'] = null;
            $data['department_id'] = null;
        }

        User::query()->create($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('User created.')]);

        return to_route('users.index');
    }

    public function edit(Request $request, User $user): Response
    {
        $this->authorize('update', $user);

        $user->load('department:id,name');

        return Inertia::render('users/Edit', [
            'managedUser' => $this->userPayload($user, $request),
            ...UserFormOptions::for($request->user()),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        if ($data['role'] !== UserRole::Employee->value) {
            $data['employee_number'] = null;
            $data['department_id'] = null;
        }

        $user->update($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('User updated.')]);

        return to_route('users.index');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

        if ($user->createdEvents()->exists()) {
            return back()->withErrors([
                'user' => __('This user has created events and cannot be deleted.'),
            ]);
        }

        if ($user->startedEventSessions()->exists()) {
            return back()->withErrors([
                'user' => __('This user has started event sessions and cannot be deleted.'),
            ]);
        }

        if ($user->attendances()->exists()) {
            return back()->withErrors([
                'user' => __('This user has attendance records and cannot be deleted.'),
            ]);
        }

        $user->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('User deleted.')]);

        return to_route('users.index');
    }

    public function revokeTokens(Request $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        if (! $user->isEmployee()) {
            return back()->withErrors([
                'user' => __('Only employee mobile sessions can be revoked.'),
            ]);
        }

        $user->tokens()->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Mobile sessions revoked for :name.', ['name' => $user->name]),
        ]);

        return back();
    }

    public function sendEmailVerification(Request $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        if (! $user->isEmployee() || ! $user->is_active) {
            return back()->withErrors([
                'user' => __('Verification codes can only be sent to active employees.'),
            ]);
        }

        if ($user->hasVerifiedEmail()) {
            return back()->withErrors([
                'user' => __('This employee has already confirmed their email.'),
            ]);
        }

        $this->emailVerification->sendCode($user);

        ActivityLogger::log($request, 'employee_email_verification_sent', $user);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Verification code sent to :email.', ['email' => $user->email]),
        ]);

        return back();
    }

    public function sendPasswordReset(Request $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        if (! $user->isEmployee() || ! $user->is_active) {
            return back()->withErrors([
                'user' => __('Password reset can only be sent to active employees.'),
            ]);
        }

        Password::sendResetLink(
            ['email' => $user->email],
            function (User $employee, string $token): void {
                $employee->notify(new MobileResetPassword($token));
            },
        );

        ActivityLogger::log($request, 'employee_password_reset_sent', $user);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Password reset email sent to :email.', ['email' => $user->email]),
        ]);

        return back();
    }

    public function setPassword(SetUserPasswordRequest $request, User $user): RedirectResponse
    {
        $user->update([
            'password' => Hash::make($request->validated('password')),
        ]);

        $user->tokens()->delete();

        ActivityLogger::log($request, 'employee_password_set', $user);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Temporary password set. Mobile sessions were revoked.'),
        ]);

        return back();
    }

    /**
     * @return array<string, mixed>
     */
    private function userPayload(User $user, Request $request): array
    {
        return [
            'id' => $user->id,
            'first_name' => $user->first_name,
            'middle_name' => $user->middle_name,
            'last_name' => $user->last_name,
            'suffix' => $user->suffix,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role->value,
            'role_label' => $user->role->label(),
            'employee_number' => $user->employee_number,
            'department_id' => $user->department_id,
            'department_name' => $user->department?->name,
            'is_active' => $user->is_active,
            'email_verified_at' => $user->email_verified_at?->toIso8601String(),
            'can' => [
                'update' => $request->user()?->can('update', $user) ?? false,
                'delete' => $request->user()?->can('delete', $user) ?? false,
                'revokeTokens' => $user->isEmployee() && ($request->user()?->can('update', $user) ?? false),
                'managePassword' => $user->isEmployee() && ($request->user()?->can('update', $user) ?? false),
            ],
        ];
    }
}
