<?php

trait WriteConsoleTrait {
	  
    public function write_console($file, $line, $msg, $verbose=1) {
        if($verbose){

            $msg = str_replace("\\", "\\\\", $msg);
            $msg = str_replace("\"", "\\\"", $msg);
            $msg = str_replace("\n", "\\n", $msg);
                
            /*if ($verbose == 2) {
                fwrite(STDLOG, date("d-m-Y H:i:s") . " Arquivo : $file - Linha : $line - $msg\n");
            }*/

            fwrite(STDOUT, "VERBOSE \" '\033[01;34mArq:\033[01;32m$file - \033[01;34mL:\033[01;32m$line - $msg \033[0m'\"\n");
            fflush(STDOUT);
            fgets(STDIN, 1024);
        }
        
    }  
}