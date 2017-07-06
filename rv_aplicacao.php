#!/usr/bin/php -q
<?php
require_once __DIR__."/vendor/autoload.php";
require_once __DIR__."/Connections.php";

require_once __DIR__."/Aplicacao.php";
require_once __DIR__."/Numero.php";
require_once __DIR__."/Ligacao.php";
require_once __DIR__."/agi_novo.php";


require_once __DIR__."/Models/Linhas/Linhas.php";
require_once __DIR__."/Models/Linhas/DadosAutenticacaoLinhas.php";
require_once __DIR__."/Models/Linhas/DadosConfiguracoesLinhas.php";
require_once __DIR__."/Models/Configuracoes/Configuracoes.php";

date_default_timezone_set('America/Sao_Paulo');
$agi = new AGI();

$ligacao = new Ligacao();
$ligacao->setAgi($agi);

$callerid = new Numero($agi->get_variable("CALLERID(num)")['data']);
$exten = new Numero($agi->get_variable("EXTEN")['data']);

$ligacao->setExten($exten);
$ligacao->setCallerId($callerid);

$agi->write_console(__FILE__,__LINE__, "Exten: ".$ligacao->getExten(), 1);
$agi->write_console(__FILE__,__LINE__, "Callerid: ".$ligacao->getCallerId(), 1);

//$autenticacao_linha = DadosConfiguracoesLinhas::where('callerid', $callerid->getNumeroCompleto())->first();

if(!$autenticacao_linha){

	$autenticacao_linha = DadosAutenticacaoLinhas::where('login_ata', $callerid->getNumeroCompleto())->first();

	if(!$autenticacao_linha){
		$agi->write_console(__FILE__,__LINE__, "FALHA AO ENCONTRAR EXTENSÃO: ".$callerid->getNumeroCompleto(), $verbose);
		die(1);
	}
	
}

$linha = $autenticacao_linha->linha()->complete()->first();

$configuracoes = Configuracoes::first();

$aplicacao = new Aplicacao($agi, $ligacao, $linha, $configuracoes);


$aplicacao->exec();



