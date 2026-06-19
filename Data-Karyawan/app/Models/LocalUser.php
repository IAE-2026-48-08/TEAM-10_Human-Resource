<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LocalUser extends Model
{
    protected $fillable = ['email', 'name', 'role', 'sso_sub'];
}