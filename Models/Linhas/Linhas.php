<?php

use Illuminate\Database\Eloquent\Model;

require_once __DIR__."/../Assinantes/Assinantes.php";
require_once __DIR__."/DadosAutenticacaoLinhas.php";
require_once __DIR__."/DadosConfiguracoesLinhas.php";
require_once __DIR__."/DadosFacilidadesLinhas.php";
require_once __DIR__."/DadosPermissoesLinhas.php";
require_once __DIR__."/Dids.php";
require_once __DIR__."/../Planos/Planos.php";
require_once __DIR__."/../Uras/Uras.php";
require_once __DIR__."/../GruposAtendimento/GruposAtendimento.php";

class Linhas extends Model
{
    protected $table = "linhas";

    protected $fillable = ["assinante_id",
							"tecnologia",
							"ddd_local",
							"nome",
							"simultaneas",
							"funcionalidade",
							"status_did",
							"codecs",
							"cli"
	];

	public function setStatusDidAttribute($value){
		$this->attributes['status_did'] = (Boolean)$value;
	}

	public function setCodecsAttribute($value){
		$this->attributes['codecs'] = in_array(gettype($value), ['array', 'object']) ? json_encode($value) : "[]";
	}

	public function setCliAttribute($value){
		$this->attributes['cli'] = (Boolean)$value;
	}

	public function assinante(){
		return $this->belongsTo(Assinantes::class, 'assinante_id' , 'id');
	}
 	
 	public function autenticacao(){
 		return $this->hasOne(DadosAutenticacaoLinhas::class, 'linha_id', 'id');
 	} 

 	public function configuracoes(){
 		return $this->hasOne(DadosConfiguracoesLinhas::class, 'linha_id', 'id');
 	} 

 	public function facilidades(){
 		return $this->hasOne(DadosFacilidadesLinhas::class, 'linha_id', 'id');
 	} 

 	public function permissoes(){
 		return $this->hasOne(DadosPermissoesLinhas::class, 'linha_id', 'id');
 	} 

 	public function did(){
 		return $this->hasOne(Dids::class, 'linha_id', 'id');
 	}  	
	
	/*public function saudacoes(){
 		return $this->hasMany(Saudacoes::class, 'linha_id', 'id');
 	}*/  	

 	public function scopeComplete($query)
    {
        return $query->with('permissoes',
        					'autenticacao', 
        					'assinante', 
        					'configuracoes', 
        					'facilidades', 
        					'did', 
        					['ura'=>function($query){
        						$query->with("audio");
        					}]);
    }

    public function grupos()
    {
        return $this->belongsToMany(GruposAtendimento::class, 'grupos_linhas', 'linha_id', 'grupo_id');
    }

    public function plano(){
    	//tenta pegar o plano da linha, se não pega o plano do assinante
    	if($this->plano == null){
    		return $this->assinante->planos;
    	}

    	return $this->hasOne(Planos::class, 'plano', 'id')->first();
    }
}
