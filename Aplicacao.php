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
		$this->agi->write_console(__FILE__, __LINE__, "THIS LINHA ID : ".$linha->id);
		$this->config = $config;
	}

	public function exec(){
		$app = $this->ligacao->getExten();

		if(substr($app, 0, 1) == "*"){
			$app = substr($app, 1);
			$app_arg = null;

			if(($arg_pos = strpos($app, "*")) !== FALSE){
				list($app, $app_arg) = explode("*", $app);
			}

		}

		$this->agi->write_console(__FILE__, __LINE__, "atalho_sigam_me: ".$this->config->atalho_siga_me);
		$this->agi->write_console(__FILE__, __LINE__, "atalho_cadeado: ".$this->config->atalho_cadeado);
		$this->agi->write_console(__FILE__, __LINE__, "app ".$app);
		$this->agi->write_console(__FILE__, __LINE__, "argumeto app ".$app_arg);

		if($app == $this->config->atalho_siga_me){
			$this->sigaMe();
		}

		if($app == $this->config->atalho_cadeado){
			$this->cadeado();
		}

		if($app == $this->config->atalho_monitoramento && $app_arg !== null){
			$linha_alvo = $this->linha->assinante
									  ->linhas()
									  ->select('linhas.id', 'dados_autenticacao_linhas.login_ata', 'dados_facilidades_linhas.monitoravel', 'dados_facilidades_linhas.pode_monitorar')
									  ->leftjoin('dados_autenticacao_linhas', 'dados_autenticacao_linhas.linha_id', '=', 'linhas.id')
									  ->leftjoin('dados_facilidades_linhas', 'dados_facilidades_linhas.linha_id', '=', 'linhas.id')
									  ->where('login_ata', $app_arg)
									  ->first();							  
								
			$isDono = ($linha_alvo !== null);
			$podeMonitorar = ($this->linha->facilidades()->first()->pode_monitorar == 1);
			$alvoPodeSerMonitorado = $linha_alvo->facilidades->monitoravel == 1;
			
			if($isDono && $podeMonitorar && $alvoPodeSerMonitorado){
				$this->agi->exec("ChanSpy", "SIP/".$linha_alvo->autenticacao->login_ata);
			} else {
				$this->agi->write_console(__FILE__, __LINE__, "Pode monitorar ? ".(+$podeMonitorar));
				$this->agi->write_console(__FILE__, __LINE__, "Alvo pode ser monitorado ? ".(+$alvoPodeSerMonitorado));
				$this->agi->write_console(__FILE__, __LINE__, "É dono ? ".(+$isDono));
			}
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