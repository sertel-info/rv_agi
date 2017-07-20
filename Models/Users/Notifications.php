<?php

use Illuminate\Database\Eloquent\Model;
require_once __DIR__."/User.php";

class Notifications extends Model
{
    protected $fillable  = ['message',
							'type',
							'title',
							'seen'];

	public function user(){
		return $this->belongsTo(User::class, "user_id", "id");
	}
}
