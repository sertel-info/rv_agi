<?php

use Illuminate\Database\Eloquent\Model;
require_once __DIR__."/../Assinantes/Assinantes.php";

class Planos extends Model
{
    protected $table = "planos";

    protected $fillable = ["nome",
    						"tipo",
							"valor_sms",
							"valor_fixo_local",
							"valor_fixo_ddd",
							"valor_movel_local",
							"valor_movel_ddd",
							"valor_ddi",
							"valor_ip",
							"descricao"
						   ];


	function assinantes(){
		return $this->hasMany(Assinantes::class, "plano", "id");
	} 
}
