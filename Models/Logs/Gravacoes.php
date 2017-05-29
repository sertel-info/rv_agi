<?php

use Illuminate\Database\Eloquent\Model;

class Gravacoes extends Model
{	
	protected $connection = "mysql-rv_logs";
    protected $table = "gravacoes";
    public $timestamps = false;
    protected $fillable = ["exten",
							"callerid",
							"data",
							"unique_id",
							"linha",
							"assinante",
							"arquivo"];

}
