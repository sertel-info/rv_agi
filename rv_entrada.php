#!/usr/bin/php -q
<?php
require_once __DIR__."/Dial.php";
require_once __DIR__."/agi_novo.php";
require_once __DIR__."/Ligacao.php";
require_once __DIR__."/DbHelper.php";
require_once __DIR__."/Numero.php";
require_once __DIR__."/vendor/autoload.php";
require_once __DIR__."/Connections.php";
require_once __DIR__."/Models/Linhas/Dids.php";

date_default_timezone_set('America/Sao_Paulo');

//$db = new DbHelper(new mysqli("127.0.0.1", "ipbxsertel", "@ipbxsertel", "ramal_virtual"));
$agi = new AGI();

$agi->set_variable("CDR(type)", "entrante");
$ligacao = new Ligacao();
$ligacao->setAgi($agi);

$callerid = new Numero($agi->get_variable("CALLERID(num)")['data']);
$exten = new Numero($agi->get_variable("EXTEN")['data']);

$ligacao->setCallerId($callerid);
$ligacao->setExten($exten);

//$line = $db->getLine($ligacao->getExten());
//$ligacao->setLine($line);
$did_linha = Dids::where('extensao_did', $exten->getNumeroCompleto())
					->orWhere('extensao_did', $exten->getNumeroComDDD().' ')
					->first();

if(!$did_linha){
	$agi->write_console(__FILE__,__LINE__, "FALHA AO ENCONTRAR EXTENSÃO: ".$callerid->getNumero().'/'.$callerid->getNumeroComDDD());
	die(1);
} 

$linha = Linhas::complete()->find($did_linha);

$ligacao->setLinha($linha);

$redirect_ext = new Numero($linha->autenticacao->login_ata);
$ligacao->setExten($redirect_ext);
$ligacao->verificaSigaMe();

if($ligacao->verificaGravacao()){
	//$ligacao->setLinha($ligador);
	$ligacao->setUniqueId($agi->get_variable("UNIQUEID")['data']);
	$ligacao->setDirRec($agi->get_variable("DIR_REC_IN_OUT")['data']);
	$ligacao->setTipoRec("internas");
	
	$ligacao->iniciaGravacao();
}

$dial = new Dial($ligacao);
$dial->execEntreRamais();

if(in_array($dial->getLastStatus(), ['NOANSWER', 'BUSY', 'CONGESTION'])){
	$ligacao->execVoiceMail();
}