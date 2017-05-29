<?php
require_once(__DIR__."/WriteConsoleTrait.php");

class Numero {
	
	use WriteConsoleTrait;

	private $numero_completo;
	private $numero;
	//private $codArea = 21;
	private $operadora_discagem; //operadora para discagem Ex.: 21, 31;
	private $operadora; //operadora do número Ex.: Claro, Vivo, Tim;
	private $isPortado;
	private $isDDD = false;
	private $isDDI;
	private $db;
	private $tipo; //fixo ou móvel
	private $mcdu;
	private $prefixo;
	private $verbose = true;
	private $ddd;
	private $num_operadoras = [
			"vivo"=>21,
			"claro"=>15,
			"oi"=>31,
			"tim"=>41,
			"nextel"=>99,
	];

	public function __construct($numero){
		$this->numero_completo = $numero;
		$this->setParts();
	}

	public function setParts(){
		$numero_destino = $this->numero_completo;
		$codArea = $this->ddd;

		//$tipo = 'interno';
		$digito=substr($numero_destino, -9, 1); /** 02121 964221082 -- NUMERO COM OPERADORA, ZERO E DDD **/
		preg_match("/^(00|0|9090|90)?(([0-9]{2})?([0-9]{2}))?(([0-9])[0-9]{7,})|([0-9]{3,5})/m", $numero_destino, $mts);

		/*$this->write_console(__FILE__, __LINE__,'OPERADORA E DDD --> ' .$mts[2], $this->verbose);
		$this->write_console(__FILE__, __LINE__,'OPERADORA ->> ' .$mts[3], $this->verbose);
		$this->write_console(__FILE__, __LINE__,'DDD -->' .$mts[4], $this->verbose);
		$this->write_console(__FILE__, __LINE__,'NUMERO DE DESTINO --> ' .$mts[5], $this->verbose);
		$this->write_console(__FILE__, __LINE__,'DIGITO IDENTIFICADOR --> ' .$mts[6], $this->verbose);*/

		/*nonoDigito = array('11','12','13','14','15','16','17','18','19','21','22','24','27','28','31','32','33','34','35','37','38','61','62','64','65','63','66','67','68','69','71','72','73','74','75','77','79','91','92','93','94','95','96','97','98','99');*/

		$nextel = array('77','78');

		if(strlen($numero_destino) < 7){
			
			if(substr($numero_destino, 0, 1) == 0){
				$this->tipo = 'interno';
				$this->numero = substr($mts[0], 1);

			} else {
				$this->tipo = 'servico';
				$this->operadora = 'nextel';
				$this->numero = $mts[0];
			}

			return;

		} else if(strlen($mts[5])==8 && in_array(substr($mts[5],0,2),$nextel)){
							
			if($codArea!=$mts[4]){
				$numero = $codArea.$mts[5];
				
			} else {					
				$numero = $mts[4].$mts[5];
			}

			$tipo = 'nextel';	
		/*in_array($mts[4],$nonoDigito) && */			
		} elseif(!in_array(substr($mts[5],0,2),$nextel) && preg_match("/^[6-9]/", $mts[6],$m)){
								
			if(strlen($mts[5])==8){
				$numero = $mts[4].'9'.$mts[5];
			}else{
				$numero = $mts[4].$mts[5];
			}

			$tipo = 'movel';
		/*in_array($mts[4],$nonoDigito) && */
		} elseif(!in_array(substr($mts[5],0,2),$nextel) && preg_match("/^[2-5]/", $mts[6],$m)){
							
			$numero = $mts[4].$mts[5];
			$tipo = 'fixo';
		
		/* && !in_array($mts[4],$nonoDigito)*/
		} elseif($codArea != $mts[4] && !in_array(substr($mts[5],0,2),$nextel) && preg_match("/^[6-9]/", $mts[6],$m)){
							
			if(strlen($mts[5])==8){
				$numero = $codArea.'9'.$mts[5];
			}else{
				$numero = $codArea.$mts[5];						
			}

			$tipo = 'movel';

		/* && !in_array($mts[4],$nonoDigito) */	
		} elseif($codArea!=$mts[4] && !in_array(substr($mts[5],0,2),$nextel) && preg_match("/^[2-5]/", $mts[6],$m)){
							
			$numero = $codArea.$mts[5];
			$tipo = 'fixo';
							
		}
		
		$this->tipo = $tipo;

		/*if($tipo == 'interno'){
			$this->numero = $this->numero_completo;
			return;
		}*/
		
		$this->numero = $mts[5];
		$this->operadora_discagem = $mts[3];

		if(!empty($mts[4])){
			$this->ddd = $mts[4];
			$this->isDDD = true;
		}

		if(strlen($numero) >= 8){
			$this->mcdu = substr($this->numero, -4);
			$this->prefixo = substr($this->numero, 0, -4);
		}

		/*
		$nume'] = $numero;
		$numero_formatado['ddd'] = $mts[4];
		$numero_formatado['operadora_ddd'] = $mts[2];
		$numero_formatado['operadora'] = $mts[3];
		$numero_formatado['partes'] = $mts;

		return $numero_formatado;
		*/
	}


	public function setMcdu(){
		if($this->numero !== null){
			$this->mcdu = substr($this->numero, -4);
		}
	}

	public function setPrefixo(){
		if($this->numero !== null){
			$this->prefixo = substr($this->numero, 0, -4);
		}
	}

	public function getPrefixo(){
		return $this->prefixo;
	}

	public function isDDD(){
		return $this->isDDD;
	}

	public function getTipo(){
		return $this->tipo;
	}

	public function setTipo($tipo){
		$this->tipo = $tipo;
	}

	public function setDb($db){
		$this->db = $db;
	}

	public function getNumeroCompleto(){
		return $this->numero_completo;
	}

	public function setIsPortado($isPortado){
		return $this->isPortado = $isPortado;
	}

	public function getNumero(){
		return $this->numero;
	}

	public function getDDD(){
		return $this->ddd;
	}

	public function setDDD($ddd){
		$this->ddd = $ddd;
	}

	public function setVerbose($verb){
		$this->verbose = $verb;
	}

	public function getOperadora(){
		return $this->operadora;
	}

	public function setOperadora($operadora){
		$this->operadora = $operadora;
	}

	public function getOperadoraNum(){
		return $this->operadora_num;
	}

	public function getNumeroComDDD(){
		return $this->ddd.$this->numero;
	}

	public function forceOperadora(){
		if(!$this->isDDD()){
			return;
		}

		if(isset($this->num_operadoras[strtolower($this->operadora)])){
			$this->operadora_num = $this->num_operadoras[strtolower($this->operadora)];
		} else {
			$this->operadora_num = $this->num_operadoras['nextel'];
		}		
	}
}