<?php

require_once __DIR__."/Log/Logger.php";
require_once __DIR__."/Agi.php";

class Dial{
	
	private $agi;
	private $tronco;
	private $tech_prefix = '';
	private $tempo_chamada = 30;
	private $tecnologia;
	private $opcoes = 'Ttg';
	private $ligacao;
	private $arquivos_troncos = __DIR__."/../rv_troncos.ini";
	private $last_status;
	private $tentativas;
	private $verbose = true;
	private $modo_prefixo = "ddd"; 
	//quais os prefixos de saida (ddd, operadora, cod_pais) Ex.: 55+operadora+ddd

	function __construct(Ligacao $ligacao){
		$this->ligacao = $ligacao;
		$this->agi = AGI::getSingleton();

		$linha = $ligacao->getLinha();

		if($linha !== null){
			$this->tecnologia = $linha->tecnologia;
		}
		
		Logger::write(__FILE__,__LINE__, "TECNOLOGIA DA LIGAÇÃO:".$this->tecnologia );
	}

	public function gerarPrefixos(){
		$prefixo = '';

		if($this->modo_prefixo !== null ){
			$prefixos_tronco = explode('+', $this->modo_prefixo);

			foreach($prefixos_tronco as $el){
				Logger::write(__FILE__,__LINE__, "PREFIXO TRONCO ".$el, $this->verbose);

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
		Logger::write(__FILE__,__LINE__, "GET DIAL ", $this->verbose);
		
		if(!isset($this->tecnologia)){
			$tecnologia = 'sip';
		} else {
			$tecnologia = strtolower($this->tecnologia);
		}
		
		Logger::write(__FILE__,__LINE__, "TECNOLOGIA ".$tecnologia, $this->verbose);

		$numero_formatado = $this->gerarPrefixos().$this->ligacao->getExtenObj()->getNumero();
		Logger::write(__FILE__,__LINE__, "NUMERO FORMATADO ".$this->ligacao->getExten(), $this->verbose);

		if($tecnologia == 'sip'){

			$tronco = $this->tronco ? "@".$this->tronco['nome'] : "";

			$dial = "sip/".
						$this->tech_prefix.
						$numero_formatado.
						$tronco.','.
						$this->tempo_chamada.
						($this->opcoes != "" ? ",".$this->opcoes : $this->opcoes);
		
		} else if($tecnologia == 'iax' || $tecnologia == 'iax2') {

			$tronco = $this->tronco ? $this->tronco['nome']."/" : "";

			$dial = "IAX2/".
					$tronco.
					$numero_formatado.",".
					$this->tempo_chamada.
					($this->opcoes != "" ? ",".$this->opcoes : $this->opcoes);					

		} 
		
		Logger::write(__FILE__,__LINE__, "DIAL:".$dial );
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
		$tipo_ligacao = $this->ligacao->getTipo();

		if($tipo_ligacao == 'sainte' 
			&& in_array($this->ligacao->getExtenObj()->getTipo(), ['fixo', 'movel', 'servico', 'ddi'])){

			$this->execSainte();			
		
		} else if($tipo_ligacao == 'entrante'){
			$this->execEntrante();
		
		} else {
			$this->execEntreRamais();
		} 
	}
	

	public function getRotas(){
		$rotas_linha = json_decode($this->ligacao->getLinha()->configuracoes->rotas_saida);

		Logger::write(__LINE__, __FILE__, "Rotas: ".var_export($rotas_linha, true) ,true);

		$troncos_arquivo = parse_ini_file($this->arquivos_troncos, true);
		
		if($troncos_arquivo == null || $troncos_arquivo == false){
		Logger::write(__LINE__, __FILE__, "FALHA AO LER O ARQUIVO DE TRONCOS NO CAMINHO: ".$this->arquivos_troncos,true);
		}
		

		$rotas = array();
		/*Logger::write(__LINE__, __FILE__, var_export( array_intersect_key($troncos_arquivo, array_flip($troncos_linha)),true))*/;

		array_walk($rotas_linha, function($rota, $key) use ($troncos_arquivo, &$rotas){

			if(isset($troncos_arquivo[$rota])){
				$rotas[$rota] = $troncos_arquivo[$rota];
			} else if($rota == 'EBS'){
				$rotas['EBS'] = true;
			}

		});

		return $rotas;
	}

	public function setTempoChamada($tempo = null){
		if($tempo == null){
			$this->tempo_chamada = isset($this->tronco['tempo_chamada']) ?
										 $this->tronco['tempo_chamada'] : 
										 $this->tempo_chamada;
		} else {
			$this->tempo_chamada = $tempo;
		}
	}

	public function setTechPrefix(){

		if(!isset($this->tronco['tech_prefix'])){
			//Logger::write(__FILE__,__LINE__, "!! ERRO AO LER TECH PREFIX DO TRONCO !!", $this->verbose);
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
		Logger::write(__FILE__,__LINE__, "exec servico", $this->verbose);
		$this->dial($this->getDialGsm());
	}

	public function setTecnologia($tecnologia){
		$this->tecnologia = $tecnologia;
	}

	public function execDDI(){
		Logger::write(__FILE__,__LINE__, "DISCANDO DDI", $this->verbose);
		$this->verificaTempoMax();
		$exten = $this->ligacao->getExtenObj()->getNumeroComDDD();
		$this->agi->exec("DIAL", "sip/323410#".$exten."@liguetelecom,30,".$this->opcoes);
	}

	public function execSainte(){
		$tipo = $this->ligacao->getExtenObj()->getTipo();

		if($this->ligacao->getExtenObj()->isDDI()){
		
			$this->execDDI();
		
		} else if($tipo == 'movel'){
			Logger::write(__FILE__,__LINE__, "exec movel", $this->verbose);
			$this->execFixo();
			return;
		} else if($tipo == 'fixo'){
			$this->execFixo();
			return;
		} else if($tipo == 'servico'){
			$this->execServico();
			return;
		}
	}

	public function execEntreRamais(){
		Logger::write(__FILE__,__LINE__, "Executando chamada entre ramais", $this->verbose);
	
		$this->tronco = null;

		$this->dial($this->getDialString());
	}

	public function execFixo(){

		$linha = $this->ligacao->getLinha();

		$rotas = $this->getRotas();
		Logger::write(__LINE__, __FILE__, "R ".var_export($rotas, true) ,true);

		$tentativas = 0;
		
		if(!count($rotas)){
			Logger::write(__FILE__,__LINE__, "Nenhum tronco apropriado encontrado", $this->verbose);
			die(1);
		}

		foreach($rotas as $nome_rota=>$rota){

			if($nome_rota == 'EBS' && $this->ligacao->getExtenObj()->getTipo() == "movel"){
				Logger::write(__FILE__,__LINE__, "exec ebs", $this->verbose);
				$this->dial($this->getDialGsm());
				continue;
			}

			if(strpos($rota['tipo'], $this->ligacao->getExtenObj()->getTipo()) == -1){
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

			Logger::write(__FILE__,__LINE__, "TENTATIVA : ".$tentativas, $this->verbose);
			Logger::write(__FILE__,__LINE__, "TRONCO : ".$nome_rota,   $this->verbose);
			//Logger::write(__FILE__,__LINE__, "DIAL STATUS: ".$dial_status, $this->verbose);
			Logger::write(__FILE__,__LINE__, "exec tronco", $this->verbose);
			$this->dial($this->getDialString());
			
			$tentativas++;

			if(!in_array($this->last_status, ["CHANUNAVAIL", "BUSY", "CONGESTION"])){
				break;
			}
		}
	}

	public function execEntrante(){
		Logger::write(__FILE__,__LINE__, "Executanto chamada entrante ", $this->verbose);
		$this->tronco = null;

		$this->dial($this->getDialString());
	}

	public function getLastStatus(){
		return $this->last_status;
	}

	public function setVerbose($verb){
		$this->verbose = $verb;
	}

	public function getLigacao(){
		return $this->ligacao;
	}

	public function setLigacao($ligacao){
		$this->ligacao = $ligacao;
	}

	public function execRaw($expression){
		$this->agi->exec('DIAL', $expression);
	}
}

