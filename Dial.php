<?php

class Dial{
	
	private $agi;
	private $tronco;
	private $tech_prefix = '';
	private $tempo_chamada = 60;
	private $tecnologia;
	private $opcoes = 'Tt';
	private $ligacao;
	private $arquivos_troncos = "rv_troncos.ini";
	private $last_status;
	private $tentativas;
	private $verbose = true;
	private $direcao; //sainte, entrante ou interna;
	private $modo_prefixo = "ddd"; 
	//quais os prefixos de saida (ddd, operadora, cod_pais) Ex.: 55+operadora+ddd

	function __construct(Ligacao $ligacao){
		$this->ligacao = $ligacao;
		$this->agi = $ligacao->getAgi();

		$this->tecnologia = $ligacao->getLinha()->tecnologia;
		$this->agi->write_console(__FILE__,__LINE__, "TECNOLOGIA DA LIGAÇÃO:".$this->tecnologia );
	}

	public function gerarPrefixos(){
		$prefixo = '';

		if($this->modo_prefixo !== null ){
			$prefixos_tronco = explode('+', $this->modo_prefixo);

			foreach($prefixos_tronco as $el){
				$this->agi->write_console(__FILE__,__LINE__, "PREFIXO TRONCO ".$el, $this->verbose);

				if($el == 'operadora')
					$prefixo .= $this->ligacao->getExtenObj()->getOperadora();

				if($el == 'cod_pais')
					$prefixo .= "55";

				if($el == 'ddd')
					$prefixo .= $this->ligacao->getExtenObj()->getDDD();

				if(is_numeric($el))
					$prefixo .= $el;
			}
		}

		return $prefixo;
	}

	public function getDialString(){
		$this->agi->write_console(__FILE__,__LINE__, "GET DIAL ", $this->verbose);
		
		$tecnologia = strtolower($this->tecnologia);

		$numero_formatado = $this->gerarPrefixos().$this->ligacao->getExtenObj()->getNumero();

		if($tecnologia == 'sip'){

			$tronco = $this->tronco ? "@".$this->tronco['nome'] : "";

			$dial = $this->tecnologia."/".
								$this->tech_prefix.
								$numero_formatado.
								$tronco.','.
								$this->tempo_chamada.
								($this->opcoes != "" ? ",".$this->opcoes : $this->opcoes);
		
		} else if($tecnologia == 'iax' ||
				  $tecnologia == 'iax2'){

			$tronco = $this->tronco ? $this->tronco['nome']."/" : "";

			$dial = "IAX2/".
					$tronco.
					$numero_formatado.",".
					$this->tempo_chamada.
					($this->opcoes != "" ? ",".$this->opcoes : $this->opcoes);					

		} 
		
		$this->agi->write_console(__FILE__,__LINE__, "DIAL:".$dial );
		return $dial;
	}

	public function dial($dial){

		$this->agi->exec('DIAL', $dial);

		$this->last_status = $this->agi->get_variable("DIALSTATUS")['data'];
	}

	public function setLine($line){
		$this->line = $line;
	}

	/*public function getLine(){
		return $this->line;
	}*/

	public function verificaTempoMax(){
		if($this->ligacao->getLimiteTempo() !== null){

			$this->opcoes .= "L(".(($this->ligacao->getLimiteTempo())*1000).")";
		}
	}


	public function exec(){
		$this->verificaTempoMax();

		$this->agi->write_console(__FILE__,__LINE__, "TIPO LIGAÇÃO: ".$this->ligacao->getTipo());
		if(in_array($this->ligacao->getTipo(), ['fixo', 'movel', 'nextel', 'servico'])){
			$this->direcao = 'sainte';
			$this->execSainte();			
		} else {
			$this->direcao = 'entre_ramais';
			$this->execEntreRamais();
		}
	}
	

	public function getRotas(){
		$rotas_linha = json_decode($this->ligacao->getLinha()->configuracoes->rotas_saida);
		$this->agi->write_console(__LINE__, __FILE__, var_export($rotas_linha) ,true);

		$troncos_arquivo = parse_ini_file($this->arquivos_troncos, true);
		
		$rotas = array();
		/*$this->agi->write_console(__LINE__, __FILE__, var_export( array_intersect_key($troncos_arquivo, array_flip($troncos_linha)),true))*/;

		array_walk($rotas_linha, function($rota, $key) use ($troncos_arquivo, &$rotas){

			if(isset($troncos_arquivo[$rota])){
				$rotas[$rota] = $troncos_arquivo[$rota];
			} else if($rota == 'EBS'){
				$rotas['EBS'] = true;
			}

		});

		return $rotas;
	}

	public function setTempoChamada(){
		$this->tempo_chamada = isset($this->tronco['tempo_chamada']) ?
										 $this->tronco['tempo_chamada'] : 
										 $this->tempo_chamada;
	}

	public function setTechPrefix(){

		if(!isset($this->tronco['tech_prefix'])){
			//$this->agi->write_console(__FILE__,__LINE__, "!! ERRO AO LER TECH PREFIX DO TRONCO !!", $this->verbose);
			$this->tech_prefix = '';
			return;
		}

		$this->tech_prefix = (isset($this->tronco['cli']) && $this->tronco['cli'] == 'sim'?
								  $this->tronco['tech_prefix']."#" :
								  "");
	}

	public function getDialGsm(){
		$numero = $this->ligacao->getExtenObj();
		$numero->forceOperadora();

		if($numero->isDDD() && empty($numero->operadora)){
			$numero->setOperadora("nextel");
		}

		if($numero->getDDD() == $this->ligacao->getLinha()->ddd_local){
			$numero_formatado = $numero->getNumero();
		} else {

			$numero_formatado = $numero->getOperadoraNum().$numero->getDDD().$numero->getNumero();
			if($numero->isDDD())
				$numero_formatado = '0'.$numero_formatado;
		}

		$dial_string = "KHOMP/*g".strtoupper($numero->getOperadora()).
							   "/".$numero_formatado.
							   ",".$this->tempo_chamada.
							   ','.$this->opcoes;

		return $dial_string;
	}

	public function execServico(){
		$this->agi->write_console(__FILE__,__LINE__, "exec servico", $this->verbose);
		$this->dial($this->getDialGsm());
	}

	public function setTecnologia($tecnologia){
		$this->tecnologia = $tecnologia;
	}


	public function execSainte(){
		$tipo = $this->ligacao->getTipo();

		if($tipo == 'movel'){
			$this->agi->write_console(__FILE__,__LINE__, "exec movel", $this->verbose);
			$this->execFixo();
			return;
		} else if($tipo == 'fixo'){
			$this->execFixo();
		} else if($tipo == 'servico'){
			$this->execServico();
		}
	}

	public function execEntreRamais(){
		$this->tronco = null;
		$this->agi->write_console(__FILE__,__LINE__, "entre ramais", $this->verbose);

		$this->dial($this->getDialString());
	}

	public function execFixo(){
		$linha = $this->ligacao->getLinha();

		$rotas = $this->getRotas();

		$tentativas = 0;
	
		if(!count($rotas)){
			$this->agi->write_console(__FILE__,__LINE__, "Nenhum tronco apropriado encontrado", $this->verbose);
			die(1);
		}

		foreach($rotas as $nome_rota=>$rota){

			if($nome_rota == 'EBS' && $this->ligacao->getTipo() == 'movel'){
				$this->agi->write_console(__FILE__,__LINE__, "exec ebs", $this->verbose);
				$this->dial($this->getDialGsm());
				continue;
			}

			$this->tronco = $rota;
			$this->tronco['nome'] = $nome_rota;
			
			if(isset($this->tronco['prefixo']))
				$this->modo_prefixo = $this->tronco['prefixo'];
			
			if(isset($this->tronco['tecnologia'])){
				$this->setTecnologia($this->tronco['tecnologia']);
			}

			$this->setTempoChamada();
			$this->setTechPrefix();
			
			if($this->ligacao->getLinha()->configuracoes->ring_falso 
						&& strpos($this->opcoes, 'r') === FALSE){
				$this->opcoes .= "r";	
			}

			$this->agi->write_console(__FILE__,__LINE__, "TENTATIVA : ".$tentativas, $this->verbose);
			$this->agi->write_console(__FILE__,__LINE__, "TRONCO : ".$nome_rota,   $this->verbose);
			//$this->agi->write_console(__FILE__,__LINE__, "DIAL STATUS: ".$dial_status, $this->verbose);
			$this->agi->write_console(__FILE__,__LINE__, "exec tronco", $this->verbose);
			$this->dial($this->getDialString());
			
			$tentativas++;

			if(!in_array($this->last_status, ["CHANUNAVAIL", "BUSY", "CONGESTION"])){
				break;
			}
		}
	}

	public function getLastStatus(){
		return $this->last_status;
	}

	public function setVerbose($verb){
		$this->verbose = $verb;
	}
}


/**

public function getTroncosDoArquivo(){
		$this->agi->write_console(__FILE__, __LINE__, "GETTING TRONCOS ", $this->verbose);
		$tipo = $this->ligacao->getTipo();
		$linha = $this->ligacao->getLinha();

		$troncos = parse_ini_file($this->arquivos_troncos, true);

		if(!$troncos){
			$this->agi->write_console(__FILE__, __LINE__, "FALHA AO LER ARQUIVO DE CONFIGURAÇÃO DE TRONCOS ", $this->verbose);
			return [];
		}

		if(!strlen($tipo)){
			$this->agi->write_console(__FILE__, __LINE__, "LIGACAO SEM TIPO SETADO ", $this->verbose);
			return [];
		}

		$this->agi->write_console(__FILE__, __LINE__, "ROTA COM CLI: ".$linha->cli , $this->verbose);

		$troncos = array_filter($troncos, function($el) use ($tipo, $linha) {	
			$tronco_cli = isset($el['cli']) && $el['cli'] == 'sim' ? 1 : 0;
			$tipos_tronco = explode(',', $el['tipo']);

			return (in_array($tipo, $tipos_tronco) || !isset($el['tipo']) || !strlen($el['tipo']))
					 && ($tronco_cli == $linha->cli);
		});
		
		$this->agi->write_console(__FILE__, __LINE__, "QTD TRONCOS: ".count($troncos), $this->verbose);

		return $troncos;
	}

*/