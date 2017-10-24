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

include "./../config.php";
include "./../mysql.class.php";

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
		// for($i = 0; $i < 10; $i++){
			$i = 0;
			$res = shell_exec("casperjs --ssl-protocol=any --ignore-ssl-errors=true --cookie=\"{$this->CONFIG['cookie']}\" --page_id=\"{$i}\" --synd=\"{$this->CONFIG['synd']}\" {$this->CONFIG['DIR']}/console/syndicate.warlog.js");
			$json = object_to_array(json_decode($res));
			if (stristr($json['title'], "Авторизация в игре")){
				shell_exec("casperjs --ssl-protocol=any --ignore-ssl-errors=true --cookie=\"{$this->CONFIG['cookie']}\" --login=\"{$this->CONFIG['user_login']}\" --password=\"{$this->CONFIG['user_password']}\"  {$this->CONFIG['DIR']}/console/auth.js");
			}else{
				foreach ($json['data'] as $row) {
					$row['war'] = trim($row['war']);
					preg_match_all("/bid=([0-9]+)/i", $row['war'], $m2);
					$war_id = $m2[1][0];

					
					$res = $this->mysql->query("SELECT COUNT(*) FROM syndicate_warlog WHERE `war_id` = '".addslashes($war_id)."'");
					$n = $this->mysql->result($res, 0);
					if ($n == 1){
						// если этот лог уже есть в БД, то не тратим время и проверяем остальные
						continue;
					}
					

					// дата
					preg_match("/([0-9]{2})\.([0-9]{2})\.([0-9]{2}) ([0-9]{2})\:([0-9]{2})/i", $row['date'], $m);
					$date = ($m[3]+2000)."-".$m[2]."-".$m[1]." ".$m[4].":".$m[5].":00";
					$tm = strtotime($date);
					$date = date("Y-m-d H:i:s", $tm);
					


					// отделяем бойцов от списка команда
					$row['act'] = explode(" &nbsp; ", $row['act']);
					$row['act'][0] = explode(" vs ", $row['act'][0]);
					// делим бойцов на 2 команды
					$row['act'][1] = explode(" vs ", $row['act'][1]);
					// теперь определяем это нападение синдиката или защита
					if (mb_strpos($row['act'][0][0], (string)$this->CONFIG['synd']) !== false){
						// это наша атака
						$type = "attack";
						// если у нас цвет красный - атака выигрышная
						if (mb_strstr($row['act'][0][0], "color:red")){
							$win = 1;
						}
						// если у нас цвет синий - продули
						if (mb_strstr($row['act'][0][0], "color:blue")){
							$win = 0;
						}
						// если у нас цвет зеленый - ничья
						if (mb_strstr($row['act'][0][0], "color:green")){
							$win = 0;
						}
					}
					if (mb_strpos($row['act'][0][1], (string)$this->CONFIG['synd']) !== false){
						// это защита
						$type = "defense";
						// если у нас цвет красный - атака выигрышная
						if (mb_strstr($row['act'][0][1], "color:red")){
							$win = 1;
						}
						// если у нас цвет синий - продули
						if (mb_strstr($row['act'][0][1], "color:blue")){
							$win = 0;
						}
						// если у нас цвет зеленый - ничья
						if (mb_strstr($row['act'][0][1], "color:green")){
							$win = 0;
						}
					}
					// получаем список наших бойцов
					switch ($type){
						case 'attack': {
							$members = $row['act'][1][0];
							break;
						}
						case 'defense': {
							$members = $row['act'][1][1];
							break;
						}
						default: break;
					}

					preg_match_all("/id=([0-9]+)/i", $members, $m3);

					// кто инициировал нападение (неважно враг или друг)
					preg_match_all("/id=([0-9]+)/i", $row['act'][1][0], $m4);
					

					foreach ($m3[1] as $value) {
						$this->mysql->query("INSERT INTO syndicate_members_war (`war_id`, `member_id`) VALUES ('".addslashes($war_id)."', '".addslashes($value)."')");
					}

					$this->mysql->query("INSERT IGNORE INTO syndicate_warlog (`war_id`, `cdate`, `type`, `win`, `init`, `object`, `log`) VALUES ('".addslashes($war_id)."', '".addslashes($date)."', '".addslashes($type)."', {$win}, {$m4[1][0]}, 0, '')");
			
				}
			}
			// sleep(5);
		// }
	}
}

$logic = new logic($CONFIG);
$logic->site();





?>