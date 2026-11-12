<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * De qué rama y grupo es un programa que se está creando.
 *
 * - Un usuario común (Educador): sale SIEMPRE de su propia asignación. Lo que
 *   venga en el payload se ignora: nadie puede crear programas en otra rama.
 * - Developer: no tiene rama propia, así que elige rama y grupo al crear. Si
 *   además es Educador de verdad y no manda nada, se usa su asignación.
 *
 * Si falta la rama (o, para el Developer, el grupo) se corta con un mensaje claro
 * en vez de dejar que la base rechace el insert (rama_id es NOT NULL).
 */
class UbicacionPrograma
{
    /**
     * @param  array  $validado  Datos ya validados del request (puede traer rama_id / grupo_id).
     * @return array{rama_id: int, grupo_id: int|null}
     *
     * @throws ValidationException
     */
    public static function resolver(User $user, array $validado): array
    {
        $esDeveloper = $user->isDeveloper();

        $ramaId  = $esDeveloper ? ($validado['rama_id'] ?? $user->rama_id) : $user->rama_id;
        $grupoId = $esDeveloper ? ($validado['grupo_id'] ?? $user->grupo_id) : $user->grupo_id;

        if (! $ramaId) {
            throw ValidationException::withMessages([
                'rama_id' => [$esDeveloper
                    ? 'Elegí la rama a la que pertenece el programa.'
                    : 'No podés crear programas porque tu usuario no tiene una rama asignada. Pedile al Director que te asigne el rol de Educador con tu rama.'],
            ]);
        }

        if ($esDeveloper && ! $grupoId) {
            throw ValidationException::withMessages([
                'grupo_id' => ['Elegí el grupo al que pertenece el programa.'],
            ]);
        }

        return ['rama_id' => (int) $ramaId, 'grupo_id' => $grupoId ? (int) $grupoId : null];
    }
}
