<?php

require_once __DIR__."/Agi.php";
require_once __DIR__."/Log/Logger.php";
require_once __DIR__."/Numero.php";
require_once __DIR__."/VoiceMailManager.php";

class FilasManager {

	function __construct(Fila $fila = null){			
		$this->agi = AGI::getSingleton();
	}

	public function execFila($fila){
		Logger::write(__FILE__, __LINE__, "Executando fila");
		
		$this->agi->exec("Queue", $fila->nome.'-'.$fila->id.",,,,".$fila->tempo_chamada);
		$status = $this->agi->get_variable("QUEUESTATUS")["data"];

		Logger::write(__FILE__, __LINE__,
								  "Status da fila ".$this->agi->get_variable("QUEUESTATUS")['data']);

		if(in_array($status, ["JOINEMPTY", "LEAVEEMPTY", "TIMEOUT"])){
			Logger::write(__FILE__,__LINE__, "Transbordando fila para Voice Mail");
			$exten = new Numero($this->agi->get_variable("EXTEN")['data']);
			$manager = new VoiceMailManager();
			$vm = $manager->createVoiceMail($exten);
			$manager->execVoiceMail($vm);
		}
	}


}