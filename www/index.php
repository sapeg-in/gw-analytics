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
		$results['graph_labels'] = [];
		$results['graph_labels2'] = [];

		for($i = 14; $i >= 0; $i--){
			$results['graph_labels'][] = date("Y-m-d", time()-86400*$i);
			$results['graph_labels2'][] = date("Y-m-d", time()-86400*$i);
		}

		$res = $this->mysql->query("SELECT COUNT(*) as n, DATE(cdate) as dt FROM `syndicate_log` WHERE type = 'attack' AND cdate > NOW()-INTERVAL 14 DAY GROUP BY DATE(cdate) ORDER BY DATE(cdate) ASC");
		$results['attacks_n'] = [];
		while ($row = $this->mysql->fetch($res)) {
			$results['attacks_n'][$row['dt']] = intval($row['n']);
		}

		$res = $this->mysql->query("SELECT COUNT(*) as n, DATE(cdate) as dt FROM `syndicate_log` WHERE type = 'defense' AND cdate > NOW()-INTERVAL 14 DAY GROUP BY DATE(cdate) ORDER BY DATE(cdate) ASC");
		// $results['defenses_dt'] = [];
		$results['defenses_n'] = [];
		while ($row = $this->mysql->fetch($res)) {
			$results['defenses_n'][$row['dt']] = intval($row['n']);
		}

		$res = $this->mysql->query("SELECT COUNT(*) as n, DATE(cdate) as dt FROM `syndicate_log` WHERE type = 'group_defense' AND cdate > NOW()-INTERVAL 14 DAY GROUP BY DATE(cdate) ORDER BY DATE(cdate) ASC");
		$results['group_defenses_n'] = [];
		while ($row = $this->mysql->fetch($res)) {
			$results['group_defenses_n'][$row['dt']] = intval($row['n']);
		}
		foreach ($results['graph_labels'] as $dt) {
			if (!isset($results['group_defenses_n'][$dt])) $results['group_defenses_n'][$dt] = 0;
			if (!isset($results['defenses_n'][$dt])) $results['group_defenses_n'][$dt] = 0;
			if (!isset($results['attacks_n'][$dt])) $results['group_defenses_n'][$dt] = 0;
		}
		
		ksort($results['group_defenses_n']);
		ksort($results['defenses_n']);
		ksort($results['attacks_n']);
		


		$res = $this->mysql->query("SELECT plus_pts, minus_pts, plus_gb, minus_gb, plus_gb-minus_gb as total_gb, plus_pts-minus_pts as total_pts, DATE(cdate) as dt FROM `syndicate_log` WHERE type = 'control_es' AND cdate > NOW()-INTERVAL 14 DAY ORDER BY DATE(cdate) ASC");
		$results['control_es'] = [];
		while ($row = $this->mysql->fetch($res)) {
			$results['control_es'][$row['dt']] = $row;
		}

		$res = $this->mysql->query("SELECT plus_pts, minus_pts, plus_gb, minus_gb, plus_gb-minus_gb as total_gb, plus_pts-minus_pts as total_pts, DATE(cdate) as dt FROM `syndicate_log` WHERE type = 'control_uran' AND cdate > NOW()-INTERVAL 14 DAY ORDER BY DATE(cdate) ASC");
		$results['control_uran'] = [];
		while ($row = $this->mysql->fetch($res)) {
			$results['control_uran'][$row['dt']] = $row;
		}

		$res = $this->mysql->query("SELECT plus_pts, minus_pts, plus_gb, minus_gb, plus_gb-minus_gb as total_gb, plus_pts-minus_pts as total_pts, DATE(cdate) as dt FROM `syndicate_log` WHERE type = 'control_other' AND cdate > NOW()-INTERVAL 14 DAY ORDER BY DATE(cdate) ASC");
		$results['control_other'] = [];
		while ($row = $this->mysql->fetch($res)) {
			$results['control_other'][$row['dt']] = $row;
		}

		$results['profit_pts'] = [];
		$results['profit_gb'] = [];
		$results['profit_gb'] = [];

		foreach ($results['graph_labels2'] as $dt) {
			if (!isset($results['control_es'][$dt])){
				$results['control_es'][$dt] = ["plus_pts" => 0, "minus_pts" => 0, "plus_gb" => 0, "minus_gb" => 0, "total_gb" => 0, "total_pts" => 0];
			}
			if (!isset($results['control_uran'][$dt])){
				$results['control_uran'][$dt] = ["plus_pts" => 0, "minus_pts" => 0, "plus_gb" => 0, "minus_gb" => 0, "total_gb" => 0, "total_pts" => 0];
			}
			if (!isset($results['control_other'][$dt])){
				$results['control_other'][$dt] = ["plus_pts" => 0, "minus_pts" => 0, "plus_gb" => 0, "minus_gb" => 0, "total_gb" => 0, "total_pts" => 0];
			}
			$results['profit_pts'][$dt] = $results['control_es'][$dt]['total_pts']+$results['control_uran'][$dt]['total_pts']+$results['control_other'][$dt]['total_pts'];
			$results['profit_gb'][$dt] = $results['control_es'][$dt]['total_gb']+$results['control_uran'][$dt]['total_gb']+$results['control_other'][$dt]['total_gb'];
			$res = $this->mysql->query("SELECT SUM(minus_pts)*(-1) FROM `syndicate_log` WHERE (type = 'pts_shop' OR type = 'pts_syndicat' OR type = 'pts_exp') AND DATE(cdate) = '{$dt}'");
			$results['nefit_pts'][$dt] = $this->mysql->result($res, 0)-$results['control_es'][$dt]['minus_pts']-$results['control_uran'][$dt]['minus_pts']-$results['control_other'][$dt]['minus_pts'];

			$res = $this->mysql->query("SELECT SUM(minus_pts)*(-1) FROM `syndicate_log` WHERE type = 'pts_shop' AND DATE(cdate) = '{$dt}'");
			$results['shop_pts'][$dt] = $this->mysql->result($res, 0);

			$res = $this->mysql->query("SELECT SUM(minus_pts)*(-1) FROM `syndicate_log` WHERE type = 'pts_exp' AND DATE(cdate) = '{$dt}'");
			$results['exp_pts'][$dt] = $this->mysql->result($res, 0);

			$res = $this->mysql->query("SELECT SUM(minus_pts)*(-1) FROM `syndicate_log` WHERE type = 'pts_syndicat' AND DATE(cdate) = '{$dt}'");
			$results['syndicat_pts'][$dt] = $this->mysql->result($res, 0);
		}

		ksort($results['control_es']);
		ksort($results['control_uran']);
		ksort($results['control_other']);
		
		$results['control_es_plus_pts'] = [];
		foreach ($results['control_es'] as $v) {
			$results['control_es_plus_pts'][] = $v['plus_pts'];
		}

		$results['control_es_minus_pts'] = [];
		foreach ($results['control_es'] as $v) {
			$results['control_es_minus_pts'][] = -$v['minus_pts'];
		}

		$results['control_es_plus_gb'] = [];
		foreach ($results['control_es'] as $v) {
			$results['control_es_plus_gb'][] = $v['plus_gb'];
		}

		$results['control_es_minus_gb'] = [];
		foreach ($results['control_es'] as $v) {
			$results['control_es_minus_gb'][] = -$v['minus_gb'];
		}

		




		$results['control_uran_plus_pts'] = [];
		foreach ($results['control_uran'] as $v) {
			$results['control_uran_plus_pts'][] = $v['plus_pts'];
		}

		$results['control_uran_minus_pts'] = [];
		foreach ($results['control_uran'] as $v) {
			$results['control_uran_minus_pts'][] = -$v['minus_pts'];
		}

		$results['control_uran_plus_gb'] = [];
		foreach ($results['control_uran'] as $v) {
			$results['control_uran_plus_gb'][] = $v['plus_gb'];
		}

		$results['control_uran_minus_gb'] = [];
		foreach ($results['control_uran'] as $v) {
			$results['control_uran_minus_gb'][] = -$v['minus_gb'];
		}
		


		$results['control_other_plus_pts'] = [];
		foreach ($results['control_other'] as $v) {
			$results['control_other_plus_pts'][] = $v['plus_pts'];
		}

		$results['control_other_minus_pts'] = [];
		foreach ($results['control_other'] as $v) {
			$results['control_other_minus_pts'][] = -$v['minus_pts'];
		}

		$results['control_other_plus_gb'] = [];
		foreach ($results['control_other'] as $v) {
			$results['control_other_plus_gb'][] = $v['plus_gb'];
		}

		$results['control_other_minus_gb'] = [];
		foreach ($results['control_other'] as $v) {
			$results['control_other_minus_gb'][] = -$v['minus_gb'];
		}




		$res = $this->mysql->query("SELECT COUNT(*) as n, who, name, DATE(cdate) as dt FROM `syndicate_log` as sl INNER JOIN syndicate_members as sm ON (sm.id = sl.who)  WHERE type = 'attack' AND DATE(cdate) = DATE(NOW()) GROUP BY who ORDER BY n DESC, who LIMIT 10");
		$results['attackers_today'] = [];
		while ($row = $this->mysql->fetch($res)) {
			$results['attackers_today'][] = $row;
		}

		$res = $this->mysql->query("SELECT COUNT(*) as n, who, name, DATE(cdate) as dt FROM `syndicate_log` as sl INNER JOIN syndicate_members as sm ON (sm.id = sl.who) WHERE type = 'attack' AND DATE(cdate) = DATE(NOW()-INTERVAL 1 DAY) GROUP BY who ORDER BY n DESC, who LIMIT 10");
		$results['attackers_yesterday'] = [];
		while ($row = $this->mysql->fetch($res)) {
			$results['attackers_yesterday'][] = $row;
		}


		$results['pts_dates'] = [];

		for($i = 0; $i <= 5; $i++){
			$results['pts_dates'][] = date("Y-m-d", time()-86400*$i);
		}
		foreach ($results['pts_dates'] as $dt) {
			$res = $this->mysql->query("SELECT * FROM (SELECT COUNT(*) as num, SUM(minus_pts) as pts, who, name FROM `syndicate_log` as sl INNER JOIN syndicate_members as sm ON (sm.id = sl.who) WHERE sl.type = 'pts_shop' AND DATE(sl.cdate) = '{$dt}' GROUP BY who WITH ROLLUP) as sl2 ORDER BY sl2.pts DESC, sl2.who");
			$results['pts_shop'][$dt] = [];
			while ($row = $this->mysql->fetch($res)) {
				$results['pts_shop'][$dt][] = $row;
			}

			$res = $this->mysql->query("SELECT * FROM (SELECT COUNT(*) as num, SUM(minus_pts) as pts, who, name FROM `syndicate_log` as sl INNER JOIN syndicate_members as sm ON (sm.id = sl.who) WHERE sl.type = 'pts_syndicat' AND DATE(sl.cdate) = '{$dt}' GROUP BY who WITH ROLLUP) as sl2 ORDER BY sl2.pts DESC, sl2.who");
			$results['pts_syndicat'][$dt] = [];
			while ($row = $this->mysql->fetch($res)) {
				$results['pts_syndicat'][$dt][] = $row;
			}

			$res = $this->mysql->query("SELECT * FROM (SELECT COUNT(*) as num, SUM(minus_pts) as pts, who, name FROM `syndicate_log` as sl INNER JOIN syndicate_members as sm ON (sm.id = sl.who) WHERE sl.type = 'pts_exp' AND DATE(sl.cdate) = '{$dt}' GROUP BY who WITH ROLLUP) as sl2 ORDER BY sl2.pts DESC, sl2.who");
			$results['pts_exp'][$dt] = [];
			while ($row = $this->mysql->fetch($res)) {
				$results['pts_exp'][$dt][] = $row;
			}
		}

		// prr($results, 1);

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
		<script type="text/javascript">
			$(function(){
				var ctx = $("#attacks");
				var myChart = new Chart(ctx, {
				type: 'line',
				data: {
					labels: <?php echo json_encode($results['graph_labels']); ?>,
					datasets: [{
						label: 'Нападения',
						data: <?php echo json_encode(array_values($results['attacks_n'])); ?>,
						backgroundColor: [
							'rgba(255, 99, 132, 0.2)',
						],
						borderColor: [
							'rgba(255,99,132,1)',
						],
						borderWidth: 1
					},
					{
						label: 'Защита <?=$CONFIG['synd']?>',
						data: <?php echo json_encode(array_values($results['defenses_n'])); ?>,
						backgroundColor: [
							'rgba(54, 162, 235, 0.2)',
						],
						borderColor: [
							'rgba(54, 162, 235, 1)',
						],
						borderWidth: 1
					},
					{
						label: 'Защита союз',
						data: <?php echo json_encode(array_values($results['group_defenses_n'])); ?>,
						backgroundColor: [
							'rgba(153, 102, 255, 0.2)',
							'rgba(54, 162, 235, 0.2)',
							'rgba(255, 206, 86, 0.2)',
							'rgba(75, 192, 192, 0.2)',
							'rgba(153, 102, 255, 0.2)',
							'rgba(255, 159, 64, 0.2)'
						],
						borderColor: [
							'rgba(153, 102, 255, 1)',
							'rgba(54, 162, 235, 1)',
							'rgba(255, 206, 86, 1)',
							'rgba(75, 192, 192, 1)',
							'rgba(153, 102, 255, 1)',
							'rgba(255, 159, 64, 1)'
						],
						borderWidth: 1
					}],
				},
				options: {
					title: {
						display: true,
						text: 'Количество атак и защит',
					},
					scales: {
						yAxes: [{
							ticks: {
								beginAtZero:true
							}
						}]
					}
				}
				});


				var ctx2 = $("#pts");
				var myChart = new Chart(ctx2, {
				type: 'line',
				data: {
					labels: <?php echo json_encode($results['graph_labels2']); ?>,
					datasets: [
					{
						label: 'Чистый общий доход PTS',
						data: <?php echo json_encode(array_values($results['profit_pts'])); ?>,
						backgroundColor: [
							'rgba(0, 0, 0, 0.2)',
						],
						borderColor: [
							'rgba(0, 0, 0, 1)',
						],
						borderWidth: 1
					},
					{
						label: 'Расход PTS синдикатом (магаз, звания, СО, нападения)',
						data: <?php echo json_encode(array_values($results['nefit_pts'])); ?>,
						backgroundColor: [
							'rgba(0, 0, 0, 0.2)',
						],
						borderColor: [
							'rgba(0, 0, 0, 1)',
						],
						borderWidth: 1
					},
					{
						label: 'Расход PTS магазин',
						data: <?php echo json_encode(array_values($results['shop_pts'])); ?>,
						backgroundColor: [
							'rgba(220, 227, 38, 0.2)',
						],
						borderColor: [
							'rgba(220, 227, 38, 1)',
						],
						borderWidth: 1
					},
					{
						label: 'Расход PTS синдикат (звания)',
						data: <?php echo json_encode(array_values($results['syndicat_pts'])); ?>,
						backgroundColor: [
							'rgba(14, 230, 217, 0.2)',
						],
						borderColor: [
							'rgba(14, 230, 217, 1)',
						],
						borderWidth: 1
					},
					{
						label: 'Расход PTS С.опыт (перевод)',
						data: <?php echo json_encode(array_values($results['exp_pts'])); ?>,
						backgroundColor: [
							'rgba(230, 151, 14, 0.2)',
						],
						borderColor: [
							'rgba(230, 151, 14, 1)',
						],
						borderWidth: 1
					},
					{
						label: 'Электростанции PTS прибыль',
						data: <?php echo json_encode(array_values($results['control_es_plus_pts'])); ?>,
						backgroundColor: [
							'rgba(255, 99, 132, 0.2)',
						],
						borderColor: [
							'rgba(255, 99, 132, 1)',
						],
						borderWidth: 1
					},
					{
						label: 'Электростанции PTS атаки',
						data: <?php echo json_encode(array_values($results['control_es_minus_pts'])); ?>,
						backgroundColor: [
							'rgba(54, 162, 235, 0.2)',
						],
						borderColor: [
							'rgba(54, 162, 235, 1)',
						],
						borderWidth: 1
					},

					{
						label: 'Уран PTS прибыль',
						data: <?php echo json_encode(array_values($results['control_uran_plus_pts'])); ?>,
						backgroundColor: [
							'rgba(255, 206, 86, 0.2)',
						],
						borderColor: [
							'rgba(255, 206, 86, 1)',
						],
						borderWidth: 1
					},
					{
						label: 'Уран PTS атаки',
						data: <?php echo json_encode(array_values($results['control_uran_minus_pts'])); ?>,
						backgroundColor: [
							'rgba(255, 159, 64, 0.2)',
						],
						borderColor: [
							'rgba(255, 159, 64, 1)',
						],
						borderWidth: 1
					},
					{
						label: 'Остальная недвига PTS прибыль',
						data: <?php echo json_encode(array_values($results['control_other_plus_pts'])); ?>,
						backgroundColor: [
							'rgba(153, 102, 255, 0.2)',
						],
						borderColor: [
							'rgba(153, 102, 255, 1)',
						],
						borderWidth: 1
					},
					{
						label: 'Остальная недвига PTS атаки',
						data: <?php echo json_encode(array_values($results['control_other_minus_pts'])); ?>,
						backgroundColor: [
							'rgba(75, 192, 192, 0.2)',
						],
						borderColor: [
							'rgba(75, 192, 192, 1)',
						],
						borderWidth: 1
					},
					
					],
				},
				options: {
					tooltips: {
						
					},
					title: {
						display: true,
						text: 'PTS',
					},
					/*
					scales: {
						yAxes: [{
							ticks: {
								beginAtZero:true
							}
						}]
					}*/
				}
				});

				var ctx3 = $("#gb");
				var myChart = new Chart(ctx3, {
				type: 'line',
				data: {
					labels: <?php echo json_encode($results['graph_labels2']); ?>,
					datasets: [
					{
						label: 'Чистый общий доход GB',
						data: <?php echo json_encode(array_values($results['profit_gb'])); ?>,
						backgroundColor: [
							'rgba(0, 0, 0, 0.2)',
						],
						borderColor: [
							'rgba(0, 0, 0, 1)',
						],
						borderWidth: 1
					},
					{
						label: 'Электростанции GB +',
						data: <?php echo json_encode(array_values($results['control_es_plus_gb'])); ?>,
						backgroundColor: [
							'rgba(255, 99, 132, 0.2)',
						],
						borderColor: [
							'rgba(255, 99, 132, 1)',
						],
						borderWidth: 1
					},
					{
						label: 'Электростанции GB -',
						data: <?php echo json_encode(array_values($results['control_es_minus_gb'])); ?>,
						backgroundColor: [
							'rgba(54, 162, 235, 0.2)',
						],
						borderColor: [
							'rgba(54, 162, 235, 1)',
						],
						borderWidth: 1
					},

					{
						label: 'Уран GB +',
						data: <?php echo json_encode(array_values($results['control_uran_plus_gb'])); ?>,
						backgroundColor: [
							'rgba(255, 206, 86, 0.2)',
						],
						borderColor: [
							'rgba(255, 206, 86, 1)',
						],
						borderWidth: 1
					},
					{
						label: 'Уран GB -',
						data: <?php echo json_encode(array_values($results['control_uran_minus_gb'])); ?>,
						backgroundColor: [
							'rgba(255, 159, 64, 0.2)',
						],
						borderColor: [
							'rgba(255, 159, 64, 1)',
						],
						borderWidth: 1
					},

					{
						label: 'Остальная недвига GB +',
						data: <?php echo json_encode(array_values($results['control_other_plus_gb'])); ?>,
						backgroundColor: [
							'rgba(153, 102, 255, 0.2)',
						],
						borderColor: [
							'rgba(153, 102, 255, 1)',
						],
						borderWidth: 1
					},
					{
						label: 'Остальная недвига GB -',
						data: <?php echo json_encode(array_values($results['control_other_minus_gb'])); ?>,
						backgroundColor: [
							'rgba(75, 192, 192, 0.2)',
						],
						borderColor: [
							'rgba(75, 192, 192, 1)',
						],
						borderWidth: 1
					},
					
					],
				},
				options: {
					tooltips: {
						
					},
					title: {
						display: true,
						text: 'GB',
					},
					/*
					scales: {
						yAxes: [{
							ticks: {
								beginAtZero:true
							}
						}]
					}*/
				}
				});
			});
		</script>
		<style type="text/css">
			.content {
				padding: 25px;
			}
		</style>
		
		<div class="content">
			<div class="row">
				<div class="col-md-8">
					<canvas id="attacks" style="height: 500; width: 500px;"></canvas>
				</div>
				<div class="col-md-2">
					<p>ТОП 10 атакующих сегодня</p>
					<table class="table">
						<thead>
							<tr>
							<th>Атак</th>
							<th>Ник</th>
							</tr>
						</thead>
					<tbody>
						<?php
							foreach($results['attackers_today'] as $row){
						?>
								<tr>
								<th><?=$row['n']?></th>
								<th><a href="log.php?id=<?=$row['who']?>" target="_blank"><?=$row['name']?></a></th>
								</tr>
						<?php
							}
						?>
					</tbody>
					</table>
				</div>
				<div class="col-md-2">
					<p>ТОП 10 атакующих вчера</p>
					<table class="table">
						<thead>
							<tr>
							<th>Атак</th>
							<th>Ник</th>
							</tr>
						</thead>
					<tbody>
						<?php
							foreach($results['attackers_yesterday'] as $row){
						?>
								<tr>
								<th><?=$row['n']?></th>
								<th><a href="log.php?id=<?=$row['who']?>" target="_blank"><?=$row['name']?></a></th>
								</tr>
						<?php
							}
						?>
					</tbody>
					</table>
				</div>
			</div>
			<div class="row">
				<h2 style="text-align: center;">Учет PTS</h2>
				<br /><br />
				<canvas id="pts" style="height: 500; width: 500px;"></canvas>
			</div>
			<div class="row">
				<h2 style="text-align: center;">Учет ганджубаксов (Гб)</h2>
				<br /><br />
				<canvas id="gb" style="height: 500; width: 500px;"></canvas>	
			</div>
			<br /><br />
			<hr />
			<br /><br />
			<div class="row">
				<h2 style="text-align: center;">Траты PTS в магазине</h2>
				<br /><br />
				<?php
					foreach($results['pts_dates'] as $dt){
				?>
					<div class="col-md-2">
						<p style="text-align: center;"><b><?=$dt?> / <?=$results['pts_shop'][$dt][0]['pts']?> PTS</b></p>
						<table class="table">
							<thead>
								<tr>
								<th>PTS</th>
								<th>Ник</th>
								</tr>
							</thead>
						<tbody>
							<?php
								foreach($results['pts_shop'][$dt] as $i => $row){
									if ($i > 0){
							?>
										<tr>
										<th><?=$row['pts']?></th>
										<th><a href="log.php?id=<?=$row['who']?>" target="_blank"><?=$row['name']?></a></th>
										</tr>
							<?php
									}
								}
							?>
						</tbody>
						</table>
					</div>
				<?php
					}
				?>
			</div>

			<br /><br />
			<hr />
			<br /><br />
			<div class="row">
				<h2 style="text-align: center;">Траты PTS на звания</h2>
				<br /><br />
				<?php
					foreach($results['pts_dates'] as $dt){
				?>
					<div class="col-md-2">
						<p style="text-align: center;"><b><?=$dt?> / <?=$results['pts_syndicat'][$dt][0]['pts']?> PTS</b></p>
						<table class="table">
							<thead>
								<tr>
								<th>PTS</th>
								<th>Ник</th>
								</tr>
							</thead>
						<tbody>
							<?php
								foreach($results['pts_syndicat'][$dt] as $i => $row){
									if ($i > 0){
							?>
										<tr>
										<th><?=$row['pts']?></th>
										<th><a href="log.php?id=<?=$row['who']?>" target="_blank"><?=$row['name']?></a></th>
										</tr>
							<?php
									}
								}
							?>
						</tbody>
						</table>
					</div>
				<?php
					}
				?>
			</div>

			<br /><br />
			<hr />
			<br /><br />
			<div class="row">
				<h2 style="text-align: center;">Траты PTS на перевод синдикатного опыта</h2>
				<br /><br />
				<?php
					foreach($results['pts_dates'] as $dt){
				?>
					<div class="col-md-2">
						<p style="text-align: center;"><b><?=$dt?> / <?=$results['pts_exp'][$dt][0]['pts']?> PTS</b></p>
						<table class="table">
							<thead>
								<tr>
								<th>PTS</th>
								<th>Ник</th>
								</tr>
							</thead>
						<tbody>
							<?php
								foreach($results['pts_exp'][$dt] as $i => $row){
									if ($i > 0){
							?>
										<tr>
										<th><?=$row['pts']?></th>
										<th><a href="log.php?id=<?=$row['who']?>" target="_blank"><?=$row['name']?></a></th>
										</tr>
							<?php
									}
								}
							?>
						</tbody>
						</table>
					</div>
				<?php
					}
				?>
			</div>
		</div>
	</body>
</html>