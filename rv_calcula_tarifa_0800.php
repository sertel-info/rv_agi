#!/usr/bin/php -q
<?php

require_once __DIR__."/vendor/autoload.php";
require_once __DIR__."/Classes/Connections.php";
require_once __DIR__."/Classes/Agi.php";
require_once __DIR__."/Classes/Numero.php";
require_once __DIR__."/Classes/Log/Logger.php";
require_once __DIR__."/Classes/BillCalculator.php";
require_once __DIR__."/Models/Users/Notifications.php";
require_once __DIR__."/Models/Linhas/DadosAutenticacaoLinhas.php";
require_once __DIR__."/Models/Linhas/DadosConfiguracoesLinhas.php";

$agi = new AGI();
$duracao = $agi->get_variable('CDR(billsec)')['data'];

Logger::write(__FILE__,__LINE__, "Billsec: ".$duracao);

$exten = new Numero( $agi->get_variable('CDR(dst)')['data'] );
$callerid = new Numero( $agi->get_variable('CDR(src)')['data'] );;

Logger::write(__FILE__,__LINE__, "Exten: ".$exten->getNumeroCompleto(), 1);
Logger::write(__FILE__,__LINE__, "Callerid: ".$callerid->getNumeroCompleto(), 1);

$autenticacao_linha = DadosConfiguracoesLinhas::where('callerid', $exten->getNumeroCompleto())->first();

if(!$autenticacao_linha){
	$autenticacao_linha = DadosAutenticacaoLinhas::where('login_ata', $exten->getNumero())->first();

	if(!$autenticacao_linha)
		$autenticacao_linha = Dids::where('extensao_did', $exten->getNumero())->first();

	if(!$autenticacao_linha){
		Logger::write(__FILE__,__LINE__, "FALHA AO ENCONTRAR LINHA: ".$exten->getNumero(), 1);
		die(1);
	}
} 

$linha = Linhas::complete()->find($autenticacao_linha->linha_id);

//$exten->setTipo('fixo');
$tipo_callerid = $callerid->getTipo();

if($tipo_callerid == 'fixo'){
	$tarifa = 0.28;
//} else if($tipo_callerid == 'movel' || $tipo_callerid == null){
}else{
	$tarifa = 0.99;
}

Logger::write(__FILE__,__LINE__, "TARIFA: ".$tarifa);

$total_a_pagar = BillCalculator::calcTarifa('movel', $tarifa, $duracao);

$dados_financeiros = $linha->assinante->financeiro;

$novos_creditos = floatval($dados_financeiros->creditos) - $total_a_pagar;

$dados_financeiros->update(['creditos'=>$novos_creditos]);

/*$notification = $linha->assinante->user->notifications();

$current_date = (new DateTime())->format('Y-m-d');

$notifications_today = $notification->where("type", "alerta-saldo")
								    ->where("DATE_FORMAT(created_at, '%Y-%m-%d')", "=", $current_date)
								    ->count();

if($novos_creditos <= floatval($dados_financeiros->alerta_saldo) && $notifications_today = 0){

	$notification = new Notifications();
	$notification->title = "Alerta de saldo";
	$notification->message = "Seus créditos estão chegando ao final";
	$notification->seen = false;
	$notification->level = "danger";
	$notification->type = "alerta-saldo";

	$linha->assinante->user->notifications()->save($notification);
}*/

$agi->set_variable("CDR(cost)", $total_a_pagar);
