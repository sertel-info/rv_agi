<?php

require_once __DIR__."/UraManager.php";
require_once __DIR__."/../Models/Uras/Uras.php";
require_once __DIR__."/../Models/GruposAtendimento/GruposAtendimento.php";
require_once __DIR__."/../Models/Filas/Filas.php";
require_once __DIR__."/GruposManager.php";
require_once __DIR__."/Log/Logger.php";
require_once __DIR__."/FilasManager.php";

class AtendAutomaticoManager {

	private $linha;
	private $agi;

	function __construct($linha, $agi){
		$this->linha = $linha;
		$this->agi = AGI::getSingleton();
	}

	public function exec(){
		$this->agi->exec('Answer');

		if($this->linha->facilidades->atend_automatico_tipo == "ura"){
			$this->execUra();
		} else if($this->linha->facilidades->atend_automatico_tipo == "grupo"){
			$this->execGrupo();
		} else if($this->linha->facilidades->atend_automatico_tipo == "fila"){
			$this->execFila();
		}
		
	}

	public function execUra(){
		$ura = Uras::whereRaw("MD5(id) = '".$this->linha->facilidades->atend_automatico_destino."'")->first();
		
		Logger::write(__FILE__,__LINE__, "Executando ura ");

		if(!$ura){
			Logger::write(__FILE__,__LINE__, "Não foi possível encontrar a ura.".$this->linha->facilidades->atend_automatico_destino);
			exit;
		}

		$ura_manag = new UraManager( $ura , $this->agi );
		$ura_manag->exec();
	}

	public function execGrupo(){
		$grupo = GruposAtendimento::whereRaw("MD5(id) = '".$this->linha->facilidades->atend_automatico_destino."'")->first();

		Logger::write(__FILE__,__LINE__, "Executando grupo ");

		if(!$grupo){
			Logger::write(__FILE__,__LINE__, "Não foi possível encontrar o grupo.".$this->linha->facilidades->atend_automatico_destino);
			exit;
		}

		$grupo_manag = new GruposManager($grupo, $this->agi);
		$grupo_manag->exec();
	}

	public function execFila(){
		$fila = Filas::whereRaw("MD5(id) = '".$this->linha->facilidades->atend_automatico_destino."'")
						->first();
		
		if(!$fila){
			Logger::write(__FILE__, __LINE__, "Não foi possível encontrar a fila.".$this->linha->facilidades->atend_automatico_destino);
			exit;
		}

		$manager = new FilasManager();
		$manager->execFila($fila);
		
	}
}