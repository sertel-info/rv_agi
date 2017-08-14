<?php

class AGINotificationMessageCompiler {

	private $assinante;
	private $valid_variables = array("nome_usuario", "creditos_atuais");

	public function compile($msg, $assinante){
		$this->assinante = $assinante;

		$compiled_msg = preg_replace_callback("/{([a-zA-Z_]+)}/", function($matches){
			
			if(in_array($matches[1], $this->valid_variables)){
				$camel_case_variable = implode("", array_map(function($word){ return ucfirst($word); },
															 explode("_", $matches[1])));
				return $this->{"get".$camel_case_variable}();
			}

		}, $msg);

		return $compiled_msg;
	}

	public function getNomeUsuario(){
		if($this->assinante == null){
			return null;
		}

		if($this->assinante->tipo == 0){
			return $this->assinante->nome_fantasia;
		}

		return $this->assinante->nome;
	}

	public function getCreditosAtuais(){
		if($this->assinante == null){
			return null;
		}

		return $this->assinante->financeiro->creditos;
	}
}