<?php

require_once __DIR__."/Models/Portabilidade/Numeros.php";
require_once __DIR__."/Models/Portabilidade/Operadoras.php";
require_once __DIR__."/Models/Portabilidade/Prefixos.php";

class VerificadorPortabilidade {

	private $num;
	private $isPortado;

	function __construct($num = null){
		if($num !== null){
			$this->num = $num;
		}
	}

	public function setNumero(Numero $numero){
		$this->num = $numero;
	}

	public function isPortado(){
		return $this->isPortado;
	}

	public function getOperadora(){
		//$this->write_console(__FILE__,__LINE__, "Setando ddd ".$this->ddd);

		/*if(!$this->db){
		  	$this->write_console(__FILE__, __LINE__, "BANCO DE DADOS INVÁLIDO");
		  	return;
		}*/

		if($this->num->getTipo() !== 'movel'){		    	
		 	//$this->write_console(__FILE__, __LINE__, "PULANDO PESQUISA DE PORTABILIDADE PARA O TIPO ".$this->tipo);
		  	return;
		}

		if(($operadora = $this->getOperadoraPortada()) !== null){
			$this->isPortado = true;
			return $operadora;
		}

		$this->isPortado = false;

		$prefixo = Prefixos::where("prefixo", $this->num->getDDD().$this->num->getPrefixo())
							->leftjoin("operadoras", "operadoras.rn1", "=", "prefixos.rn1")
							->first();

		if($prefixo !== null){
			return $prefixo->nome_ope;
		}

		return null;

		/*$operadora = $this->db->execQuery("SELECT nome_ope ".
		    											"FROM prefixos ".
		      											"LEFT JOIN operadoras ON prefixos.rn1=operadoras.rn1 ".
		      											"WHERE prefixo = '".$this->ddd.$this->prefixo."'".
		      											" AND (".$this->mcdu." BETWEEN mcdu_inicial AND mcdu_final)");

		return $operadora;*/

		/*$this->write_console(__FILE__, __LINE__, "Número Portado : ".($this->isPortado?'SIM':'NAO'), $this->verbose);
		$this->write_console(__FILE__, __LINE__, "Operadora do número : ".$this->operadora, $this->verbose);*/
	}

	public function getOperadoraPortada(){
		$operadora = Numeros::where("numero", "'".$this->num->getNumeroComDDD()."'")
							  ->leftjoin("operadoras", "operadoras.rn1", "=", "numeros.operadora")
							  ->first();

		/*$operadora = $this->db->execQuery("SELECT nome_ope ".
		      										"FROM numeros, operadoras ".
		      										"WHERE numero = ".$this->ddd.$this->numero." ".
													"AND rn1=operadora");*/

		return $operadora;
	}

}