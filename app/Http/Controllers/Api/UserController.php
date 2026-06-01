<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    // Roles that each creator role is allowed to create
    const CREATABLE_ROLE = [
        'admin'          => ['admin', 'national_admin', 'estatal_admin', 'municipal_admin', 'seccion_admin', 'field_workforce'],
        'national_admin' => ['estatal_admin'],
        'estatal_admin'  => ['municipal_admin'],
        'municipal_admin'=> ['seccion_admin'],
        'seccion_admin'  => ['field_workforce'],
        'field_workforce'=> [],
    ];

    /**
     * List users. Non-admin roles only see users within their national_admin subtree.
     */
    public function index(Request $request)
    {
        $creator = auth()->user();
        $query   = User::select('iduserId', 'username', 'email', 'role', 'cve_ent', 'cve_mun', 'cve_seccion', 'national_admin_id', 'created_at');

        if ($request->has('role')) {
            $query->where('role', $request->input('role'));
        }

        // Non-admin roles only see users tied to the same national_admin
        if ($creator->role !== 'admin') {
            $nationalAdminId = $creator->role === 'national_admin'
                ? $creator->iduserId
                : $creator->national_admin_id;

            if ($nationalAdminId) {
                $query->where('national_admin_id', $nationalAdminId);
            } else {
                $query->whereRaw('0 = 1');
            }
        }

        return response()->json($query->orderBy('created_at', 'desc')->paginate(50));
    }

    /**
     * Create a new user.
     */
    public function store(Request $request)
    {
        $creator       = auth()->user();
        $allowedRoles  = self::CREATABLE_ROLE[$creator->role] ?? [];

        $data = $request->validate([
            'username'        => 'required|string|max:45',
            'email'           => 'required|email|max:100|unique:users,email',
            'password'        => 'required|string|min:6',
            'role'            => ['required', Rule::in($allowedRoles)],
            'cve_ent'         => 'nullable|string|max:6',
            'cve_mun'         => 'nullable|string|max:9',
            'cve_seccion'     => 'nullable|string|max:4',
            'national_admin_id' => 'nullable|integer|exists:users,iduserId',
        ]);

        if (!in_array($data['role'], $allowedRoles)) {
            return response()->json(['error' => 'You are not allowed to create a user with that role'], 403);
        }

        // Determine national_admin_id automatically
        $nationalAdminId = null;
        if ($creator->role === 'national_admin') {
            $nationalAdminId = $creator->iduserId;
        } elseif ($creator->role !== 'admin') {
            $nationalAdminId = $creator->national_admin_id;
        } elseif (isset($data['national_admin_id'])) {
            // admin can explicitly set it
            $nationalAdminId = $data['national_admin_id'];
        }

        $user = User::create([
            'username'         => $data['username'],
            'email'            => $data['email'],
            'password'         => Hash::make($data['password']),
            'role'             => $data['role'],
            'cve_ent'          => $data['cve_ent'] ?? null,
            'cve_mun'          => $data['cve_mun'] ?? null,
            'cve_seccion'      => $data['cve_seccion'] ?? null,
            'national_admin_id'=> $nationalAdminId,
        ]);

        return response()->json(
            $user->only('iduserId', 'username', 'email', 'role', 'cve_ent', 'cve_mun', 'cve_seccion', 'national_admin_id'),
            201
        );
    }

    /**
     * Update an existing user.
     */
    public function update(Request $request, int $id)
    {
        $user = User::findOrFail($id);

        $data = $request->validate([
            'username'    => 'sometimes|string|max:45',
            'email'       => ['sometimes', 'email', 'max:100', Rule::unique('users', 'email')->ignore($user->iduserId, 'iduserId')],
            'password'    => 'sometimes|string|min:6',
            'role'        => ['sometimes', Rule::in(['admin', 'national_admin', 'estatal_admin', 'municipal_admin', 'seccion_admin', 'field_workforce'])],
            'cve_ent'     => 'nullable|string|max:6',
            'cve_mun'     => 'nullable|string|max:9',
            'cve_seccion' => 'nullable|string|max:4',
        ]);

        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $user->update($data);

        return response()->json($user->only('iduserId', 'username', 'email', 'role', 'cve_ent', 'cve_mun', 'cve_seccion', 'national_admin_id'));
    }

    /**
     * Delete a user.
     */
    public function destroy(int $id)
    {
        $user = User::findOrFail($id);

        if ($user->iduserId === auth()->id()) {
            return response()->json(['error' => 'Cannot delete your own account'], 422);
        }

        $user->delete();
        return response()->json(['message' => 'User deleted']);
    }

    /**
     * Return only seccion_admin users (for user_in_charge dropdown).
     */
    public function seccionAdmins()
    {
        $users = User::where('role', 'seccion_admin')
            ->select('iduserId', 'username', 'email', 'cve_ent', 'cve_mun', 'cve_seccion')
            ->orderBy('username')
            ->get();

        return response()->json($users);
    }

    /**
     * Return all national_admin users (for layer assignment selector).
     */
    public function nationalAdmins()
    {
        $users = User::where('role', 'national_admin')
            ->select('iduserId', 'username', 'email')
            ->orderBy('username')
            ->get();

        return response()->json($users);
    }
}
