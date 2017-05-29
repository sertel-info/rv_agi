<?php

//require_once __DIR__."/DbHelper.php";
//require_once __DIR__."/Ligacao.php";
//require_once __DIR__."/Numero.php";
require_once __DIR__."/vendor/autoload.php";
require_once __DIR__."/Connections.php";
require_once __DIR__."/Models/Logs/Gravacoes.php";

$arquivo_caminho = $argv[2];

if(!file_exists($arquivo_caminho)){
	echo chr(13).chr(10)."Arquivo de gravação não foi criado '".$arquivo_caminho."'".chr(13).chr(10);
	exit;
}

echo chr(13).chr(10)."Arquivo criardo criado '".$arquivo_caminho."'".chr(13).chr(10);

//retira a extensão
//$arquivo = explode('.',  $arquivo_caminho)[0];
$arquivo = pathinfo($arquivo_caminho);

list($sys_path, $dir_rec, $assinante_id, $linha_id, $rec_tipo) = explode('/', substr($arquivo['dirname'], 1)) ;

///home/rv_gravacoes/12/14/internas/internas-20180373-2000-1494941018_131-10_20_10_20_2017.wav49

list($rec_tipo, $callerid, $exten, $unique_id, $data) = explode('-', $arquivo['filename']);

/*$callerid = new Numero($callerid);
$exten = new Numero($exten);*/

$line = [
		'linha_id'=>$linha_id,
		'assinante_id'=>$assinante_id
		];

$gravacao = new Gravacoes([
							"exten"=>$exten,
							"callerid"=>$callerid,
							"data"=>DateTime::createFromFormat('H_i_s_d_m_Y', $data)->format('H-i-s-d-m-Y'),
							"unique_id"=>$unique_id,
							"linha"=>$linha_id,
							"assinante"=>$assinante_id,
							"arquivo"=>$arquivo['dirname']."/".$arquivo['filename'].'.'.$arquivo['extension']]);
/*
$ligacao = new Ligacao();

$ligacao->setExten($exten);
$ligacao->setCallerId($callerid);

$ligacao->setDate(DateTime::createFromFormat('H_i_s_d_m_Y', $data)->format('H-i-s-d-m-Y'));
$ligacao->setLine($line);

$gravacao = new Gravacao();

$gravacao->setDb(new DbHelper(new mysqli("127.0.0.1", "ipbxsertel", "@ipbxsertel", "rv_logs")));
$gravacao->setPasta($pasta);
$gravacao->setDirRec($dir_rec);
$gravacao->setUniqueId($unique_id);
$gravacao->setArquivo($arquivo['dirname']."/".$arquivo['filename'].'.'.$arquivo['extension']);

$gravacao->setLigacao($ligacao);
*/
$gravacao->save();
exit;
