#!/usr/bin/php -q
<?php
require_once __DIR__."/vendor/autoload.php";
require_once __DIR__."/Ligacao.php";
require_once __DIR__."/Dial.php";
require_once __DIR__."/agi_novo.php";

require_once __DIR__."/Numero.php";
//require_once __DIR__."/Gravacao.php";
require_once __DIR__."/Connections.php";

require_once __DIR__."/Models/Linhas/DadosAutenticacaoLinhas.php";
require_once __DIR__."/Models/Linhas/DadosConfiguracoesLinhas.php";
require_once __DIR__."/Models/Linhas/Linhas.php";
require_once __DIR__."/Models/Logs/Gravacoes.php";

date_default_timezone_set('America/Sao_Paulo');

$verbose = 1;

$agi = new AGI();
$agi->set_variable("CDR(type)", "internas");

$ligacao = new Ligacao();
$ligacao->setAgi($agi);

//$db = new DbHelper(new mysqli("127.0.0.1", "ipbxsertel", "@ipbxsertel", "ramal_virtual"));

$callerid = new Numero($agi->get_variable("CALLERID(num)")['data']);
$exten = new Numero($agi->get_variable("EXTEN")['data']);

$agi->set_variable("CDR(dst)", $exten->getNumero());

$ligacao->setCallerId($callerid);
$ligacao->setExten($exten);

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
$agi->set_variable("CDR(ramal)", '123');

//$autenticacao_ligador = DadosConfiguracoesLinhas::where('callerid', $callerid->getNumero())->first();

$autenticacao_receptor = DadosAutenticacaoLinhas::where('login_ata', $ligacao->getExtenObj()->getNumero())->first();

if(!$autenticacao_receptor){
	$agi->write_console(__FILE__,__LINE__, "FALHA AO ENCONTRAR O RECEPTOR: ".$ligacao->getExtenObj()->getNumero(), $verbose);
	die(1);
} 

$receptor = Linhas::complete()->find($autenticacao_receptor->linha_id);

$ligacao->setLinha($receptor);

$ligacao->verificaSigaMe();

if($ligacao->verificaGravacao()){
	$ligacao->setLinha($ligador);
	$ligacao->setUniqueId($agi->get_variable("UNIQUEID")['data']);
	$ligacao->setDirRec($agi->get_variable("DIR_REC_IN_OUT")['data']);
	$ligacao->setTipoRec("internas");

	$ligacao->iniciaGravacao();
}


$dial = new Dial($ligacao);
$dial->exec();

if(in_array($dial->getLastStatus(), ['NOANSWER', 'BUSY', 'CONGESTION'])){
    $vm_resp = $ligacao->execVoiceMail();
}

