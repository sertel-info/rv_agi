<?php


use Illuminate\Database\Eloquent\Model;
require_once __DIR__."/Linhas.php";

class DadosPermissoesLinhas extends Model
{
    protected $table = "dados_permissoes_linhas";

    protected $fillable = [
    						"ligacao_fixo",
							"ligacao_internacional",
							"ligacao_movel",
							"ligacao_ip",
							"status",
							"linha_id"
						  ];

    public function linha(){
        return $this->belongsTo(Linhas::class, 'linha_id', 'id');
    }
    
	public function setLigacaoFixoAttribute($value){
		return $this->attributes['ligacao_fixo'] = (Boolean)$value;
	}

	public function setLigacaoInternacionalAttribute($value){
		return $this->attributes['ligacao_internacional'] = (Boolean)$value;
	}

	public function setLigacaoMovelAttribute($value){
		return $this->attributes['ligacao_movel'] = (Boolean)$value;
	}

	public function setLigacaoIpAttribute($value){
		return $this->attributes['ligacao_ip'] = (Boolean)$value;
	}

	public function setStatusAttribute($value){
		return $this->attributes['status'] = (Boolean)$value;
	}
	



}
