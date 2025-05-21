<?php

namespace App\Imports;

use App\Models\User;
use App\Models\Branch;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\WithValidation;

class UsersImport implements ToModel, WithHeadingRow, SkipsEmptyRows, WithValidation
{
    /**
     * Mapping baris Excel ke model User
     */
    public function model(array $row)
    {
        // Cari branch berdasarkan nama (case-insensitive)
        $branchName = trim($row['branch'] ?? '');
        $branch = Branch::whereRaw('LOWER(name) = ?', [strtolower($branchName)])->first();
        $branchId = $branch ? $branch->id : null;

        // Validasi role dan status
        $role = strtolower(trim($row['role'] ?? ''));
        $allowedRoles = ['admin', 'kepala_cabang', 'supervisor', 'salesman'];
        if (!in_array($role, $allowedRoles, true)) {
            $role = 'salesman'; // default role jika tidak valid
        }

        $status = strtolower(trim($row['status'] ?? ''));
        $allowedStatus = ['aktif', 'nonaktif'];
        if (!in_array($status, $allowedStatus, true)) {
            $status = 'aktif'; // default status
        }

        // Password harus di-hash sebelum disimpan
        $password = $row['password'] ?? null;
        if (!$password) {
            // jika password kosong, beri default password 'password'
            $password = Hash::make('password');
        } else {
            $password = Hash::make($password);
        }

        return new User([
            'branch_id'       => $branchId,
            'name'            => trim($row['name'] ?? ''),
            'username'        => trim($row['username'] ?? ''),
            'email'           => trim($row['email'] ?? null),
            'password'        => $password,
            'role'            => $role,
            'status'          => $status,
            'remember_token'  => null,
        ]);
    }

    /**
     * Validasi untuk tiap baris import
     */
    public function rules(): array
    {
        return [
            '*.username' => ['required', 'string', 'max:255', 'unique:user,username'],
            '*.email' => ['nullable', 'email', 'max:255', 'unique:user,email'],
            '*.name' => ['required', 'string', 'max:255'],
            '*.role' => ['required', Rule::in(['admin', 'kepala_cabang', 'supervisor', 'salesman'])],
            '*.status' => ['nullable', Rule::in(['aktif', 'nonaktif'])],
            '*.password' => ['nullable', 'string', 'min:6'],
            '*.branch' => [
                'nullable',
                'string',
                function($attribute, $value, $fail) {
                    if ($value && !\App\Models\Branch::whereRaw('LOWER(name) = ?', [strtolower($value)])->exists()) {
                        $fail("Branch dengan nama '{$value}' tidak ditemukan.");
                    }
                }
            ],
        ];
    }

    /**
     * Baris header Excel (default 1)
     */
    public function headingRow(): int
    {
        return 1;
    }
}
