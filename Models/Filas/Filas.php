<?php
require_once __DIR__."/../Linhas/Linhas.php";
require_once __DIR__."/../Assinantes/Assinantes.php";

use Illuminate\Database\Eloquent\Model;

class Filas extends Model
{
    protected $table = 'filas';
    
    protected $fillable = ["nome",
							"tipo",
							"tempo_chamada",
							"regra_transbordo"];


	public function linhas(){
		return $this->belongsToMany(Linhas::class, 'linhas_filas', 'fila_id', 'linha_id');
	}

	public function assinante(){
		return $this->belongsTo(Assinantes::class, "assinante_id", "id");
	}

	public function scopeWithIdMd5($query){
        if($query->getQuery()->columns == null){ 
            $query->addSelect('*');
        }

        $query->addSelect(\DB::Raw('MD5(filas.id) as id_md5'));
    }
}
