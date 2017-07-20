<?php

require_once __DIR__."/Agi.php";
require_once __DIR__."/VoiceMail.php";

class VoiceMailManager {

	function __construct(){
		$this->agi = AGI::getSingleton();
	}

	public function execVoiceMail(VoiceMail $vm){
		$this->agi->exec('VoiceMail', $vm->toApplicationArg());
	}

	public function createVoiceMail(Numero $numero){
		return new VoiceMail($numero);
	}
}