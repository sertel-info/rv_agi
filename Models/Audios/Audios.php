<?php

use Illuminate\Database\Eloquent\Model;

require_once __DIR__."/../Uras/Uras.php";

class Audios extends Model
{
    protected $table = 'audios';

    protected $fillable = ["nome_original",
							"nome",
							"caminho",
							"extensao",
							];


	public function ura(){
		return $this->hasOne(Uras::class, 'audio_id', 'id');
	}

}
