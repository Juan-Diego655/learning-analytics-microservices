<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * 3 usuarios de prueba con roles distintos. Misma password para todos: "password".
     * En producción esto JAMÁS estaría en un seeder.
     */
    public function run(): void
    {
        $defaultInstitution = (string) Str::uuid();

        User::updateOrCreate(
            ['email' => 'admin@learning.test'],
            [
                'name' => 'Admin Ministerio',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'institution_id' => null,
            ]
        );

        User::updateOrCreate(
            ['email' => 'docente@learning.test'],
            [
                'name' => 'Profesor Demo',
                'password' => Hash::make('password'),
                'role' => 'docente',
                'institution_id' => $defaultInstitution,
            ]
        );

        User::updateOrCreate(
            ['email' => 'estudiante@learning.test'],
            [
                'name' => 'Estudiante Demo',
                'password' => Hash::make('password'),
                'role' => 'estudiante',
                'institution_id' => $defaultInstitution,
            ]
        );
    }
}
