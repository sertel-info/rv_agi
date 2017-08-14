<?php

class BillCalculator{

	public static function calcTarifaFixo($tarifa, $tempo){

		//se o tempo for menor que 3 segundos, não cobra
		if($tempo <= 3){
			return 0.00;
		}
		//retira 3 segundos do billsec, 
		//pois somente começa a cobrar após o terceiro segundo
		$segundos = intval($tempo) - 3;
		$final = 0;

		//supondo o cálculo com 30/6
		$metodo['bloco_inicial'] = 30;
		$metodo['tamanho_blocos'] = 6;

		$tarifaCorrigida = str_replace(',', '.', $tarifa);
		echo chr(13).chr(10).'tarifa :'.$tarifaCorrigida.chr(13).chr(10);

		//nos primeiros 30 segundos cobra 50% da tafica por minuto;
		$final += $tarifaCorrigida/2;

		//tira os primeiros 30 segundos para o cálculo;
		$segundosReal = $segundos - $metodo['bloco_inicial'];
		//$segundosReal = $segundos > $metodo['bloco_inicial'] ? $segundos - $metodo['bloco_inicial'] : $segundos;

		//número de blocos  = qtd. de segundos além dos 30 primeiros dividida 6;
		//se houver sobra quebrada, cobra um bloco inteiro
		$sobra = $segundosReal % 6 > 0 ? 1 : 0; 
		$numero_blocos = (int)($segundosReal/6)+$sobra;

		if($numero_blocos < 1){
			$numero_blocos = 0;
		}

		$preco_por_bloco = $tarifaCorrigida/10;
		
		$final +=  (($numero_blocos) * ($preco_por_bloco));

		return number_format($final, 2);
	}

	public static function calcTarifaMovel($tarifa, $tempo){
		$tarifa = $tarifa/60;

		$final = $tarifa*$tempo;

		return number_format($final, 2);
	}

	public static function calcTarifa($tipo, $tarifa, $tempo){
		if(!in_array($tipo, ['movel', 'fixo', 'ddi']))
			return false;

		if($tipo == 'movel'){
			return self::calcTarifaMovel($tarifa, $tempo);
		} else if($tipo == 'fixo'){
			return self::calcTarifaFixo($tarifa, $tempo);
		}
	}


	public static function calcTempoMaxLigacao(Ligacao $ligacao){
		$saldo = $ligacao->getLinha()->assinante->financeiro->creditos;
		$exten = $ligacao->getExtenObj();
		
		if($exten->getTipo() == 'movel' || $exten->getTipo() == 'ddi'){
			return self::calcTempoMaxMovel($ligacao->getTarifa(), $saldo);
		}

		return self::calcTempoMaxFixo($ligacao->getTarifa(), $saldo);
	}

	public static function calcTempoMaxFixo($tarifa, $saldo){

		$preco_por_bloco = $tarifa/10;

		if($saldo < ($tarifa/2)){
		  	$tempo_maximo = 0;
		}		
		else if($saldo == ($tarifa/2)){
			$tempo_maximo = 30;
	    } else {
		    $saldoUtil = $saldo - ($tarifa/2);
		    $tempo_maximo = (($saldoUtil/$preco_por_bloco) * 6)+30;
		}

		return (int)$tempo_maximo;
	}

	public static function calcTempoMaxMovel($tarifa, $saldo){
		return (int)($saldo / ($tarifa/60));
	}
}