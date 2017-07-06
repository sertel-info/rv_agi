#!/usr/bin/php -q
<?php
require_once __DIR__."/Classes/Agi.php";
require_once __DIR__."/Classes/MailService.php";
require_once __DIR__."/Classes/DbHelper.php";
require_once __DIR__.'/vendor/autoload.php';

date_default_timezone_set('America/Sao_Paulo');

define("MAIL_SERVICE_ADDRESS", "http://127.0.0.1:8889");

$agi = new AGI();
$db = new DbHelper(new mysqli("127.0.0.1", "ipbxsertel", "@ipbxsertel", "ramal_virtual"));

$rv_voice_mail_data = $agi->get_variable("rv_voice_mail_data");

if(!$rv_voice_mail_data['result']){
	$agi->write_console(__FILE__,__LINE__, "Não foi possivel acessar a variável rv_voice_mail_data");
	die(1);
}

//$agi->write_console(__FILE__,__LINE__, "ENVIANDO EMAIL DO VOICEMAIL");

$dados = $rv_voice_mail_data['data'];
$dados = json_decode($dados, true);

$cfg_padrao_vm = $db->getConfigs();

$campos_padrao = array("mensagem"=>$cfg_padrao_vm['voice_mail_mensagem_padrao'],
						 "assunto"=>$cfg_padrao_vm['voice_mail_assunto_padrao'],
						 "receptor"=>$dados['data']['receptor'],
						 "remetente"=>$cfg_padrao_vm['voice_mail_remetente_padrao']);

/** Os campos que vierem pela variável "rv_voice_mail_data"
	Substituirão os padrões vindos do banco, caso necessário **/
 
$campos = array_merge($campos_padrao, $dados['data']);


/**$agi->write_console(__FILE__,__LINE__, "Campos !! ");
foreach($campos as $nome=>$campo){
	$agi->write_console(__FILE__,__LINE__, "Campo: ".$nome. " Valor: ".$campo);
}**/


$mail_service = new MailService(MAIL_SERVICE_ADDRESS, new Unirest\Request\Body, new Unirest\Request);

$mail_service->setData($campos);

if(isset($dados['exten']) && $dados['exten'] !== ''){
	
	/** PEGA O ARQUIVO DA GRAVAÇÃO **/
	$spool_asterisk = '/var/spool/asterisk/voicemail/rv_correio_voz/'.$dados['exten'].'/INBOX/';
	$gravacao = glob($spool_asterisk . '*.wav');
	$gravacao = array_combine($gravacao, array_map('filectime', $gravacao));
	arsort($gravacao);
	$gravacao = key($gravacao);
	$agi->write_console(__FILE__,__LINE__, "ARQUIVO DE GRAVAÇÃO: " . $gravacao);
	/*******/

	//$gravacao = '/var/spool/asterisk/voicemail/rv_correio_voz/2000/INBOX/msg0000.wav';
	$arquivos = array('gravacao'=>$gravacao);

	/*** CRIA LINK SIMBÓLICO DA GRAVAÇÃO DA PASTA SPOOL PARA A PASTA /OPT/RV_VOICEMAIL **/
	if($gravacao !== ''){
        $link_path = "/opt/rv_voice_mail";
        $assinante_folder = $link_path . '/' . $dados['assinante'];
        $exten_folder = $assinante_folder . '/' . $dados['exten'];
		
        if(!file_exists($link_path)){
			$agi->write_console(__FILE__,__LINE__, "NÃO EXISTE O DESTINO DO LINK : ");

			if(!mkdir($link_path)){
				$agi->write_console(__FILE__,__LINE__, "FALHA AO CRIAR A PASTA : " . $link_path);        	
			}
        }

        if(!file_exists($assinante_folder)){
			$agi->write_console(__FILE__,__LINE__, "NÃO EXISTE A PASTA DO ASSINANTE : " . $assinante_folder);        	

			if(!mkdir($assinante_folder)){
				$agi->write_console(__FILE__,__LINE__, "FALHA AO CRIAR PASTA DO ASSINANTE: " . $assinante_folder);        	
	        }

        }

        if(!file_exists($exten_folder)){
			$agi->write_console(__FILE__,__LINE__, "NÃO EXISTE A PASTA DO EXTEN : " . $exten_folder);        	

			if(!mkdir($exten_folder)){
				$agi->write_console(__FILE__,__LINE__, "FALHA AO CRIAR PASTA DO EXTEN: " . $exten_folder);        	
        	}
        }

        $nome_wav = basename($gravacao);
        $nome_txt = basename($nome_wav, ".wav") . ".txt";

        if(symlink($gravacao, $exten_folder . "/" . $nome_wav) &&
           symlink($spool_asterisk . $nome_txt  , $exten_folder . '/'. $nome_txt)){
			$agi->write_console(__FILE__,__LINE__, "LINK DA GRAVAÇÃO CRIADO COM SUCESSO: ");
        } else {
			$agi->write_console(__FILE__,__LINE__, "FALHA AO CRIAR O LINK DA GRAVAÇÃO: ");        		
        }
    }
    /*********************************************************************************/

	$mail_service->setAttachment($arquivos);
}

$response = $mail_service->send();

if(gettype($response->body) == 'string'){
	$agi->write_console(__FILE__,__LINE__, "RESULTADO VOICE MAIL: ".$response->body);
}
