#!/usr/bin/php -q
<?php
require_once __DIR__."/vendor/autoload.php";

require_once __DIR__."/Ligacao.php";
require_once __DIR__."/Dial.php";
require_once __DIR__."/agi_novo.php";
require_once __DIR__."/DbHelper.php";
require_once __DIR__."/Numero.php";
require_once __DIR__."/BillCalculator.php";
//require_once __DIR__."/Gravacao.php";
require_once __DIR__."/VerificadorPortabilidade.php";

require_once __DIR__."/Connections.php";
require_once __DIR__."/Models/Linhas/DadosAutenticacaoLinhas.php";
require_once __DIR__."/Models/Linhas/DadosConfiguracoesLinhas.php";
require_once __DIR__."/Models/Linhas/Linhas.php";

$verbose = true;

date_default_timezone_set('America/Sao_Paulo');

$agi = new AGI();
$agi->set_variable("CDR(type)", "sainte");

$ligacao = new Ligacao();
$ligacao->setAgi($agi);


$callerid = new Numero($agi->get_variable("CALLERID(num)")['data']);

$agi->write_console(__FILE__,__LINE__, "CALLERID: ".$callerid->getNumeroCompleto(), $verbose);

$autenticacao_linha = DadosConfiguracoesLinhas::where('callerid', $callerid->getNumeroCompleto())->first();

if(!$autenticacao_linha){
	$autenticacao_linha = DadosAutenticacaoLinhas::where('login_ata', $callerid->getNumeroCompleto())->first();

	if(!$autenticacao_linha){
		$agi->write_console(__FILE__,__LINE__, "fala 2 ", $verbose);
		$autenticacao_linha = Dids::where('extensao_did', $callerid->getNumero())->first();
	}

	if(!$autenticacao_linha){
		$agi->write_console(__FILE__,__LINE__, "FALHA AO ENCONTRAR EXTENSÃO: ".$callerid->getNumeroCompleto(), $verbose);
		die(1);
	}	
} 

$linha = Linhas::complete()->find($autenticacao_linha->linha_id);

$agi->write_console(__FILE__,__LINE__, "Linha ".$linha->id, $verbose);

if(!$linha){
	$agi->write_console(__FILE__,__LINE__, "FALHA AO ENCONTRAR LINHA NO BANCO", $verbose);
	die(1);
}


$exten = new Numero($agi->get_variable("EXTEN")['data']);

if(empty($exten->getDDD())){
	$exten->setDDD($linha->ddd_local);
}

$verif_portab = new VerificadorPortabilidade($exten);
$exten->setOperadora($verif_portab->getOperadora());
$exten->setIsPortado($verif_portab->isPortado());

$ligacao->setCallerId($callerid);
$ligacao->setExten($exten);

$ligacao->setLinha($linha);

$ligacao->verificaCadeado();

$ligacao->setTarifa($linha->assinante->planos()->first()->__get('valor_'  . 
											$exten->getTipo() . '_' .
											($exten->isDDD() ? 'ddd' : 'local')));

$ligacao->setLimiteTempo(BillCalculator::calcTempoMaxLigacao($ligacao));

$agi->write_console(__FILE__,__LINE__, "Tarifa da ligação: ".$ligacao->getTarifa(),1);
$agi->write_console(__FILE__,__LINE__, "Créditos do assinante: ".$linha->assinante->financeiro->creditos,1);
$agi->write_console(__FILE__,__LINE__, "Tempo máximo da ligação: ".$ligacao->getLimiteTempo(),1);

if($ligacao->verificaGravacao()){
	//$ligacao->setLinha($ligador);
	$ligacao->setUniqueId($agi->get_variable("UNIQUEID")['data']);
	$ligacao->setDirRec($agi->get_variable("DIR_REC_IN_OUT")['data']);
	$ligacao->setTipoRec("saintes");
	
	$ligacao->iniciaGravacao();
}

$dial = new Dial($ligacao);
$dial->exec();