#!/usr/bin/php -q
<?php
include __DIR__."/../phpagi-asmanager.2.2.php";

$agi = new AGI();
$conn = new mysqli("127.0.0.1", "ipbxsertel", "@ipbxsertel", "ramal_virtual");

$exten = substr($agi->get_variable('EXTEN')['data'], 1);
$callerid = $agi->get_variable('CALLERID(num)')['data'];

date_default_timezone_set('America/Sao_Paulo');
$data = date('H-i-s-d-m-Y');

write_console(__FILE__,__LINE__, "EXTEN: ".$exten);
write_console(__FILE__,__LINE__, "CALLERID: ".$callerid);


if($conn->connect_errno){
	write_console(__FILE__,__LINE__, "FALHA AO CONECTAR AO BANCO(" . $conn->connect_errno . ") " . $conn->connect_error);
	die(1);
}

$query = "SELECT IF(assinantes.nome is null,assinantes.nome_fantasia, assinantes.nome) as nome,".
								  "linhas.cli,".
								  "linhas.tecnologia,".
								  "linhas.id as linha_id,".
								  "assinantes.id as assinante_id ".
								  "FROM dados_autenticacao_linhas ".
								  "LEFT JOIN linhas ON dados_autenticacao_linhas.linha_id = linhas.id ".
								  "LEFT JOIN assinantes ON linhas.assinante_id = assinantes.id ".
								  "WHERE dados_autenticacao_linhas.numero = '".$callerid."' ".
								  "LIMIT 1";


$result = $conn->query($query);


if(!$result || $conn->error){
	write_console(__FILE__,__LINE__, "FALHA AO ENCONTRAR LINHA NO BANCO(" . $conn->error . ") ");
	die(1);
}

if(!$result->num_rows){
	write_console(__FILE__,__LINE__, "NENHUMA LINHA ENCONTRADA NO BANCO");
	die(1);
}

$linha = $result->fetch_assoc();

/** INICIA A GRAVAÇÃO **/
$dir_rec = $agi->get_variable("DIR_REC_IN_OUT")['data'];
$unique_id = $agi->get_variable("UNIQUEID")['data'];
$agi->exec("MixMonitor", "$dir_rec/".$linha['assinante_id']."/".
									 $linha["linha_id"]."/".
									 "out/out-$callerid-$exten-$unique_id-$data.wav49,ab");



write_console(__FILE__,__LINE__, "ASSINANTE: ".$linha['nome']);
write_console(__FILE__,__LINE__, "CLI ATIVA: ".$linha['cli']);


$troncos = parse_ini_file('troncos_ramal_virtual.ini', true);

if(!$troncos){
	write_console(__FILE__,__LINE__, "FALHA AO LER ARQUIVO DE CONFIGURAÇÃO DE TRONCOS ");
	die(1);
}

$tentativas = 0;

foreach($troncos as $nome_tronco=>$tronco){
	if((Boolean)$tronco['cli'] !== (Boolean)$linha['cli']){
		continue;
	}
	$tech_prefix = ((Boolean)$tronco['cli'] ? $tronco['tech_prefix']."#" : "");
	$tempo_chamada = isset($tronco['tempo_chamada']) ? $tronco['tempo_chamada'] : 30;
	
	$agi->exec("DIAL", $linha['tecnologia']."/".$tech_prefix.$exten."@".$nome_tronco.','.$tempo_chamada.',r');

	$dial_status = $agi->get_variable("DIALSTATUS")['data'];
	$tentativas++;

	if(!in_array($dial_status, ["CHANUNAVAIL", "BUSY", "CONGESTION"])){
		break;
	}

	write_console(__FILE__,__LINE__, "TENTATIVA : ".$tentativas);
	write_console(__FILE__,__LINE__, "TRONCO : ".$nome_tronco);
	write_console(__FILE__,__LINE__, "DIAL STATUS: ".$dial_status);
}





