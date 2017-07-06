<?php

require_once __DIR__."/Numero.php";
require_once __DIR__."/Dial.php";
require_once __DIR__."/Ligacao.php";

class GruposManager {

	private $tipo;
	private $dial;
	private $agi;
	private $grupo;

	function __construct($grupoModel, $agi){
		$this->grupo = $grupoModel;
		
		$this->linhas = $grupoModel->linhas()
								      ->with("autenticacao")
				                      ->withPivot('posicao')
				                      ->orderBy('pivot_posicao')
				                      ->get();

		$this->agi = $agi;
		$this->agi->write_console(__FILE__,__LINE__, "Iniciando Grupos Manager");
	}

	public function exec(){
		$ligacao = new Ligacao();
		$ligacao->setAgi($this->agi);
		$ligacao->setLimiteTempo($this->grupo->tempo_chamada);

		$dial = new Dial($ligacao);

		if($this->grupo->tipo == 'hierarquico'){
			$this->agi->write_console(__FILE__,__LINE__, "Grupo Hierárquico");
			$this->hierarquico($dial);
		} else if($this->grupo->tipo == 'distribuidor') {
			$this->agi->write_console(__FILE__,__LINE__, "Grupo Distribuidor");			
			$this->distribuidor($dial);
		} else if( $this->grupo->tipo == 'multiplo'){
			$this->agi->write_console(__FILE__,__LINE__, "Grupo Múltiplos");
			$this->multiplo($dial);
		}
	}


	public function hierarquico($dial){
		$linhas = $this->linhas;

		$rotacao = $linhas->pluck('autenticacao.login_ata')->toArray();
		$this->agi->write_console(__FILE__,__LINE__, "ROTACAO INICIAL ".json_encode($rotacao));

		$rotacao_parcial = [current($rotacao), next($rotacao)];
		
		$this->agi->write_console(__FILE__,__LINE__, "ROTACAO PARCIAL INICIAL ".json_encode($rotacao_parcial));

		$ligacao = $dial->getLigacao();
		$ligacao->setLimiteTempo($this->grupo->tempo_chamada);
		$ligacao->setTipo( 'interna' );

		do {

			foreach($rotacao_parcial as $ramal){

				$numero = new Numero($ramal); 
				$ligacao->setExten( $numero );
				$this->agi->write_console(__FILE__,__LINE__, "RAMAL ".$ramal);

				$dial->setLigacao($ligacao);
				$dial->exec();

			}

			$novo_ramal_rotacao = next($rotacao);
			$this->agi->write_console(__FILE__,__LINE__, "NEXT ".$novo_ramal_rotacao);
			array_push($rotacao_parcial, $novo_ramal_rotacao);
		
			$this->agi->write_console(__FILE__,__LINE__, "NOVA ROTACAO PARCIAL ".json_encode($rotacao_parcial));

		} while($novo_ramal_rotacao !== false
				&& in_array($dial->getLastStatus(), ["CHANUNAVAIL",
															"BUSY",
															"CONGESTION",
															"NOANSWER"]));
	}


	public function multiplo($dial){

		$linhas = $this->linhas;
		$ramais = $linhas->pluck('autenticacao.login_ata')->toArray();
		
		$expression_arr = array_map(function($el){ 
							return "SIP/".$el;
						}, $ramais);
		
		$dial->execRaw( implode("&", $expression_arr) );
	}


	public function distribuidor($dial){
		$id_assinante = $this->grupo->assinante()->first()->id;
		$linhas = $this->linhas;

		$ramais_grupo = $linhas->pluck('autenticacao.login_ata')->toArray();

		$rotacao = $this->agi->get_variable('DB(rv_distribuidores/'.$id_assinante.')')['data'];
		$rotacao_obj = json_decode($rotacao, true);

		$this->agi->write_console(__FILE__,__LINE__, "RAMAIS DO GRUPO: ".json_encode($ramais_grupo));

		if(empty($rotacao_obj) || count($rotacao_obj) !== count($ramais_grupo) ){
			$rotacao_obj = $ramais_grupo;
		}
		
		$this->agi->write_console(__FILE__,__LINE__, "ROTACAO: ".$rotacao);

		$ligacao = $dial->getLigacao();
		$ligacao->setLimiteTempo($this->grupo->tempo_chamada);

		foreach($rotacao_obj as $key=>$ramal){

			$numero = new Numero($ramal);
			$ligacao->setExten($numero);
			$dial->setLigacao($ligacao);
			$dial->exec();
			
			$this->agi->write_console(__FILE__,__LINE__, "STATUS : ".$dial->getLastStatus());
			
			if($dial->getLastStatus() == "ANSWER") {
				$ramal_atendedor = $ramal;
				$key_ramal_atendedor = $key;
				
				unset($rotacao_obj[$key_ramal_atendedor]); //remove o ramal da lista

				array_push($rotacao_obj, $ramal_atendedor); //coloca ele para o final 

				break;
			}
		}

		//$this->agi->write_console(__FILE__,__LINE__, "ROTACAO ".$rotacao);
		$this->agi->set_variable('DB(rv_distribuidores/'.$id_assinante.')', json_encode($rotacao_obj));
	}
}