<?php

require_once __DIR__."/Log/Logger.php";
require_once __DIR__."/Agi.php";

class VerificadorPermissoes{
	
	private $erros = array();
	private $agi;

	function __construct(){
		$this->agi = AGI::getSingleton();
	}

	public function verificar($permissoes, $exten){
		Logger::write(__FILE__,__LINE__, "verificando permissao", 1);

		$tipo_ligacao = "";
		$hasPermissao = false;
		$isAtiva = false;

		if($exten->getTipo() == "interno"){
			$tipo_ligacao = "ip";
		} else {
			$tipo_ligacao = $exten->getTipo();
		}

		$isAtiva = $permissoes->__get("status");

		$hasPermissao = $permissoes->__get("ligacao_".$tipo_ligacao);

		if(!$isAtiva){
			array_push($this->erros, "Esta linha não está ativa !!");
		}

		if(!$hasPermissao){
			array_push($this->erros, "Esta linha não tem permissão para fazer ligação do tipo ".$tipo_ligacao);
		}

		return (Boolean)$hasPermissao && (Boolean)$isAtiva;
	}

	public function getErros(){
		return $this->erros;
	}
}