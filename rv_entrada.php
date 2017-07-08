#!/usr/bin/php -q
<?php
require_once __DIR__."/vendor/autoload.php";

require_once __DIR__."/Classes/Connections.php";
require_once __DIR__."/Classes/Dial.php";
require_once __DIR__."/Classes/Agi.php";
require_once __DIR__."/Classes/Ligacao.php";
require_once __DIR__."/Classes/DbHelper.php";
require_once __DIR__."/Classes/GruposManager.php";
require_once __DIR__."/Classes/Numero.php";
require_once __DIR__."/Classes/UraManager.php";
require_once __DIR__."/Classes/Saudacao.php";
require_once __DIR__."/Classes/AtendAutomaticoManager.php";

require_once __DIR__."/Models/Linhas/Dids.php";
//require_once __DIR__."/Models/Saudacoes/Saudacoes.php";

date_default_timezone_set('America/Sao_Paulo');
$verbose = true;

//$db = new DbHelper(new mysqli("127.0.0.1", "ipbxsertel", "@ipbxsertel", "ramal_virtual"));
$agi = new AGI();

$agi->set_variable("CDR(type)", "entrante");
$ligacao = new Ligacao();
$ligacao->setTipo('entrante');
$ligacao->setAgi($agi);

$callerid = new Numero($agi->get_variable("CALLERID(num)")['data']);
$exten = new Numero($agi->get_variable("EXTEN")['data']);

$ligacao->setCallerId($callerid);
$ligacao->setExten($exten);

//$line = $db->getLine($ligacao->getExten());
//$ligacao->setLine($line);

$did_linha = Dids::where('extensao_did', $exten->getNumeroCompleto())->first();

if(!$did_linha){
	$agi->write_console(__FILE__,__LINE__, "FALHA AO ENCONTRAR EXTENSÃO: ".$exten->getNumeroCompleto(), $verbose);
	die(1);
} 

$linha = Linhas::complete()->find($did_linha);

/** SAUDAÇÕES **/

if( $linha->assinante->facilidades->saudacoes == 1 && 
   ($destino = $linha->facilidades->saudacoes_destino) !== null){
	
	$agi->write_console(__FILE__,__LINE__, "SAUDAÇÃO ATIVA ".$destino);

	$saudacoes =  $linha->assinante->saudacoes()->whereRaw("MD5(id) = '".$destino."'")->get();

	foreach($saudacoes as $saudacao_model){
		$saudacao = new Saudacao($saudacao_model, $saudacao_model->audio, $agi);
		$saudacao->exec();
	}
}

/** ATENDIMENTO AUTOMÁTICO **/

if($linha->facilidades->atend_automatico == 1){
	$agi->write_console(__FILE__,__LINE__, "ATENDIMENTO AUTOMÁTICO ATIVO : ".$linha->facilidades->atend_automatico_tipo);
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

if(in_array($dial->getLastStatus(), ['NOANSWER', 'BUSY', 'CONGESTION'])){
	$ligacao->execVoiceMail();
}