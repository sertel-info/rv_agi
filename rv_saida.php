#!/usr/bin/php -q
<?php
require_once __DIR__."/vendor/autoload.php";

require_once __DIR__."/Classes/Connections.php";
require_once __DIR__."/Classes/Ligacao.php";
require_once __DIR__."/Classes/Dial.php";
require_once __DIR__."/Classes/Agi.php";
require_once __DIR__."/Classes/Numero.php";
require_once __DIR__."/Classes/BillCalculator.php";
require_once __DIR__."/Classes/VerificadorPortabilidade.php";
require_once __DIR__."/Classes/VerificadorPermissoes.php";
require_once __DIR__."/Classes/Log/Logger.php";

require_once __DIR__."/Models/Linhas/DadosAutenticacaoLinhas.php";
require_once __DIR__."/Models/Linhas/DadosConfiguracoesLinhas.php";
require_once __DIR__."/Models/Linhas/Linhas.php";
require_once __DIR__."/Models/Portabilidade/Numeros.php";
require_once __DIR__."/Models/Portabilidade/Operadoras.php";
require_once __DIR__."/Models/Portabilidade/Prefixos.php";


$verbose = true;

date_default_timezone_set('America/Sao_Paulo');

$agi = AGI::getSingleton();
$agi->set_variable("CDR(type)", "sainte");

$ligacao = new Ligacao();

$ligacao->setTipo('sainte');

$callerid = new Numero($agi->get_variable("CALLERID(num)")['data']);
$exten = new Numero($agi->get_variable("EXTEN")['data']);

Logger::write(__FILE__,__LINE__, "CALLERID: ".$callerid->getNumeroCompleto(), $verbose);

$autenticacao_linha = DadosConfiguracoesLinhas::where('callerid', $callerid->getNumeroCompleto())->first();

if(!$autenticacao_linha){
	$autenticacao_linha = DadosAutenticacaoLinhas::where('login_ata', $callerid->getNumeroCompleto())->first();

	if(!$autenticacao_linha){
		Logger::write(__FILE__,__LINE__, "fala 2 ", $verbose);
		$autenticacao_linha = Dids::where('extensao_did', $callerid->getNumero())->first();
	}

	if(!$autenticacao_linha){
		Logger::write(__FILE__,__LINE__, "FALHA AO ENCONTRAR EXTENSÃO: ".$callerid->getNumeroCompleto(), $verbose);
		die(1);
	}	
} 

$linha = Linhas::complete()->find($autenticacao_linha->linha_id);

/** VERIFICA PERMISSÕES **/
	
	$verif_permissoes = new VerificadorPermissoes($agi);

	$hasPermissao = $verif_permissoes->verificar($linha->permissoes, $exten);
	
	if(!$hasPermissao){
		foreach ($verif_permissoes->getErros() as $erro){
			Logger::write(__FILE__,__LINE__, $erro, $verbose);
		}
		exit;
	}

/************************/

$agi->set_variable("CDR(src_account)", $linha->autenticacao->login_ata);


if($linha->configuracoes->callerid !== $callerid->getNumeroCompleto()){
	Logger::write(__FILE__,__LINE__, "MUNDANDO CALLERID: ". $linha->configuracoes->callerid, $verbose);

	$agi->set_variable("CALLERID(num)", $linha->configuracoes->callerid);
	$callerid = new Numero($linha->configuracoes->callerid);
}

Logger::write(__FILE__,__LINE__, "Linha ".$linha->id, $verbose);

if(!$linha){
	Logger::write(__FILE__,__LINE__, "FALHA AO ENCONTRAR LINHA NO BANCO", $verbose);
	die(1);
}

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

$titulo_tarifa = "valor";

if($exten->isDDD()){
	$titulo_tarifa .= "_".$exten->getTipo()."_ddd";
} else if($exten->isDDI()){
	$titulo_tarifa .= "_ddi";
} else {
	$titulo_tarifa .= "_".$exten->getTipo()."_local";
}

Logger::write(__FILE__,__LINE__, "Título tarifa: ".$titulo_tarifa, 1);

$ligacao->setTarifa($linha->assinante->planos()->first()->__get($titulo_tarifa));
Logger::write(__FILE__,__LINE__, "Tarifa da ligação: ".$ligacao->getTarifa(),1);

$ligacao->setLimiteTempo(BillCalculator::calcTempoMaxLigacao($ligacao));

Logger::write(__FILE__,__LINE__, "Créditos do assinante: ".$linha->assinante->financeiro->creditos,1);
Logger::write(__FILE__,__LINE__, "Tempo máximo da ligação: ".$ligacao->getLimiteTempo(),1);

if($ligacao->verificaGravacao()){
	//$ligacao->setLinha($ligador);
	$ligacao->setUniqueId($agi->get_variable("UNIQUEID")['data']);
	$ligacao->setDirRec($agi->get_variable("DIR_REC_IN_OUT")['data']);
	$ligacao->setTipoRec("saintes");
	
	$ligacao->iniciaGravacao();
}

$dial = new Dial($ligacao);
$dial->exec();