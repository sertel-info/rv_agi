<?php
require_once(__DIR__."/WriteConsoleTrait.php");

class Ligacao {
	use WriteConsoleTrait;

	private $exten;
	private $callerid;
	private $agi;
	private $date;
	private $tipo;
	private $linha;
	private $verbose = true;
	private $duracao;
	private $unique_id;
	private $dir_rec;
	private $tipo_rec;
	private $limite_tempo;
	private $tarifa = 0;
	
	function __construct(){
		$this->date = date('H-i-s-d-m-Y');
	}
	

	public function iniciaGravacao(){
		$arquivo = $this->gerarNomeArquivoGravacao();

		$this->agi->exec("MixMonitor",
						  $arquivo.',ab'.
						  ',"php /var/lib/asterisk/agi-bin/ramal_virtual/rv_guarda_gravacao.php -g '.$arquivo.' > /tmp/guardar_gravacao.txt"');

	}

	/*public function setGravacao(Gravacoes $gravacao){
		$gravacao->callerid = $this->callerid->getNumero();
		$gravacao->exten = $this->exten->getNumero();
		$gravacao->unique_id = $this->unique_id;
		$gravacao->data = $this->data;
		$gravacao->assinante = $this->linha->assinante_id;
		$gravacao->linha = $this->linha->id;

		$this->gravacao = $gravacao;		
	}*/
	
	public function setTarifa($tarifa){
		$this->tarifa = $tarifa;
	}


	public function getTarifa(){
		return $this->tarifa;
	}


	public function setDirRec($dir){
		$this->dir_rec = $dir;
	}


	public function setLimiteTempo($tempo){
		$this->limite_tempo = $tempo;
	}


	public function setUniqueId($id){
		$this->unique_id = $id;
	}


	public function setTipoRec($tipo){
		$this->tipo_rec = $tipo;
	}


	public function getLimiteTempo(){
		return $this->limite_tempo;
	}


	public function gerarNomeArquivoGravacao(){
		$dir_rec = $this->dir_rec;
		
		if(!in_array(substr($dir_rec, -1, 1), ['/', '\\'])){
			$dir_rec = $dir_rec.'/';
		}

		$pasta = $this->tipo_rec;
		$exten = $this->exten->getNumeroComDDD();
		$callerid = $this->callerid->getNumeroCompleto();
		$unique_id = $this->unique_id;
		$tipo_rec = $this->tipo_rec;
		$data = DateTime::createFromFormat('H-i-s-d-m-Y', $this->date)->format('H_i_s_d_m_Y');
	
		$this->arquivo = "$dir_rec".$this->linha->assinante_id."/".
													   $this->linha->id."/".
													   "$tipo_rec/$tipo_rec-$callerid-$exten-$unique_id-$data.wav";

		return $this->arquivo;
	}


	public function verificaGravacao(){
		return $this->linha->facilidades->gravacao;
	}


	public function verificaCadeado(){
		$facilidades_linha = $this->linha->facilidades;

		if($facilidades_linha->cadeado_pessoal == 1 && strlen($facilidades_linha->cadeado_pin)){
			
			$this->agi->answer();
			$this->agi->exec("playback", "cadeado");
			$this->agi->write_console(__FILE__,__LINE__, "CADEADO ATIVO", $this->verbose);
			$this->agi->hangup();

		}
	}


	public function verificaSigaMe(){
		$facilidades_linha = $this->linha->facilidades;

		if($facilidades_linha->siga_me == 1 && strlen($facilidades_linha->num_siga_me)){
			$this->write_console(__FILE__,__LINE__,  "SIGA ME ATIVO", $this->verbose);
			$this->write_console(__FILE__,__LINE__,  "NUM SIGA ME: ".$linha['num_siga_me'],$this->verbose);
			
			$this->setExten($linha['num_siga_me']);
		}
	}

	
	public function execVoiceMail(){
		$facilidades_linha = $this->linha->facilidades;

		if($facilidades_linha->caixa_postal == 1){
			$this->write_console(__FILE__,__LINE__, 'VOICE MAIL ATIVADO' , $this->verbose);	

			$email_data = array("data"=>array('receptor'=>$facilidades_linha->cx_postal_email),
						        "exten"=>$this->getExten(),
						        "status"=>'SUCCESS',
						        "assinante"=>$this->linha->assinante_id);

			$this->agi->set_variable('rv_voice_mail_data', json_encode($email_data));

			$this->agi->exec('VoiceMail', $this->getExten()."@"."rv_correio_voz");
			$status = $this->agi->get_variable('VMSTATUS')['data'];

			$file = '';
			
			if($status == 'SUCCESS'){
				return 1;
			}

			return 0;
		} else {
			$this->write_console(__FILE__,__LINE__,'VOICE MAIL DESATIVADO',$this->verbose);	
		}
	}


	public function setExten(Numero $exten){
		$this->exten = $exten;
	}


	public function setAgi(AGI $agi){
		$this->agi = $agi;
	}


	public function setCallerId(Numero $callerid){
		$this->callerid = $callerid;
	}


	public function getAgi(){
		return $this->agi;
	}


	public function getTipo(){
		return $this->exten->getTipo();
	}


	public function getExten(){
		return $this->exten->getNumeroCompleto();
	}


	public function getExtenObj(){
		return $this->exten;
	}


	public function getCallerId(){
		return $this->callerid->getNumeroCompleto();
	}


	public function getCallerIdObj(){
		return $this->callerid;
	}


	public function setLinha($line){
		$this->linha = $line;
	}


	public function getLinha(){
		return $this->linha;
	}


	public function getDate(){
		return $this->date;
	}


	public function setDate($date){
		return $this->date = $date;
	}


	public function getDuracao(){
		return $this->duracao;
	}


	public function setDuracao($duracao){
		$this->duracao = $duracao;
	}


	public function setVerbose($verb){
		$this->verbose = $verb;
	}
}
