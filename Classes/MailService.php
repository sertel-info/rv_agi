<?php
//require_once __DIR__.'/vendor/autoload.php';

class MailService{
	
	private $address;
	private $data;
	private $attachment;
	private $unirest_body;
	private $unirest_request;

	function __construct($address, $unirest_body, $unirest_request){
		$this->address = $address;
		$this->unirest_request = $unirest_request;
		$this->unirest_body = $unirest_body;
	}

	public function setData($data){
		$this->data = $data;
	}

	public function setAttachment($file){
		$this->attachment = $file;
	}

	public function send(){
		$body = $this->unirest_body->multipart($this->data, $this->attachment);
		$response = $this->unirest_request->post($this->address, '', $body);

		return $response;
	}
}