<?php

use Illuminate\Database\Eloquent\Model;
require_once __DIR__."/Linhas.php";

class DadosConfiguracoesLinhas extends Model
{
    protected $table = "dados_configuracoes_linhas";

    protected $fillable = [
							"callerid",
							"envio_dtmf",
							"ring_falso",
							"nat",
                            "linha_id"
    					  ];
    
    public function linha(){
        return $this->belongsTo(Linhas::class, 'linha_id');
    }

    public function setRingFalsoAttribute($value){
    	return $this->attributes['ring_falso'] = (Boolean)$value;
    }

    public function setNatAttribute($value){
    	return $this->attributes['nat'] = (Boolean)$value;
    }

}
