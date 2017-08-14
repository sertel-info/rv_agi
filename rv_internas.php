#!/usr/bin/php -q
<?php
require_once __DIR__."/vendor/autoload.php";

require_once __DIR__."/Classes/Connections.php";
require_once __DIR__."/Classes/Ligacao.php";
require_once __DIR__."/Classes/Dial.php";
require_once __DIR__."/Classes/Agi.php";
require_once __DIR__."/Classes/Numero.php";
require_once __DIR__."/Classes/Saudacao.php";
require_once __DIR__."/Classes/Log/Logger.php";
require_once __DIR__."/Classes/AtendAutomaticoManager.php";
require_once __DIR__."/Classes/VerificadorPermissoes.php";

require_once __DIR__."/Models/Linhas/DadosAutenticacaoLinhas.php";
require_once __DIR__."/Models/Linhas/DadosConfiguracoesLinhas.php";
require_once __DIR__."/Models/Linhas/Linhas.php";
require_once __DIR__."/Models/Logs/Gravacoes.php";

//remover
require_once __DIR__."/Models/Notificacoes/Notificacoes.php";
require_once __DIR__."/Models/Notificacoes/NotificacoesUsers.php";
require_once __DIR__."/Classes/Notificacoes/AGINotificationMessageCompiler.php";

date_default_timezone_set('America/Sao_Paulo');

$verbose = 1;

$agi = AGI::getSingleton();

$agi->set_variable("CDR(type)", "internas");

$ligacao = new Ligacao();
//$ligacao->setAgi($agi);

$callerid = new Numero($agi->get_variable("CALLERID(num)")['data']);
$exten = new Numero($agi->get_variable("EXTEN")['data']);

$agi->set_variable("CDR(dst_type)", $exten->getTipo());
//$agi->set_variable("CDR(dst)", $exten->getNumero());

$ligacao->setCallerId($callerid);
$ligacao->setExten($exten);
$ligacao->setTipo("interna");

Logger::write(__FILE__,__LINE__, "Exten: ".$ligacao->getExten(), $verbose);
Logger::write(__FILE__,__LINE__, "Callerid: ".$ligacao->getCallerId(), $verbose);


$autenticacao_ligador = DadosConfiguracoesLinhas::where('callerid', $callerid->getNumeroCompleto())->first();

if(!$autenticacao_ligador){

	$autenticacao_ligador = DadosAutenticacaoLinhas::where('login_ata', $callerid->getNumero())->first();

	if(!$autenticacao_ligador){
		Logger::write(__FILE__,__LINE__, "FALHA AO ENCONTRAR EXTENSÃO: ".$ligacao->getCallerId(), $verbose);
		die(1);
	}
	
}

$ligador = Linhas::complete()->find($autenticacao_ligador->linha_id);
$agi->set_variable("CDR(src_id)", $ligador->id);

$autenticacao_receptor = DadosAutenticacaoLinhas::where('login_ata', $ligacao->getExtenObj()->getNumero())->first();

if(!$autenticacao_receptor){
	Logger::write(__FILE__,__LINE__, "FALHA AO ENCONTRAR O RECEPTOR: ".$ligacao->getExtenObj()->getNumero(), $verbose);
	die(1);
}

/** VERIFICA PERMISSÕES **/
	$verif_permissoes = new VerificadorPermissoes($agi);

	$hasPermissao = $verif_permissoes->verificar($ligador->permissoes, $exten);
	if(!$hasPermissao){
		foreach ($verif_permissoes->getErros() as $erro){
			Logger::write(__FILE__,__LINE__, $erro, $verbose);
		}

		$agi->exec("PlayBack", "noRota");
		exit;
	}

/************************/

$receptor = Linhas::complete()->find($autenticacao_receptor->linha_id);
$agi->set_variable("CDR(dst_id)", $receptor->id);


/** ATENDIMENTO AUTOMÁTICO **/

if($receptor->facilidades->atend_automatico == 1){
	Logger::write(__FILE__,__LINE__, "ATENDIMENTO AUTOMÁTICO ATIVO : ".$receptor->facilidades->atend_automatico_tipo);
	$atend_auto = new AtendAutomaticoManager($receptor, $agi);
	$atend_auto->exec();
	exit;
}

/***************************/

/** SAUDAÇÕES **/

if( $receptor->assinante->facilidades->saudacoes == 1 && 
   ($destino = $receptor->facilidades->saudacoes_destino) !== null){
	
	Logger::write(__FILE__,__LINE__, "SAUDAÇÃO ATIVA ".$destino);

	$saudacoes =  $receptor->assinante->saudacoes()->whereRaw("MD5(id) = '".$destino."'")->get();

	foreach($saudacoes as $saudacao_model){
		$saudacao = new Saudacao($saudacao_model, $saudacao_model->audio, $agi);
		$saudacao->exec();
	}
}

/****************************/

$ligacao->setLinha($receptor);
$ligacao->verificaSigaMe();

// REMOVEEEEEEEEEEEEEEEEEEE
Logger::write(__FILE__,__LINE__, "Começando notificações ");
$msg_compiler = new AGINotificationMessageCompiler();
	$notifications = Notificacoes::where('escutar_evento', 'CreditosAcabando')->get();
	$assinante = $ligador->assinante;
	foreach($notifications as $notification){
		$notification_user = new NotificacoesUsers();
   		$compiled_msg = $msg_compiler->compile($notification->mensagem, $assinante);
   		Logger::write(__FILE__,__LINE__, "Compiled msg ".$compiled_msg);

   		$notification_user->vista = false;
        $notification_user->notificacao_id = $notification->id;
        $notification_user->user_id = $assinante->user->id;
        $notification_user->mensagem_compilada = $compiled_msg;
        $notification_user->save();
	}
//EEEEEEEEEEEEEEEEEEEEEEER

$dial = new Dial($ligacao);


$dial->exec();

if(in_array($dial->getLastStatus(), ['NOANSWER', 'BUSY', 'CONGESTION'])
	&& $receptor->facilidades->caixa_postal == 1){
	Logger::write(__FILE__,__LINE__, "Voicemail : ATIVADO");
	Logger::write(__FILE__,__LINE__, "Executando VoiceMail...");

	$manager = new VoiceMailManager();
	$vm = $manager->createVoiceMail($exten);
	$manager->execVoiceMail($vm);
}

