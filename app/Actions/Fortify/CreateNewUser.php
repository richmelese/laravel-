<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array  $input
     * @return \App\Models\User
     */
    public function create(array $input)
    {
        Validator::make($input, [
            'first_name' => ['required_without:name', 'nullable', 'string', 'min:2', 'max:255', 'regex:/^[\p{L}\s\-\'\.]+$/u'],
            'last_name' => ['nullable', 'string', 'min:2', 'max:255', 'regex:/^[\p{L}\s\-\'\.]+$/u'],
            'name' => ['required_without:first_name', 'nullable', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class),
            ],
            'phone' => [
                'required',
                'string',
                'regex:/^\+?[1-9]\d{6,14}$/',
                Rule::unique(User::class, 'phone'),
            ],
            'password' => $this->passwordRules(),
        ])->validate();

        $user = User::create([
            'first_name' => isset($input['first_name']) ? trim($input['first_name']) : null,
            'last_name' => isset($input['last_name']) ? trim($input['last_name']) : null,
            'name' => isset($input['name']) ? trim($input['name']) : null,
            'email' => strtolower(trim($input['email'])),
            'phone' => trim($input['phone']),
            'password' => Hash::make($input['password']),
            'status' => 'publish',
        ]);
        $user->assignRole('customer');

        return $user;
    }
}
