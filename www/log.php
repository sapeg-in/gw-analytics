<?php

include "./../config.php";
include "./../mysql.class.php";



class logic {
	var $mysql;
	var $CONFIG;
	function __construct(&$_CONFIG){
		$this->mysql = new mysql($_CONFIG);
		$this->CONFIG = $_CONFIG;
	}

	function site () {
		global $results;
		$results['log'] = [];
		

		$res = $this->mysql->query("SELECT * FROM `syndicate_log` WHERE who = '".intval($_REQUEST['id'])."' ORDER BY cdate DESC");
		while ($row = $this->mysql->fetch($res)) {
			$results['log'][] = $row;
		}
	}
}

$logic = new logic($CONFIG);
$logic->site();
?>
<!DOCTYPE HTML>
<html lang="ru-ru">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title><?=$CONFIG['synd']?></title>
		<link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
		<script type="text/javascript" src="https://code.jquery.com/jquery-1.12.4.min.js"></script>
		<script type="text/javascript" src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
		<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.7.0/Chart.min.js"></script>
		<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.7.0/Chart.bundle.min.js"></script>

		<link href="https://cdn.datatables.net/1.10.16/css/dataTables.bootstrap4.min.css" rel="stylesheet">
		<script src="https://cdn.datatables.net/1.10.16/js/jquery.dataTables.min.js"></script>
		<script src="https://cdn.datatables.net/1.10.16/js/dataTables.bootstrap4.min.js"></script>
		
	</head>

	<body>
		
		<style type="text/css">
			.content {
				padding: 25px;
			}
		</style>
		
		<div class="content">
			<div class="row">
				<div class="col-md-12">
					<h2 style="text-align: center;">Протокол персонажа </h2>
					<br /><br />
					<table class="table">
						<thead>
							<tr>
							<th>Время</th>
							<th>Лог</th>
							</tr>
						</thead>
					<tbody>
						<?php
							foreach($results['log'] as $row){
						?>
								<tr>
								<td><?=$row['cdate']?></td>
								<td><?=$row['event']?></td>
								</tr>
						<?php
							}
						?>
					</tbody>
					</table>
				</div>
			</div>
		</div>
	</body>
</html>