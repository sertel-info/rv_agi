<?php

abstract class Singleton {

	public static function getSingleton()
	    {
	        static $instance = null;
	        if (null === $instance) {
	            $instance = new static();
	        }

	        return $instance;
	    }

	/**
	 * Construtor do tipo protegido previne que uma nova instância da
	 * Classe seja criada através do operador `new` de fora dessa classe.
	 */
	protected function __construct()
	{
	}

	/**
	 * Método clone do tipo privado previne a clonagem dessa instância
	 * da classe
	 *
	 * @return void
	 */
	private function __clone()
	{
	}

	/**
	 * Método unserialize do tipo privado para prevenir a desserialização
	 * da instância dessa classe.
	 *
	 * @return void
	 */
	private function __wakeup()
	{
	}

}