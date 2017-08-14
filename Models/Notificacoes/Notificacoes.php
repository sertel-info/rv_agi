<?php

use Illuminate\Database\Eloquent\Model;

class Notificacoes extends Model
{
    protected $table = "notificacoes";
    
    protected $fillable  = ['mensagem',
							//'tipo',
							'titulo',
							'nome',
							'nivel',
							'escutar_evento',
							'ativar_email',
							'email_assunto',
							'intervalo_reenvio',
							'numero_envios',
							'email_corpo'];

	/*public function user(){
		return $this->belongsTo("App\Models\Notifications", "user_id", "id");
	}*/

	public function setAtivarEmailAttribute($value){
		$this->attributes['ativar_email'] = boolval($value);
	}

	public function getAtivarEmailAttribute($value){
		return boolval($value);
	}

	public function scopeWithIdMd5($query){
        if($query->getQuery()->columns == null){ 
            $query->addSelect('*');
        }

        $query->addSelect(\DB::Raw('MD5(notificacoes.id) as id_md5'));
    }

    public function scopeVistas($query, $vistas){
    	$query->where('seen', +$vistas);
    }


    public function scopeWithIconName($query){
		if($query->getQuery()->columns == null){ 
            $query->addSelect('*');
        }

        $icons_case = "(CASE nivel ".
        			  " WHEN 'danger' THEN 'glyphicon-exclamation-sign'".
        			  " WHEN 'warning' THEN 'glyphicon glyphicon-bell'".
        			  " WHEN 'success' THEN 'glyphicon glyphicon-ok-circle'".
        			  " END) as icon_name";

        $query->addSelect(\DB::Raw($icons_case));
    }
}
