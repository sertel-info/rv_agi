<?php


use Illuminate\Database\Eloquent\Model;
require_once __DIR__."/Linhas.php";

class DadosFacilidadesLinhas extends Model
{
    protected $table = 'dados_facilidades_linhas';

    protected $fillable = [
							"gravacao",
							"cadeado_pessoal",
							"siga_me",
							"caixa_postal",
                            "cadeado_pin",
                            "num_siga_me",
                            "cx_postal_pw",
                            "cx_postal_email"
							];

    public function linha(){
        return $this->belongsTo(Linhas::class, 'linha_id');
    }

    public function setGravacaoAttribute($value){
    	return $this->attributes['gravacao'] = (Boolean)$value;
    }

    public function setCadeadoPessoalAttribute($value){
    	return $this->attributes['cadeado_pessoal'] = (Boolean)$value;
    }

    public function setSigaMeAttribute($value){
    	return $this->attributes['siga_me'] = (Boolean)$value;
    }

    public function setCaixaPostalAttribute($value){
    	return $this->attributes['caixa_postal'] = (Boolean)$value;
    }

    public function setPinAttribute($value){
    	return $this->attributes['cadeado_pin'] = (Boolean)$value;
    }

}
