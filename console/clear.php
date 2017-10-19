<?php
set_time_limit(0);
// Turn off output buffering
ini_set('output_buffering', 'off');
// Turn off PHP output compression
ini_set('zlib.output_compression', false);
         
//Flush (send) the output buffer and turn off output buffering
//ob_end_flush();
while (@ob_end_flush());
         
// Implicitly flush the buffer(s)
ini_set('implicit_flush', true);
ob_implicit_flush(true);
 
//prevent apache from buffering it for deflate/gzip
// header("Content-type: text/plain");
// header('Cache-Control: no-cache');

error_reporting(E_ALL);

include "./config.php";
include "./mysql.class.php";

function n(){
	echo "\n";
}



class logic {
	var $mysql;
	var $CONFIG;
	function __construct(&$_CONFIG){
		$this->mysql = new mysql($_CONFIG);
		$this->CONFIG = $_CONFIG;
	}

	function site () {
		
	}
}

$logic = new logic($CONFIG);
$logic->site();





?>