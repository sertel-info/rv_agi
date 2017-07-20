<?php

class VoiceMail {

	private $numero;
	private $context = "rv_correio_voz";

	function __construct(Numero $num = null){
		
		if($num !== null)
			$this->numero = $num;

	}

	public function setNumero(Numero $num){
		$this->numero = $numero;
	}

	public function getNumero(){
		return $this->numero;
	}

	public function setContext($context){
		$this->context = $context;
	}

	public function getContext(){
		return $this->context;
	}

	public function toApplicationArg(){
		return $this->numero->getNumero()."@".$this->context;
	}

}