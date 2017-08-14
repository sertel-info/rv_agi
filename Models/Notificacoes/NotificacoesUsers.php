<?php

use Illuminate\Database\Eloquent\Model;

class NotificacoesUsers extends Model
{
	protected $table = "notificacoes_users";
	protected $fillable = ['user_id'];

	/*public function notificacao(){
		return $this->belongsTo('App\Models\Notificacoes\Notificacoes', 'notificacao_id', 'id');
	}

	public function user(){
		return $this->belongsTo('App\User', 'user_id', 'id');
	}*/

	public function scopeVista($query, $value){
		$query->where("vista", +($value));
	}

	public function scopeWithIdMd5($query){
        if($query->getQuery()->columns == null){ 
            $query->addSelect('*');
        }

        $query->addSelect(\DB::Raw('MD5(notificacoes_users.id) as id_md5'));
    }

    public function scopeWithFormatedDate($query){
    	if($query->getQuery()->columns == null){ 
            $query->addSelect('*');
        }

    	$query->addSelect(\DB::raw("DATE_FORMAT(created_at, '%d/%m/%Y %H:%i:%s') as formated_created_at"),DB::raw("DATE_FORMAT(updated_at, '%d/%m/%Y %H:%i:%s') as formated_updated_at")
    		);
    }
}
