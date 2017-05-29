<?php

use Illuminate\Database\Eloquent\Model;

require_once __DIR__."/../Linhas/Linhas.php";
require_once __DIR__."/DadosContatoAssinante.php";
require_once __DIR__."/DadosFinanceiroAssinante.php";
require_once __DIR__."/DadosFacilidadesAssinante.php";
require_once __DIR__."/../Planos/Planos.php";

class Assinantes extends Model
{
    protected $table = "assinantes";

    protected $fillable = ["plano",
                           "nome_fantasia",
                           "nome",
    					   "sobrenome",
    					   "razao_social",
    					   "cnpj",
    					   "cpf",
    					   "inscricao_estadual",
    					   "rg",
    					   "tipo"];

    /*
    *
    * Relacionamentos
    */
    public function planos(){
        return $this->belongsTo(Planos::class, "plano", "id");
    }

    public function linhas(){
        return $this->hasMany(Linhas::class, 'assinante_id', 'id');
    }

    public function contato(){
        return $this->hasOne(DadosContatoAssinante::class, 'assinante_id', 'id');
    }

    public function financeiro(){
        return $this->hasOne(DadosFinanceiroAssinante::class, 'assinante_id', 'id');
    }

    public function facilidades(){
        return $this->hasOne(DadosFacilidadesAssinante::class, 'assinante_id', 'id');
    }


    /*
    * Mutators
    */
    public function setTipoAttribute($value){
        $this->attributes['tipo'] = (Boolean)$value;
    }

    public function setCreditosAttribute($value){
        $this->attributes['creditos'] = floatval($value);
    }
    
}
