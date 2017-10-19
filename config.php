<?php

$CONFIG = [];

$CONFIG['DIR'] = "/absolute/path/to/folder"; // no slash in the end

// путь где будут лежать печеньки
$CONFIG['cookie'] = $CONFIG['DIR']."/console/c.json";

$CONFIG['dbhost'] = "localhost";
$CONFIG['dbuname'] = "";
$CONFIG['dbpass'] = "";
$CONFIG['dbname'] = "";
$CONFIG['dbport'] = 3306;

// номер синдиката
$CONFIG['synd'] = 6496;

// логин и пароль персонажа
$CONFIG['user_login'] = "";
$CONFIG['user_password'] = "";



function prr ($var, $die = 0){
	echo "<pre>\r\n";
	print_r ($var);
	echo "</pre>\r\n";
	if ($die) die();
}



function object_to_array($obj) 
{
        $arrObj = is_object($obj) ? get_object_vars($obj) : $obj;
        foreach ($arrObj as $key => $val) {
                $val = (is_array($val) || is_object($val)) ? object_to_array($val) : $val;
                $arr[$key] = $val;
        }
        return $arr;
}
?>