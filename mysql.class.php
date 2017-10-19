<?php
class mysql {
	var $dir;
	var $dbhost;
	var $dbuname;
	var $dbpass;
	var $dbname;
	// счетсчик кол-ва запросов в базу
	var $mysqlcountquery = 0;
	// счетчик общего времени запросов в базу
	var $mysqlquerytime = 0;
	var $result;
	var $dev;
	
	public $db;
	
	public $total = 0;
	public $pages = array();
	
	// конструктор
	function __construct($config){
		$this->dir = $config['DIR'];
		$this->dbhost = $config['dbhost'];
		$this->dbport = $config['dbport'];
		$this->dbuname = $config['dbuname'];
		$this->dbpass = $config['dbpass'];
		$this->dbname = $config['dbname'];
		$this->dev = 0;
		$this->connect();
	}
	
	// коннект к БД
	function connect (){
		if(!$this->db = mysqli_connect($this->dbhost, $this->dbuname, $this->dbpass, $this->dbname, $this->dbport)) die ("Ошибка подключения к БД");
		mysqli_query($this->db, "SET CHARACTER SET utf8");
		mysqli_query($this->db, "SET SESSION collation_connection='utf8_general_ci'");
		mysqli_query($this->db, "SET NAMES utf8");
		mysqli_query($this->db, "set character_set_server='utf8'");
		mysqli_query($this->db, "set character_set_results='utf8'");
		mysqli_query($this->db, "set character_set_connection='utf8'");
	}
	
	function close (){
		mysqli_close($this->db);
	}
	
	// запрос в базу
	function query ($query, $debug = false) {
		$error = false;
		$this->mysqlcountquery++;
		if ($debug){
			echo "<br>";
			echo "MySQL query: ".$query;
			echo "<br>"; 
			//die ();
		}
		//echo "<!-- {$query} -->\r\n";
		$loaded = 0.00;
		list($time, $ms) = explode(" ", microtime());
		$first_time = $time + $ms;
		//$result = mysqli_query($this->db, $query) or die ("Mysql error: ".mysqli_error($this->db)."<br />Mysql query: ".$query."");
		if (!$result = mysqli_query($this->db, $query)){
			$error = true;
		}
		list($time, $ms) = explode(" ", microtime());
		$second_time = $time + $ms;
		$loaded = $second_time - $first_time;
		//$loaded = round($loaded,2);
		// echo "<!-- {$loaded} = {$query} -->\r\n";
		if (($loaded > 0.2 and $this->dev == 1) or ($error)){
		//if ($error){
			$t = date('Y-m-d H:i:s') . ':' . $_SERVER['REQUEST_URI'] . "\n";
			$t .= "ERROR: ".mysqli_error($this->db)."\n";
			$t .= "SQL: " . $query . "\n";
			$t .= "TIME: {$loaded}\n";
			$t .= "----------------------------------------------------------\n\n";
			// $fh = fopen($this->dir."i/sql.log", 'a');
			// fputs($fh, $t);
			// fclose($fh);
			error_log($t);
			$error = false;
		}
		$this->mysqlquerytime += $loaded;
		$this->result = $result;
		return $result;
	}

	// запрос в базу
	function multi_query ($query, $debug = false) {
		$this->mysqlcountquery++;
		if ($debug){
			echo "<br>";
			echo "MySQL query: ".$query;
			echo "<br>";
			//die ();
		}
		//echo "<!-- {$query} -->\r\n";
		$loaded = 0.00;
		list($time, $ms) = explode(" ", microtime());
		$first_time = $time + $ms;
		$result = mysqli_multi_query($this->db, $query) or die ("Mysql error: ".mysqli_error($this->db)."<br />Mysql query: ".$query."");
		list($time, $ms) = explode(" ", microtime());
		$second_time = $time + $ms;
		$loaded = $second_time - $first_time;
		//$loaded = round($loaded,2);
		$this->mysqlquerytime += $loaded;
		$this->result = $result;
		do
		{
		// do nothing, just iterate over results to make sure no errors  
		}
		while (mysqli_next_result($this->db));
		return $result;
	}
	
	function result ($res, $row) {
		$r = mysqli_fetch_array($res);
		return $r[$row];
	}

	function fetch ($result = ""){
		if ($result == "" and isset($this->result)){
			return mysqli_fetch_assoc($this->result);
		}else{
			return mysqli_fetch_assoc($result);
		}
	}

	function num_rows ($res) {
		return mysqli_num_rows($res);
	}
	
	function insert_id (){
		return mysqli_insert_id($this->db);
	}
	
	function create ($fields, $table, $hash = null, $joins = null, $needpages = true){
		$where = "";
		$dwhere = "";
		$limit = "";
		$perpage = 0;
		$own_cond = "";
		$having = "";
		$force_index = "";
		if (isset($hash['having'])){
			$having = $hash['having'];
		}
		if (isset($hash['own_cond'])){
			$own_cond = $hash['own_cond'];
		}
		if (isset($hash['force_index'])){
			$force_index = $hash['force_index'];
		}
		if (isset($hash['perpage']) and is_numeric($hash['perpage'])){
			$perpage = $hash['perpage'];
		}
		unset($hash['perpage']);
		
		if (isset($hash['page']) and is_numeric($hash['page']) and isset($hash['limit'])){
			$from = (ceil($hash['page'])-1)*$hash['limit'];
			$limit = "LIMIT ".$from.", ".$hash['limit'];
		}
		if (isset($hash['limit']) and is_numeric($hash['limit']) and !isset($hash['page'])){
			$limit = "LIMIT ".ceil($hash['limit']);
		}
		// все записи
		if (isset($hash['limit']) and $hash['limit']=="all" and !isset($hash['page'])){
			$limit = "";
		}
		$order = "";
		if (isset($hash['order'])){
			$order = "ORDER BY ".$hash['order'];
		}
		$group = "";
		if (isset($hash['group'])){
			$group = "GROUP BY ".$hash['group'];
		}
		unset($hash['limit'], $hash['page'], $hash['order'], $hash['own_cond'], $hash['having'], $hash['force_index'], $hash['group']);
		if (is_array($hash))
		foreach ($hash as $key=>$value){
			if (is_array($value) and (isset($value['from']) or isset($value['to']))){
				// тип выбора "от" и "до"
				if (isset($value['from']) and strlen ($value['from'])>0 and !isset($value['to'])) $where .= " AND {$key}>=".$value['from']."";
				if (!isset($value['from']) and isset($value['to']) and strlen ($value['to'])>0) $where .= " AND {$key}<=".$value['to']."";
				if (isset($value['from']) and isset($value['to']) and strlen ($value['to'])>0 and strlen ($value['from'])>0) $where .= " AND {$key}<=".$value['to']." AND {$key}>=".$value['from']."";
			}elseif (is_array($value) and (isset($value['like']) and $value['like']==1 and isset($value['string']))){
				// LIKE
				$swhere = array();
				if (is_array($value['fields'])){
					foreach ($value['fields'] as $searchfield){
						$swhere[] = "{$searchfield} LIKE '%".$value['string']."%'";
					}
				}
				$where .= " AND (".join(" OR ", $swhere).")";
			}elseif (is_array($value) and isset($value['operand'])){
				if (is_array($value['value'])){
					foreach ($value['value'] as $vvv){
						if (is_numeric($vvv)){
							$where .= " AND {$key} {$value['operand']} ".$vvv."";
						}else{
							$where .= " AND {$key} {$value['operand']} '".$vvv."'";
						}
					}
				}else{
					if (is_numeric($vvv)){
						$where .= " AND {$key} {$value['operand']} ".$value['value']."";
					}else{
						$where .= " AND {$key} {$value['operand']} '".$value['value']."'";
					}
				}
			}elseif (is_array($value) and isset($value['or'])){
				if (is_array($value['value'])){
					$lll = array();
					foreach ($value['value'] as $k=>$vvv){
						if (is_numeric($vvv)){
							$lll[] = " AND {$key} = ".$vvv."";
						}else{
							$lll[] = " AND {$key} = '".$vvv."'";
						}
					}
					$where .= " AND (".implode(" OR ", $lll).")";
				}
			}elseif (is_array($value)){
				// множественный выбор
				$c = "";
				if (strstr($key, "!")){
					$key = str_replace("!", "", $key);
					$c = " NOT ";
				}
				if (is_string($value[0])){
					$where .= " AND {$key} {$c} IN ('".implode("', '", $value)."')";
				}else{
					$where .= " AND {$key} {$c} IN (".implode(", ", $value).")";
				}
			}elseif (is_numeric($value)){
				$c = "";
				if (strstr($key, "!")){
					$key = str_replace("!", "", $key);
					$c = "!";
				}
				$where .= " AND {$key} {$c}= ".$value."";
			}else{
				$c = "";
				if (strstr($key, "!")){
					$key = str_replace("!", "", $key);
					$c = "!";
				}
				$where .= " AND {$key} {$c}= '".$value."'";
			}
		}
		
		if (isset($own_cond) and strlen($own_cond) > 0){
			$where .= " AND ".$own_cond;
		}
		
				
		$where_pages = $where;
		
		
		$query = "SELECT {$fields} FROM {$table}";
		if (isset($force_index) and strlen($force_index) > 0){
			$query .= "\r\nFORCE INDEX ({$force_index})";
		}
		if (is_array($joins)){
			foreach ($joins as $key=>$value) {
				$query .= "\r\nLEFT JOIN {$key} ON ({$value})";
			}
		}
		$query .= "\r\nWHERE 1=1".$where." ".((isset($having) and strlen($having) > 0)?"HAVING {$having}":"")." ".$group." ".$order;
		
		$query_pages = "SELECT COUNT(*) FROM {$table}";
		if (is_array($joins)){
			foreach ($joins as $key=>$value) {
				$query_pages .= "\r\nLEFT JOIN {$key} ON ({$value})";
			}
		}
		$query_pages .= "\r\nWHERE 1=1".$where_pages." ".$group;
		
		$limit = " ".$limit;
		if ($needpages){
			$res = $this->query($query_pages);
			$this->total = mysqli_num_rows($res);
			$tmp = mysqli_fetch_field($res);
			if ($this->total == 1 and ($tmp->name == "COUNT(*)")){
				$this->total = $this->result($res, 0);
			}
			$this->pages = array();
			if ($perpage > 0){
				for ($i=1; $i<=ceil($this->total/$perpage); $i++){
					$this->pages[] = $i;
				}
			}
		}
		$this->query = $query.$limit;
		return $query.$limit;
	}
}
?>