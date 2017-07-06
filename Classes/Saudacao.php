<?php

class Saudacao {

	private $saudacao;
	private $audio;
	private $ago;

	function __construct($saudacao, $audio, $agi){
		$this->saudacao = $saudacao;
		$this->audio = $audio;
		$this->agi = $agi;
	}

	public function exec(){
		$this->agi->exec("ANSWER");

		if( $this->saudacao->tipo_audio == 'obrigatorio' ){
			
			$this->agi->exec("Playback", 'rv_audios/'.$this->audio->nome);

		} else if ( $this->saudacao->tipo_audio == 'opcional' ){
			
			$this->agi->exec("Background", 'rv_audios/'.$this->audio->nome);

		}

	}
}