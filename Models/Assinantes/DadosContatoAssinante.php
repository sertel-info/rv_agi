<?php

use Illuminate\Database\Eloquent\Model;
require_once __DIR__."/Assinantes.php";

class DadosContatoAssinante extends Model
{
    protected $table = "dados_contato_assinantes";

    protected $fillable = [
	    					"assinante_id",
							"cep",
							"endereco",
							"complemento",
							"bairro",
							"cidade",
							"estado",
							"pais",
							"email",
							"site",
							"telefone",
							"fax",
							"celular"
	    					];

	public function assinante(){
		return $this->belongsTo(Assinantes::class, 'assinante_id');
	}
}
