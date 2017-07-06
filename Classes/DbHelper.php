<?php

class DbHelper{

	private $conn;
	private $err;
	private $errno;

	use WriteConsoleTrait;

	function __construct(mysqli $conn){
		$this->conn = $conn;
	}

	function getLine($num){
		foreach(['dados_autenticacao_linhas.login_ata', 'dids.extensao_did'] as $campo){
			$linha = $this->execQuery("SELECT IF(assinantes.nome is null, assinantes.nome_fantasia, assinantes.nome) as nome,".
									  "linhas.cli,".
									  "linhas.tecnologia,".
									  "linhas.id as id_linha,".
									  "linhas.ddd_local,".
									  "assinantes.id as assinante_id ,".
									  "planos.id as plano_id ,".
									  "dados_facilidades_linhas.*, ".
									  "dados_configuracoes_linhas.*, ".
									  "dados_autenticacao_linhas.*, ".
									  "dids.* ,".
									  "planos.* ".
									  "FROM dados_autenticacao_linhas ".
									  "LEFT JOIN linhas ON dados_autenticacao_linhas.linha_id = linhas.id ".
									  "LEFT JOIN assinantes ON linhas.assinante_id = assinantes.id ".
									  "LEFT JOIN planos ON planos.id = assinantes.plano ".
									  "LEFT JOIN dados_facilidades_linhas ON linhas.id = dados_facilidades_linhas.linha_id ".
									  "LEFT JOIN dados_configuracoes_linhas ON linhas.id = dados_configuracoes_linhas.linha_id ".
									  "LEFT JOIN dids ON linhas.id = dids.linha_id ".
									  "WHERE $campo = '".$num."' ".
									  "LIMIT 1");

			/*$this->write_console(__FILE__, __LINE__, "SELECT IF(assinantes.nome is null, assinantes.nome_fantasia, assinantes.nome) as nome,".
									  "linhas.cli,".
									  "linhas.tecnologia,".
									  "linhas.id as linha_id,".
									  "assinantes.id as assinante_id ,".
									  "planos.id as plano_id ,".
									  "dados_facilidades_linhas.*, ".
									  "dados_configuracoes_linhas.*, ".
									  "dados_autenticacao_linhas.*, ".
									  "dids.* ,".
									  "planos.* ".
									  "FROM dados_autenticacao_linhas ".
									  "LEFT JOIN linhas ON dados_autenticacao_linhas.linha_id = linhas.id ".
									  "LEFT JOIN assinantes ON linhas.assinante_id = assinantes.id ".
									  "LEFT JOIN planos ON planos.id = assinantes.plano ".
									  "LEFT JOIN dados_facilidades_linhas ON linhas.id = dados_facilidades_linhas.linha_id ".
									  "LEFT JOIN dados_configuracoes_linhas ON linhas.id = dados_configuracoes_linhas.linha_id ".
									  "LEFT JOIN dids ON linhas.id = dids.linha_id ".
									  "WHERE $campo = '".$num."' ".
									  "LIMIT 1", 1);*/

			if($linha){
				break;
			}
		} 

		return $linha;
	}

	public function getConfigs(){
		return $this->execQuery("SELECT * FROM configuracoes LIMIT 1");
	}

	public function setConnnection($conn){
		if ($conn->connect_errno) {
			$this->write_console(__FILE__,__LINE__, "Falha ao conectar ao banco: %s\n", $conn->connect_error);
		} else {
			$this->conn = $conn;
		}
	}

	public function execQuery($query){
		if(!$this->conn){
			$this->write_console(__FILE__,__LINE__, "Nenhuma conexão ativa com o banco de dados");
			return false;
		}

		if(($result = $this->conn->query($query)) !== FALSE){
			if((!is_object($result) && !is_array($result)) ){
				return $result;
			}


			if($result->num_rows !== NULL){
				return $result->fetch_array();
			}

			return false;
		}

		$this->write_console(__FILE__,__LINE__, "Falha ao executar a query: ".$this->conn->error);
		return false;
	}

	public function getError(){
		return $this->conn->error;
	}

	public function getErrNo(){
		return $this->conn->errno;
	}

}