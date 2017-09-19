<?php

class Logger {

	public static function write($file, $line, $msg, $verbose=1){
        
        if($verbose){

            $msg = str_replace("\\", "\\\\", $msg);
            $msg = str_replace("\"", "\\\"", $msg);
            $msg = str_replace("\n", "\\n", $msg);
                
            /*if ($verbose == 2) {
                fwrite(STDLOG, date("d-m-Y H:i:s") . " Arquivo : $file - Linha : $line - $msg\n");
            }*/
            $date = new DateTime();
            $log = fopen("/var/log/rv.log", "a");
            fwrite($log, $date->format("y-m-d H:i:s")." ".$file." ".$line." "." -- ".$msg.chr(13).chr(10));

            fwrite(STDOUT, "VERBOSE \" '\033[01;34mArq:\033[01;32m$file - \033[01;34mL:\033[01;32m$line - $msg \033[0m'\"\n");
            fflush(STDOUT);
            fgets(STDIN, 1024);

        }
        
    } 

}
