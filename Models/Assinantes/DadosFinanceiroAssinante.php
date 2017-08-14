<?php

use Illuminate\Database\Eloquent\Model;
require_once __DIR__."/Assinantes.php";

class DadosFinanceiroAssinante extends Model
{
    protected $table = "dados_financeiro_assinantes";

    protected $fillable = ["assinante_id",
							"dias_bloqueio",
							"dia_vencimento",
							"espaco_disco",
							"limite_credito",
							"alerta_saldo",
							"creditos"
						  ];

	public function assinante(){
		return $this->belongsTo(Assinantes::class, 'assinante_id');
	}

	public function getAlertaSaldoAttribute($value){
		return floatval($value);
	}
    
}
