<?php

trait WriteConsoleTrait {
	  
    public function write_console($file, $line, $cmd, $verbose=true) {
        if($verbose){
            //$file = end(explode('/', $file));

            $cmd = str_replace("\\", "\\\\", $cmd);
            $cmd = str_replace("\"", "\\\"", $cmd);
            $cmd = str_replace("\n", "\\n", $cmd);
                
            /*if ($verbose == '2') {
                fwrite(STDLOG, date("d-m-Y") . " " . date("H:i:s") . " -- sertel.php --> File: $file - Line: $line - $cmd\n");
            }*/

            fwrite(STDOUT, "VERBOSE \" '\033[01;34mArq:\033[01;32m$file - \033[01;34mL:\033[01;32m$line - $cmd \033[0m'\"\n");
            fflush(STDOUT);
            fgets(STDIN, 1024);
        }
        
    }  
}