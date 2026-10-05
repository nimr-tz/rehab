<?php

namespace App\Actions\Fortify;

use App\Enums\Role;
use App\Models\User;
use App\Support\Countries;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Spatie\Permission\Models\Role as RoleModel;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    public const TITLES = ['Dr', 'Prof', 'Mr', 'Mrs', 'Ms'];

    /**
     * Validate and create a newly registered participant.
     *
     * @param  array<string, string>  $input
     *
     * @throws ValidationException
     */
    public function create(array $input): User
    {
        $input['email'] = Str::lower(trim($input['email'] ?? ''));

        Validator::make($input, [
            'title' => ['nullable', Rule::in(self::TITLES)],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class)],
            'phone' => ['required', 'string', 'max:32', 'regex:/^\+?[0-9 ()-]{7,20}$/'],
            'country' => ['required', Rule::in(Countries::codes())],
            'password' => $this->passwordRules(),
            'terms' => ['accepted'],
        ], [
            'phone.regex' => 'Enter a phone number with digits only, for example +255 712 345 678.',
            'terms.accepted' => 'Please confirm that your details are correct.',
        ])->validate();

        return DB::transaction(function () use ($input) {
            $user = User::create([
                'title' => $input['title'] ?? null,
                'first_name' => trim($input['first_name']),
                'last_name' => trim($input['last_name']),
                'email' => $input['email'],
                'phone' => trim($input['phone']),
                'country' => $input['country'],
                'password' => $input['password'],
            ]);

            // findOrCreate: registration must not fail on a database that was never seeded.
            $user->assignRole(RoleModel::findOrCreate(Role::Participant->value, 'web'));

            return $user;
        });
    }
}
