#!/usr/bin/php -q
<?php

require_once __DIR__."/vendor/autoload.php";
require_once __DIR__."/Classes/Connections.php";
require_once __DIR__."/Classes/Agi.php";
require_once __DIR__."/Classes/Numero.php";
require_once __DIR__."/Classes/Log/Logger.php";
require_once __DIR__."/Classes/BillCalculator.php";
require_once __DIR__."/Classes/Notificacoes/AGINotificationMessageCompiler.php";

require_once __DIR__."/Models/Linhas/DadosAutenticacaoLinhas.php";
require_once __DIR__."/Models/Linhas/DadosConfiguracoesLinhas.php";

require_once __DIR__."/Models/Notificacoes/Notificacoes.php";
require_once __DIR__."/Models/Notificacoes/NotificacoesUsers.php";

$agi = AGI::getSingleton();
$duracao = $agi->get_variable('CDR(billsec)')['data'];

Logger::write(__FILE__,__LINE__, "Billsec: ".$duracao);

$exten = new Numero( $agi->get_variable('CDR(dst)')['data'] );
$callerid = new Numero( $agi->get_variable('CALLERID(num)')['data'] );

Logger::write(__FILE__,__LINE__, "Exten: ".$exten->getNumeroCompleto(), '');
Logger::write(__FILE__,__LINE__, "Callerid: ".$callerid->getNumeroCompleto(), '');

$autenticacao_linha = DadosConfiguracoesLinhas::where('callerid', $callerid->getNumeroCompleto())->first();

if(!$autenticacao_linha){
	$autenticacao_linha = DadosAutenticacaoLinhas::where('login_ata', $callerid->getNumeroCompleto())->first();

	if(!$autenticacao_linha)
		$autenticacao_linha = Dids::where('extensao_did', $callerid->getNumero())->first();

	if(!$autenticacao_linha){
		//Logger::write(__FILE__,__LINE__, "FALHA AO ENCONTRAR LINHA: ".$callerid->getNumero(), 1);
		die(1);
	}

} 

$linha = Linhas::complete()->find($autenticacao_linha->linha_id);


$titulo_tarifa = "valor";

if($exten->isDDD()){
	$titulo_tarifa .= $exten->getTipo()."_ddd";
} else if($exten->isDDI()){
	$titulo_tarifa .= "_ddi";
} else {
	$titulo_tarifa .= $exten->getTipo()."_local";
}

$tarifa = $linha->plano()->__get($titulo_tarifa);

Logger::write(__FILE__,__LINE__, "TARIFA: ".$tarifa);

$total_a_pagar = BillCalculator::calcTarifa($exten->getTipo(), $tarifa, $duracao);

$dados_financeiros = $linha->assinante->financeiro;

$novos_creditos = floatval($dados_financeiros->creditos) - $total_a_pagar;

$resul = $dados_financeiros->update(['creditos'=>$novos_creditos]);

$agi->set_variable("CDR(cost)", $total_a_pagar);

/*
if($novos_creditos <= $linha->assinante->financeiro->alerta_saldo ){
	$msg_compiler = new AGINotificationMessageCompiler();
	$notifications = Notificacoes::where('escutar_evento', 'CreditosAcabando')->get();

	foreach($notifications as $notification){
		$notification_user = new NotificacoesUsers();
	   	$compiled_msg = $msg_compiler->compile($notification->mensagem, $linha->assinante);
	   	Logger::write(__FILE__,__LINE__, "Compiled msg ".$compiled_msg);

	   	$notification_user->vista = false;
	    $notification_user->notificacao_id = $notification->id;
	    $notification_user->user_id = $linha->assinante->user->id;
	    $notification_user->mensagem_compilada = $compiled_msg;
	    $notification_user->save();
	}

}*/