<?php

use Illuminate\Database\Eloquent\Model;

require_once __DIR__."/../Linhas/Linhas.php";
require_once __DIR__."/../Planos/Planos.php";
require_once __DIR__."/../Audios/Audios.php";
require_once __DIR__."/../Assinantes/Assinantes.php";

class Uras extends Model
{	
	protected $fillable = ['dig_tralha_tipo',
    					   'dig_asteristico_destino',
    					   'tipo_audio'];

    protected $table = 'uras';

    function __construct(){
        for($i = 1; $i <10; $i++){
            array_push($this->fillable, "dig_".$i."_tipo", "dig_".$i."_destino");
        }
    }

    public function audio(){
    	return $this->belongsTo(Audios::class, "audio_id", "id");
    }

    public function assinante(){
    	return $this->belongsTo(Assinantes::class, "assinante_id", "id");
    }

    public function linha(){
        return $this->belongsTo(Linhas::class, "linha_id", "id");
    }

}
