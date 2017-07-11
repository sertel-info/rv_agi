#!/usr/bin/php -q
<?php
require_once __DIR__."/vendor/autoload.php";

require_once __DIR__."/Classes/Connections.php";
require_once __DIR__."/Classes/Ligacao.php";
require_once __DIR__."/Classes/Dial.php";
require_once __DIR__."/Classes/Agi.php";
require_once __DIR__."/Classes/DbHelper.php";
require_once __DIR__."/Classes/Numero.php";
require_once __DIR__."/Classes/BillCalculator.php";
require_once __DIR__."/Classes/VerificadorPortabilidade.php";

require_once __DIR__."/Models/Linhas/DadosAutenticacaoLinhas.php";
require_once __DIR__."/Models/Linhas/DadosConfiguracoesLinhas.php";
require_once __DIR__."/Models/Linhas/Linhas.php";
require_once __DIR__."/Models/Portabilidade/Numeros.php";
require_once __DIR__."/Models/Portabilidade/Operadoras.php";
require_once __DIR__."/Models/Portabilidade/Prefixos.php";

$verbose = true;

date_default_timezone_set('America/Sao_Paulo');

$agi = new AGI();
$agi->set_variable("CDR(type)", "sainte");

$ligacao = new Ligacao();

$ligacao->setAgi($agi);
$ligacao->setTipo('sainte');

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
$agi->set_variable("CDR(src_account)", $linha->autenticacao->login_ata);


if($linha->configuracoes->callerid !== $callerid->getNumeroCompleto()){
	$agi->write_console(__FILE__,__LINE__, "MUNDANDO CALLERID: ". $linha->configuracoes->callerid, $verbose);

	$agi->set_variable("CALLERID(num)", $linha->configuracoes->callerid);
	$callerid = new Numero($linha->configuracoes->callerid);
}

$agi->write_console(__FILE__,__LINE__, "Linha ".$linha->id, $verbose);

if(!$linha){
	$agi->write_console(__FILE__,__LINE__, "FALHA AO ENCONTRAR LINHA NO BANCO", $verbose);
	die(1);
}

$exten = new Numero($agi->get_variable("EXTEN")['data']);
$agi->set_variable("CDR(dst_type)", $exten->getTipo());

if(empty($exten->getDDD())){
	$exten->setDDD($linha->ddd_local);
}

$agi->set_variable("CDR(dst)", $exten->getNumeroComDDD());

$verif_portab = new VerificadorPortabilidade($exten,
										     new Numeros,
											 new Operadoras,
											 new Prefixos);

$exten->setOperadora($verif_portab->getOperadora());
$exten->setIsPortado($verif_portab->isPortado());

$ligacao->setCallerId($callerid);
$ligacao->setExten($exten);
$ligacao->setTipo("sainte");
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