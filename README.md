# Sistema de Gestión Distrital - API Backend

Este proyecto forma parte del **Proyecto Final de Carrera** de Ingeniería en Sistemas de Información.  
El objetivo es desarrollar una plataforma web integral para el Distrito 3, Zona 13 perteneciente a la Asociación Scout de Argentina, que permita centralizar la comunicación institucional, la gestión de documentación y el trabajo colaborativo entre los grupos que la integran.
Este es el núcleo de procesamiento del proyecto final de carrera. Provee una API RESTful construida con **Laravel 11**, encargada de la lógica de negocio, autenticación y gestión de permisos jerárquicos.

## Tecnologías
* **Framework:** [Laravel 11](https://laravel.com/)
* **Lenguaje:** PHP 8.2+
* **Autenticación:** Laravel Sanctum (Token-based)
* **Base de Datos:** PostgreSQL (vía Supabase)
* **Hosting:** Render (Producción)

## Características Principales
* Gestión de roles y permisos (Director, Jefe de Grupo, Auxiliares, Educadores).
* Sistema de carga y versionado de programas.
* API de inscripciones a cursos.
* Módulo de noticias y descargas.
