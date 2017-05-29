<?php

use Illuminate\Database\Eloquent\Model;
require_once __DIR__."/Linhas.php";

class Dids extends Model
{
    protected $table = "dids";

    protected $fillable = ["usuario_did",
							"senha_did",
							"ip_did",
							"extensao_did",
							"linha_id"];


	public function linha(){
		return $this->belongsTo(Linhas::class, 'linha_id');
	}
}
