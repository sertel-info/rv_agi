<?php

require_once __DIR__."/../Linhas/Linhas.php";
use Illuminate\Database\Eloquent\Model;

class GruposAtendimento extends Model
{

    protected $table = 'grupos_atendimento';

    protected $fillable = ['nome', 'tipo'];

    public $timestamps = false;

    public function linhas(){
    	return $this->belongsToMany(Linhas::class, 'grupos_linhas', 'grupo_id', 'linha_id');
    }

    public function scopeWithIdMd5($query){
        if($query->getQuery()->columns == null){ 
            $query->addSelect('*');
        }
        $query->addSelect(\DB::Raw('MD5(grupos_atendimento.id) as id_md5'));
    }

    public function assinante(){
    	return $this->belongsTo(Assinantes::class, 'assinante_id', 'id');
    }
}
