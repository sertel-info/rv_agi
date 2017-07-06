#!/usr/bin/php -q
<?php
require_once __DIR__."/vendor/autoload.php";

require_once __DIR__."/Classes/Connections.php";
require_once __DIR__."/Classes/Ligacao.php";
require_once __DIR__."/Classes/Dial.php";
require_once __DIR__."/Classes/Agi.php";
require_once __DIR__."/Classes/Numero.php";
require_once __DIR__."/Classes/Saudacao.php";
//require_once __DIR__."/Classes/UraManager.php";
require_once __DIR__."/Classes/AtendAutomaticoManager.php";

require_once __DIR__."/Models/Linhas/DadosAutenticacaoLinhas.php";
require_once __DIR__."/Models/Linhas/DadosConfiguracoesLinhas.php";
require_once __DIR__."/Models/Linhas/Linhas.php";
require_once __DIR__."/Models/Logs/Gravacoes.php";
require_once __DIR__."/Models/Uras/Uras.php";

date_default_timezone_set('America/Sao_Paulo');

$verbose = 1;

$agi = new AGI();
$agi->set_variable("CDR(type)", "internas");

$ligacao = new Ligacao();
$ligacao->setAgi($agi);

$callerid = new Numero($agi->get_variable("CALLERID(num)")['data']);
$exten = new Numero($agi->get_variable("EXTEN")['data']);

$agi->set_variable("CDR(dst)", $exten->getNumero());

$ligacao->setCallerId($callerid);
$ligacao->setExten($exten);
$ligacao->setTipo("interna");

$agi->write_console(__FILE__,__LINE__, "Exten: ".$ligacao->getExten(), $verbose);
$agi->write_console(__FILE__,__LINE__, "Callerid: ".$ligacao->getCallerId(), $verbose);

$autenticacao_ligador = DadosConfiguracoesLinhas::where('callerid', $callerid->getNumeroCompleto())->first();

if(!$autenticacao_ligador){

	$autenticacao_ligador = DadosAutenticacaoLinhas::where('login_ata', $callerid->getNumero())->first();

	if(!$autenticacao_ligador){
		$agi->write_console(__FILE__,__LINE__, "FALHA AO ENCONTRAR EXTENSÃO: ".$ligacao->getCallerId(), $verbose);
		die(1);
	}
	
}

$ligador = Linhas::complete()->find($autenticacao_ligador->linha_id);

$autenticacao_receptor = DadosAutenticacaoLinhas::where('login_ata', $ligacao->getExtenObj()->getNumero())->first();

if(!$autenticacao_receptor){
	$agi->write_console(__FILE__,__LINE__, "FALHA AO ENCONTRAR O RECEPTOR: ".$ligacao->getExtenObj()->getNumero(), $verbose);
	die(1);
} 

$receptor = Linhas::complete()->find($autenticacao_receptor->linha_id);

/** SAUDAÇÕES **/

if( $receptor->assinante->facilidades->saudacoes == 1 && 
   ($destino = $receptor->facilidades->saudacoes_destino) !== null){
	
	$agi->write_console(__FILE__,__LINE__, "SAUDAÇÃO ATIVA ".$destino);

	$saudacoes =  $receptor->assinante->saudacoes()->whereRaw("MD5(id) = '".$destino."'")->get();

	foreach($saudacoes as $saudacao_model){
		$saudacao = new Saudacao($saudacao_model, $saudacao_model->audio, $agi);
		$saudacao->exec();
	}
}

/****************************/

$ligacao->setLinha($receptor);
$ligacao->verificaSigaMe();

/*
if($ligacao->verificaGravacao()){
	$ligacao->setLinha($ligador);
	$ligacao->setUniqueId($agi->get_variable("UNIQUEID")['data']);
	$ligacao->setDirRec($agi->get_variable("DIR_REC_IN_OUT")['data']);
	$ligacao->setTipoRec("internas");

	$ligacao->iniciaGravacao();
}
*/

$dial = new Dial($ligacao);
$dial->exec();

if(in_array($dial->getLastStatus(), ['NOANSWER', 'BUSY', 'CONGESTION'])){
    $vm_resp = $ligacao->execVoiceMail();
}

