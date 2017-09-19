#!/usr/bin/php -q
<?php
require_once __DIR__."/vendor/autoload.php";

require_once __DIR__."/Classes/Connections.php";
require_once __DIR__."/Classes/Dial.php";
require_once __DIR__."/Classes/Agi.php";
require_once __DIR__."/Classes/Ligacao.php";
require_once __DIR__."/Classes/GruposManager.php";
require_once __DIR__."/Classes/Numero.php";
require_once __DIR__."/Classes/UraManager.php";
require_once __DIR__."/Classes/Saudacao.php";
require_once __DIR__."/Classes/AtendAutomaticoManager.php";
require_once __DIR__."/Classes/Log/Logger.php";

require_once __DIR__."/Models/Linhas/Dids.php";

require_once __DIR__."/Models/Notificacoes/Notificacoes.php";
require_once __DIR__."/Models/Notificacoes/NotificacoesUsers.php";
require_once __DIR__."/Classes/Notificacoes/AGINotificationMessageCompiler.php";

date_default_timezone_set('America/Sao_Paulo');
$verbose = true;

$agi = AGI::getSingleton();

$agi->set_variable("CDR(type)", "entrante");
$ligacao = new Ligacao();
$ligacao->setTipo('entrante');

$callerid = new Numero($agi->get_variable("CALLERID(num)")['data']);
$exten = new Numero($agi->get_variable("EXTEN")['data']);

Logger::write(__FILE__, __LINE__, "Callerid ".$callerid->getNumero());
Logger::write(__FILE__, __LINE__, "Exten ".$exten->getNumero());

$agi->set_variable("CDR(dst_type)", $exten->getTipo());

$ligacao->setCallerId($callerid);
$ligacao->setExten($exten);

$did_linha = Dids::where('extensao_did', $exten->getNumeroCompleto())->first();

if(!$did_linha){
	Logger::write(__FILE__,__LINE__, "FALHA AO ENCONTRAR EXTENSÃO: ".$exten->getNumeroCompleto(), $verbose);
	die(1);
} 

$linha = Linhas::complete()->find($did_linha->linha_id);
$agi->set_variable("CDR(dst_account)", $linha->autenticacao->login_ata);

/** SAUDAÇÕES **/

if( $linha->assinante->facilidades->saudacoes == 1 && 
   ($destino = $linha->facilidades->saudacoes_destino) !== null){
	
	Logger::write(__FILE__,__LINE__, "SAUDAÇÃO ATIVA ".$destino);

	$saudacoes =  $linha->assinante->saudacoes()->whereRaw("MD5(id) = '".$destino."'")->get();

	foreach($saudacoes as $saudacao_model){
		$saudacao = new Saudacao($saudacao_model, $saudacao_model->audio, $agi);
		$saudacao->exec();
	}
}

/******************/

/** ATENDIMENTO AUTOMÁTICO **/

if($linha->facilidades->atend_automatico == 1){
	Logger::write(__FILE__,__LINE__, "ATENDIMENTO AUTOMÁTICO ATIVO : ".$linha->facilidades->atend_automatico_tipo);
	$atend_auto = new AtendAutomaticoManager($linha, $agi);
	$atend_auto->exec();
	exit;
}

/***************************/

$ligacao->setLinha($linha);

$redirect_ext = new Numero($linha->autenticacao->login_ata);
$ligacao->setExten($redirect_ext);

$ligacao->verificaSigaMe();


if($ligacao->verificaGravacao()){
	//$ligacao->setLinha($ligador);
	$ligacao->setUniqueId($agi->get_variable("UNIQUEID")['data']);
	$ligacao->setDirRec($agi->get_variable("DIR_REC_IN_OUT")['data']);
	$ligacao->setTipoRec("entrantes");
	
	$ligacao->iniciaGravacao();
}

$grupo_atend_model = $linha->grupos()->orderBy('posicao' , 'asc')->first();

$dial = new Dial($ligacao);

if($grupo_atend_model){

	$grupo = new GruposManager($grupo_atend_model, $dial);
	$grupo->exec();
	exit;
	
}

$dial->exec();

if(in_array($dial->getLastStatus(), ['NOANSWER', 'BUSY', 'CONGESTION'])
	&& $linha->facilidades->caixa_postal == 1){
	Logger::write(__FILE__,__LINE__, "Voicemail : ATIVADO");
	Logger::write(__FILE__,__LINE__, "Executando VoiceMail...");

	$manager = new VoiceMailManager();
	$vm = $manager->createVoiceMail(new Numero($linha->autenticacao->login_ata));
	$manager->execVoiceMail($vm);
}
