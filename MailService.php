<?php
require_once __DIR__.'/vendor/autoload.php';

class MailService{
	
	private $address;
	private $data;
	private $attachment;

	function __construct($address){
		$this->address = $address;
	}

	public function setData($data){
		$this->data = $data;
	}

	public function setAttachment($file){
		$this->attachment = $file;
	}

	public function send(){
		$body = Unirest\Request\Body::multipart($this->data, $this->attachment);
		$response = Unirest\Request::post($this->address, '', $body);

		return $response;
	}
}