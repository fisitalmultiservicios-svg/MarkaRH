<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class CreateMaster extends Command
{
    protected $signature = 'markarh:create-master';

    protected $description = 'Crear el primer Maestro mediante entrada interactiva; sin contraseñas en argumentos.';

    public function handle(): int
    {
        if (User::where('role', 'master')->exists()) {
            $this->error('Ya existe un Maestro. Gestiona el acceso mediante un procedimiento autorizado.');

            return self::FAILURE;
        }
        $data = ['name' => $this->ask('Nombre'), 'email' => mb_strtolower($this->ask('Correo electrónico') ?? ''), 'password' => $this->secret('Contraseña (mínimo 12 caracteres, mayúsculas, minúsculas y números)'), 'password_confirmation' => $this->secret('Repite la contraseña')];
        $v = Validator::make($data, ['name' => 'required|string|max:120', 'email' => 'required|email|unique:users,email', 'password' => ['required', 'confirmed', Password::min(12)->mixedCase()->numbers()]]);
        if ($v->fails()) {
            foreach ($v->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }
        User::create(['name' => $data['name'], 'email' => $data['email'], 'password' => $data['password'], 'role' => 'master', 'tenant_id' => null]);
        $this->info('Maestro creado. En el primer acceso deberás configurar tu autenticador.');

        return self::SUCCESS;
    }
}
