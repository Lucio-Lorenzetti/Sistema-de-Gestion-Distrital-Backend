<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\User;

class CoursePolicy
{
    /**
     * Mismo criterio que NewsController/DownloadController ya usan inline
     * (hasAnyRole(['Director', 'Aux Comunicación'])) — antes CoursesController
     * no tenía ninguna autorización, cualquier autenticado podía crear/editar/
     * borrar cursos.
     */
    public function create(User $user): bool
    {
        return $user->hasAnyRole(['Director', 'Aux Comunicación']);
    }

    public function update(User $user, Course $course): bool
    {
        return $this->create($user);
    }

    public function delete(User $user, Course $course): bool
    {
        return $this->create($user);
    }
}
