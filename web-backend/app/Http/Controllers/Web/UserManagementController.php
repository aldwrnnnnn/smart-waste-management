<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserManagementController extends Controller
{
    private const SAMPLE_FILES = [
        'reports/sample_organic.jpg',
        'reports/sample_plastic.jpg',
        'reports/sample_paper.jpg',
    ];

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $role = (string) $request->query('role', '');

        $query = User::withCount('wasteReports');

        if ($search !== '') {
            $query->where(function ($sub) use ($search) {
                $sub->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($role !== '') {
            $query->where('role', $role);
        }

        $users = $query->latest()->paginate(10)->withQueryString();

        $totalUsers = User::count();
        $adminCount = User::where('role', 'admin')->count();
        $reporterCount = User::where('role', 'user')->count();

        return view('users.index', [
            'users' => $users,
            'filters' => [
                'search' => $search,
                'role' => $role,
            ],
            'summary' => [
                'total' => $totalUsers,
                'admins' => $adminCount,
                'reporters' => $reporterCount,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', Rule::in(['admin', 'user'])],
            'password' => ['required', 'string', 'min:6'],
        ], [
            'name.required' => 'Nama pengguna wajib diisi.',
            'email.required' => 'Alamat email wajib diisi.',
            'email.unique' => 'Alamat email sudah digunakan oleh akun lain.',
            'role.required' => 'Peran akses (role) wajib dipilih.',
            'password.required' => 'Kata sandi wajib diisi.',
            'password.min' => 'Kata sandi minimal 6 karakter.',
        ]);

        $newUser = new User([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
        ]);
        $newUser->role = $validated['role'];
        $newUser->save();

        return redirect()->route('admin.users.index')
            ->with('success', 'Pengguna "' . $newUser->name . '" (' . strtoupper($newUser->role) . ') berhasil ditambahkan.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', Rule::in(['admin', 'user'])],
            'password' => ['nullable', 'string', 'min:6'],
        ]);

        if ((int) $request->user()->id === (int) $user->id && $validated['role'] !== 'admin') {
            return back()->with('error', 'Anda tidak dapat menurunkan role akun Administrator yang sedang Anda gunakan sendiri.');
        }

        $roleChanged = $user->role !== $validated['role'];
        $passwordChanged = ! empty($validated['password']);

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->role = $validated['role'];

        if ($passwordChanged) {
            $user->password = $validated['password'];
        }

        $user->save();

        if ($roleChanged || $passwordChanged) {
            $user->tokens()->delete();
        }

        return redirect()->route('admin.users.index')
            ->with('success', 'Data pengguna "' . $user->name . '" berhasil diperbarui.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ((int) $request->user()->id === (int) $user->id) {
            return back()->with('error', 'Anda tidak dapat menghapus akun yang sedang digunakan saat ini.');
        }

        $photoPaths = $user->wasteReports()->pluck('photo_path');
        foreach ($photoPaths as $path) {
            if ($path && ! in_array($path, self::SAMPLE_FILES, true) && Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        }

        $user->tokens()->delete();

        $userName = $user->name;
        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'Pengguna "' . $userName . '" beserta riwayat laporannya berhasil dihapus.');
    }
}
