<?php


use Illuminate\Database\Eloquent\Model;
require_once __DIR__."/Linhas.php";

class DadosAutenticacaoLinhas extends Model
{
	protected $table = "dados_autenticacao_linhas";
	
    protected $fillable = ['login_ata',
    					   'usuario',
    					   'senha',
    					   'ip',
    					   'porta',
                           'linha_id'
    					   ];
    
    public function linha(){
        return $this->belongsTo(Linhas::class, 'linha_id');
    }
}
