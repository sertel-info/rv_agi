<?php

use Illuminate\Database\Eloquent\Model;
require_once __DIR__."/Assinantes.php";

class DadosAcessoAssinante extends Model
{
    protected $table = "dados_acesso_assinantes";

    protected $fillable = ["assinante_id",
    					   "nome",
    					   "senha",
    					   "email",
    					  ];

    public function assinante(){
		return $this->belongsTo(Assinantes::class, 'assinante_id');
	}
}
