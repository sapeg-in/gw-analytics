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
		// получаем все логи боёв, которые мы еще не запросили
		$r = $this->mysql->query("SELECT * FROM `syndicate_warlog` WHERE log = '' ORDER BY war_id DESC");
		while ($row = $this->mysql->fetch($r)) {
			$res = shell_exec("casperjs --ssl-protocol=any --ignore-ssl-errors=true --cookie=\"{$this->CONFIG['cookie']}\" --war_id=\"{$row['war_id']}\" {$this->CONFIG['DIR']}/console/syndicate.war.js");
			$json = object_to_array(json_decode($res));
			if (stristr($json['title'], "Авторизация в игре")){
				shell_exec("casperjs --ssl-protocol=any --ignore-ssl-errors=true --cookie=\"{$this->CONFIG['cookie']}\" --login=\"{$this->CONFIG['user_login']}\" --password=\"{$this->CONFIG['user_password']}\"  {$this->CONFIG['DIR']}/console/auth.js");
			}else{
				// обновляем лог, в будущем может пригодиться + чтобы не дергать ганджу еще раз
				$this->mysql->query("UPDATE syndicate_warlog SET log = '".addslashes(trim($json['data']))."' WHERE `war_id` = '".addslashes($row['war_id'])."'");
				// ищем первый барашек, в строке до барашка будет (может быть) объект нападения
				$str = mb_substr($json['data'], 0, mb_strpos($json['data'], "<br>"));
				if (preg_match_all("/object\.php\?id=([0-9]+)/i", $str, $m)){
					$this->mysql->query("UPDATE syndicate_warlog SET object = '".addslashes($m[1][0])."' WHERE `war_id` = '".addslashes($row['war_id'])."'");
				}
			}
			// спим, чтобы сервер не подумал на атаку ))
			sleep(2);
		}

		// получаем все логи боёв, которые еще не привязали к логу синдиката
		$r = $this->mysql->query("SELECT * FROM `syndicate_warlog` WHERE log != '' AND type = 'attack' AND object != 0 AND log_id = 0 ORDER BY war_id DESC");
		while ($row = $this->mysql->fetch($r)) {
			$r2 = $this->mysql->query("SELECT id FROM `syndicate_log` WHERE type = '{$row['type']}' AND cdate < '{$row['cdate']}' AND cdate > '{$row['cdate']}'-INTERVAL 40 MINUTE AND who = '{$row['init']}' AND object = '{$row['object']}'");
			if ($this->mysql->num_rows($r2) == 1){
				$log_id = $this->mysql->result($r2, 0);
				$this->mysql->query("UPDATE syndicate_warlog SET log_id = '{$log_id}' WHERE `war_id` = '".addslashes($row['war_id'])."'");
			}
		}

		// получаем все логи боёв, которые еще не привязали к логу синдиката
		$r = $this->mysql->query("SELECT * FROM `syndicate_warlog` WHERE log != '' AND type = 'defense' AND object != 0 AND log_id = 0 ORDER BY war_id DESC");
		while ($row = $this->mysql->fetch($r)) {
			$r2 = $this->mysql->query("SELECT id FROM `syndicate_log` WHERE type = '{$row['type']}' AND cdate < '{$row['cdate']}' AND cdate > '{$row['cdate']}'-INTERVAL 40 MINUTE AND object = '{$row['object']}'");
			if ($this->mysql->num_rows($r2) == 1){
				$log_id = $this->mysql->result($r2, 0);
				$this->mysql->query("UPDATE syndicate_warlog SET log_id = '{$log_id}' WHERE `war_id` = '".addslashes($row['war_id'])."'");
			}
		}
	}
}

$logic = new logic($CONFIG);
$logic->site();


?>