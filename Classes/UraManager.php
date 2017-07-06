<?php

require_once __DIR__."/../Models/Linhas/Linhas.php";
require_once __DIR__."/../Models/Filas/Filas.php";
require_once __DIR__."/../Models/GruposAtendimento/GruposAtendimento.php";
require_once __DIR__."/Ligacao.php";
require_once __DIR__."/Numero.php";
require_once __DIR__."/GruposManager.php";

class UraManager{
	private $ura;
	private $dial_class;
	private $agi;
	private $tentativas;
	private $max_tentativas = 3;

	private $trans_ascii_digits = [
		'42'=>'asteristico',
		'35'=>'tralha',
		'48'=>'0',
		'49'=>'1',
		'50'=>'2',
		'51'=>'3',
		'52'=>'4',
		'53'=>'5',
		'54'=>'6',
		'55'=>'7',
		'56'=>'8',
		'57'=>'9'
	];

	function __construct($ura, AGI $agi){
		$this->ura = $ura;
		//$this->dial_class = $dial_class;
		$this->agi = $agi;
	}

	public function exec(){
	
		//$audio_app = $this->getAudioApp();
		$this->agi->write_console(__FILE__,__LINE__, "TIPO AUDIO ".$this->ura->tipo_audio);

		if($this->ura->tipo_audio == "obrigatorio"){
			$ascii_digito = $this->execPlayback();
		} else {
			$ascii_digito = $this->execBackground();
		}

		if($ascii_digito > 0){
			$digito = $this->trans_ascii_digits[$ascii_digito];
			$this->agi->write_console(__FILE__,__LINE__, "DIGITO ".$digito);

			$this->execOption($digito);

		}
	}


	public function execOption($digito){

		$tipo = $this->ura->__get("dig_".$digito."_tipo");
		$destino = $this->ura->__get("dig_".$digito."_destino");
		
		$this->tentativas++;

		if($this->tentativas >= $this->max_tentativas){
			$this->agi->hangup();
			return;
		}

		if($tipo == null || $destino  == null){
			return $this->exec();
		}

		switch($tipo){
			case 'ramal':
				$this->callRamal($destino);
			break;
			case 'grupo':
				$this->callGrupo($destino);			
			break;
			case 'fila':
				$this->callFila($destino);			
			break;
		}
	}


	public function callRamal($destino_md5_id){
		$linha = Linhas::whereRaw(" MD5(id) = '".$destino_md5_id."'")->first();

		$ligacao = new Ligacao();
		$ligacao->setLinha($linha);
		$ligacao->setTipo("interna");
		$ligacao->setExten( new Numero("0".$linha->autenticacao->login_ata) );
		$ligacao->setAgi($this->agi);

		$dial = new Dial($ligacao);
		$dial->exec();
	}


	public function callGrupo($destino_md5_id){
		$this->agi->write_console(__FILE__,__LINE__, "Destino: ".$destino_md5_id);

		$grupo_atend_model = GruposAtendimento::whereRaw(" MD5(id) = '".$destino_md5_id."'")->first();
		$this->agi->write_console(__FILE__,__LINE__, "grupo_model: ".$grupo_atend_model->id);

		$ligacao = new Ligacao();
		//$ligacao->setLinha();
		$ligacao->setTipo("interna");
		//$ligacao->setExten( new Numero("0".$linha->autenticacao->login_ata) );
		$ligacao->setAgi($this->agi);
		$dial = new Dial($ligacao);

		//$grupo_atend_model = $linha->grupos()->orderBy('posicao' , 'asc')->first();

		if($grupo_atend_model){
			$linhas_grupo = $grupo_atend_model->linhas()
											    ->with("autenticacao")
							                    ->withPivot('posicao')
							                    ->orderBy('pivot_posicao')
							                    ->get();
			
			$this->agi->write_console(__FILE__,__LINE__, "Chamando grupo: ".$grupo_atend_model->id);
			$this->agi->write_console(__FILE__,__LINE__, "Tipo grupo: ".$grupo_atend_model->tipo);

			$grupo = new GruposManager($grupo_atend_model, $this->agi);
			$grupo->exec();
		}
	}


	public function callFila($destino_md5_id){

		$fila = Filas::whereRaw("MD5(id) = '".$destino_md5_id."'")->first();
		$this->agi->write_console(__FILE__,__LINE__, "LIGANDO PARA FILA ".$fila->nome);

		$this->agi->exec("Queue", $fila->nome);
	}


	public function execPlayback(){
		$this->agi->exec("Playback", "rv_audios/".$this->ura->audio->nome);
		$digito = $this->agi->wait_for_digit(10000);

		return intval($digito['result']);
	}
	

	public function execBackground(){
		$digito = $this->agi->exec("Background", "rv_audios/".$this->ura->audio->nome);

		return intval($digito['result']);
	}

}