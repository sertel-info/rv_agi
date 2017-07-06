<?php

class Aplicacao {
	private $linha;
	private $ligacao;
	//private $db;
	private $agi;

	public function __construct(AGI $agi, Ligacao $ligacao, Linhas $linha, Configuracoes $config){
		$this->agi = $agi;
		//$this->db = $db;
		$this->ligacao = $ligacao;
		$this->linha = $linha;
		$this->config = $config;
	}

	public function exec(){
		$app = $this->ligacao->getExten();

		if(substr($app, 0, 1) == "*"){
			$app = substr($app , 1);
		}

		$this->agi->write_console(__FILE__, __LINE__, "atalho_sigam_me: ".$this->config->atalho_siga_me);
		$this->agi->write_console(__FILE__, __LINE__, "atalho_cadeado: ".$this->config->atalho_cadeado);
		$this->agi->write_console(__FILE__, __LINE__, "app ".$app);

		if($app == $this->config->atalho_siga_me){
			$this->sigaMe();
		}

		if($app == $this->config->atalho_cadeado){
			$this->cadeado();
		}
	}

	/**
	* Se o cadeado da linha estiver ativo, desativa-o,
	* se ele estiver desativado, ativa-o
	*/
	public function cadeado(){
		
		$linha = $this->linha;
		$novo_estado = !(boolean)$linha->facilidades->cadeado_pessoal;

		$this->agi->write_console(__FILE__,__LINE__, "novo_estado: ".$novo_estado );

		$linha->facilidades->update([
				"cadeado_pessoal"=>+$novo_estado,
			]);
		
		$audio = $novo_estado ? 'cadeado_on' : 'cadeado_off';

		$this->agi->exec('playback', $audio);
	}

	/**
	* Se o SigaMe estiver ativo, desativa-o
	* se estiver desativado, ativa-o
	*/
	public function sigaMe(){
		$linha = $this->linha;
		$this->agi->answer();

	    /* Ativa o siga_me e pede o número */ 
	    if($linha->facilidades->siga_me == 0){
	    	$telefone = $this->agi->get_data('digite_telefone', 4*1000, 5);

	    	$this->agi->write_console(__FILE__,__LINE__, "Ativando siga-me para o telefone: ".$telefone['result']);

	    	$linha->facilidades->update([
	    			"siga_me" => 1,
	    			"num_siga_me" => $telefone['result']
	    	]);

	        /* Desativa o siga_me */
	    } else {
	       	$this->agi->write_console(__FILE__,__LINE__, "Desativando siga-me");

	       	$this->agi->stream_file("siga_me_desativado");

	       	$linha->facilidades->update([
	    		"siga_me" => 0,
	    		"num_siga_me" => ''
	     	]);

	    }
	}
}