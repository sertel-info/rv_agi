<?php

require_once __DIR__."/User.php";
require_once __DIR__."/../Assinantes/Assinantes.php";
use Illuminate\Database\Eloquent\Model;

class User extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password', 'assinante_id', 'role'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token'
    ];

    public function assinante(){
        return $this->belongsTo(Assinantes::class, 'assinante_id', 'id');
    }

    public function notifications(){
        return $this->hasMany(Notifications::class, 'user_id', 'id');
    }

}

