#!/usr/bin/php -q
<?php

require_once __DIR__."/vendor/autoload.php";
require_once __DIR__."/Classes/Connections.php";
require_once __DIR__."/Classes/Agi.php";
require_once __DIR__."/Classes/Numero.php";
require_once __DIR__."/Classes/Log/Logger.php";
require_once __DIR__."/Classes/BillCalculator.php";
require_once __DIR__."/Classes/Notificacoes/AGINotificationMessageCompiler.php";

require_once __DIR__."/Models/Linhas/Dids.php";

require_once __DIR__."/Models/Notificacoes/Notificacoes.php";
require_once __DIR__."/Models/Notificacoes/NotificacoesUsers.php";

$agi = AGI::getSingleton();
$duracao = $agi->get_variable('CDR(billsec)')['data'];

Logger::write(__FILE__,__LINE__, "Billsec: ".$duracao);

$exten = new Numero( $agi->get_variable('CDR(dst)')['data'] );
$callerid = new Numero( $agi->get_variable('CALLERID(num)')['data'] );

Logger::write(__FILE__,__LINE__, "Exten: ".$exten->getNumeroCompleto(), 1);
Logger::write(__FILE__,__LINE__, "Callerid: ".$callerid->getNumeroCompleto(), 1);

$autenticacao_linha = Dids::where('extensao_did','like', '%'.$exten->getNumeroCompleto())
							->first();

if(!$autenticacao_linha){
	Logger::write(__FILE__,__LINE__, "FALHA AO ENCONTRAR LINHA: ".$exten->getNumeroCompleto());
	exit;
}

$linha = Linhas::complete()->find($autenticacao_linha->linha_id);

if(!$linha){
	Logger::write(__FILE__,__LINE__, "FALHA AO ENCONTRAR LINHA: ".$exten->getNumeroCompleto());
	exit;
}

if($callerid->getTipo() == null){
	$tipo = 'movel';
}

$tarifa = $linha->plano()->__get('valor_'  . $tipo . '_entrante');

Logger::write(__FILE__,__LINE__, "TARIFA: ".$tarifa);

$total_a_pagar = BillCalculator::calcTarifa('movel', $tarifa, $duracao);

$dados_financeiros = $linha->assinante->financeiro;

$novos_creditos = floatval($dados_financeiros ->creditos) - $total_a_pagar;

$resul = $dados_financeiros->update(['creditos'=>$novos_creditos]);

Logger::write(__FILE__,__LINE__, "TOTAL A PAGAR: ".$total_a_pagar);

$agi->set_variable("CDR(cost)", $total_a_pagar);

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

}