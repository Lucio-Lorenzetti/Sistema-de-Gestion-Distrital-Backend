<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $fillable = ['nombre'];
    public $timestamps = false; // Como no le pusimos timestamps en la migración, hay que avisarle
}
