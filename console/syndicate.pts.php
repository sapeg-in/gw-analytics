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
		// for($i = 0; $i < 50; $i++){
			$i = 0;
			$res = shell_exec("casperjs --ssl-protocol=any --ignore-ssl-errors=true --cookie=\"{$this->CONFIG['cookie']}\" --page_id=\"{$i}\" --synd=\"{$this->CONFIG['synd']}\" {$this->CONFIG['DIR']}/console/syndicate.pts.js");
			$json = object_to_array(json_decode($res));
			if (stristr($json['title'], "Авторизация в игре")){
				shell_exec("casperjs --ssl-protocol=any --ignore-ssl-errors=true --cookie=\"{$this->CONFIG['cookie']}\" --login=\"{$this->CONFIG['user_login']}\" --password=\"{$this->CONFIG['user_password']}\"  {$this->CONFIG['DIR']}/console/auth.js");
			}else{
				foreach ($json['data'] as $row) {
					preg_match("/([0-9]{2})\.([0-9]{2})\.([0-9]{2}) ([0-9]{2})\:([0-9]{2})/i", $row['date'], $m);
					$date = ($m[3]+2000)."-".$m[2]."-".$m[1]." ".$m[4].":".$m[5].":00";
					$tm = strtotime($date);
					$date = date("Y-m-d H:i:s", $tm);
					$row['act'] = trim($row['act']);
					$this->mysql->query("INSERT IGNORE INTO syndicate_log (`cdate`, `event`, `md5`) VALUES ('".addslashes($date)."', '".addslashes($row['act'])."', '".md5($row['act'])."')");
					if (preg_match_all("/(.+) купил ([\,0-9]+) шт. (.+) за ([\,0-9]+) PTS и ([\,0-9]+) Гб/i", $row['act'], $m)){
						$res = $this->mysql->query("SELECT id FROM syndicate_members WHERE `name` = '".addslashes($m[1][0])."'");
						$who = $this->mysql->result($res, 0);
						$this->mysql->query("UPDATE syndicate_log SET type = 'pts_shop', minus_pts = '{$m[4][0]}', who = '{$who}'  WHERE `cdate` = '".addslashes($date)."' AND `md5` = '".md5($row['act'])."'");
					}
					if (preg_match_all("/(.+) выдал звание (.+) персонажу (.+) \(([\,0-9]+) PTS\)/i", $row['act'], $m)){
						$res = $this->mysql->query("SELECT id FROM syndicate_members WHERE `name` = '".addslashes($m[1][0])."'");
						$who = $this->mysql->result($res, 0);
						$this->mysql->query("UPDATE syndicate_log SET type = 'pts_syndicat', minus_pts = '{$m[4][0]}', who = '{$who}'  WHERE `cdate` = '".addslashes($date)."' AND `md5` = '".md5($row['act'])."'");
					}
					if (preg_match_all("/(.+) перевел ([0-9]+) ед. синдикатного опыта за ([\.0-9]+) PTS/i", $row['act'], $m)){
						$res = $this->mysql->query("SELECT id FROM syndicate_members WHERE `name` = '".addslashes($m[1][0])."'");
						$who = $this->mysql->result($res, 0);
						$this->mysql->query("UPDATE syndicate_log SET type = 'pts_exp', minus_pts = '".intval($m[3][0])."', who = '{$who}'  WHERE `cdate` = '".addslashes($date)."' AND `md5` = '".md5($row['act'])."'");
					}
					
					
				}
			}
			// sleep(5);
		// }
	}
}

$logic = new logic($CONFIG);
$logic->site();





?>