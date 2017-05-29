<?php

use Illuminate\Database\Eloquent\Model;

class Configuracoes extends Model
{
    protected $table = "configuracoes";

    protected $fillable = ["prefx_aplicacoes",
							"atalho_siga_me",
							"atalho_cadeado",
							"voice_mail_assunto_padrao",
							"voice_mail_remetente_padrao",
							"voice_mail_mensagem_padrao"];

}
