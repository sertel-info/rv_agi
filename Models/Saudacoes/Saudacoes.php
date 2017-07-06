<?php

use Illuminate\Database\Eloquent\Model;

require_once __DIR__."/../Linhas/Linhas.php";
require_once __DIR__."/../Audios/Audios.php";
require_once __DIR__."/../Assinantes/Assinantes.php";

class Saudacoes extends Model
{
    protected $table = "saudacoes";
    
    protected $fillable = ["nome",
                            "tipo_audio",
                            "audio_id",
                            "ativo",
                            "assinante_id"];

    public function audio(){
        return $this->belongsTo(Audios::class, 'audio_id', 'id');
    }

    public function assinante(){
        return $this->belongsTo(Assinantes::class, 'assinante_id', 'id');
    }

    public function scopeAtivas(){
        return $this->where("ativo", 1);
    }
}
