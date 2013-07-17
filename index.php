<?php
/*########################################################
SIMPLE CGMINER REMOTE MONITORING SCRIPT WITH ALERTS
Created by: p4xil
Version: 2.0

If you like it please support it with donating:
LTC : LdQ1UHiRy24Tvmm8NHbhAdHL3Qf3JqrUbG
BTC : 1EA8UrpifP9hi7LZHjJphCJQ6Hh45mb5pP
########################################################*/

include_once ('./functions.inc.php');

$nr_rigs = count($r);

for ($i=0; $i<$nr_rigs; $i++)
{
	$r[$i]['summary'] = request('summary', $r[$i]['ip'], $r[$i]['port']);
	if ($r[$i]['summary'] != null)
	{
		$r[$i]['devs']  = request('devs',  $r[$i]['ip'], $r[$i]['port']);
		$r[$i]['stats'] = request('stats', $r[$i]['ip'], $r[$i]['port']);
		$r[$i]['pools'] = request('pools', $r[$i]['ip'], $r[$i]['port']);
		$r[$i]['coin']  = request('coin',  $r[$i]['ip'], $r[$i]['port']);
	}
}
?>

<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<title>Cgminer Monitoring Status</title>
	<meta http-equiv="refresh" content="<?php echo SCRIPT_REFRESH?>; URL=<?php echo SCRIPT_URL?>">
</head>

<body>

<style>
	body {font: 100% arial;}
	.error {color:white; background:red;}
	.ok {color:green;}
</style>

<table border=1>
	<tr>
		<th colspan="10" style="background:#ccc;">CGMINER MONITORING STATUS</th>
	</tr>
	<tr>
		<th style="width:150px;">Miner</th>
		<th style="width:120px;">Status</th>
		<th style="width:120px;">Uptime</th>
		<th style="width:100px;">MH/s</th>
		<th style="width:80px;">A</th>
		<th style="width:80px;">R</th>
		<th style="width:50px;">HW</th>
		<th style="width:100px;">Invalid</th>
		<th style="width:120px;">WU</th>
		<th style="width:120px;">WU ratio</th>
	</tr>
	<?php
	$hash_sum          = 0;
	$a_sum             = 0;
	$r_sum             = 0;
	$hw_sum            = 0;
	$wu_sum            = 0;
	$invalid_sum_ratio = 0;

	for ($i=0; $i<$nr_rigs; $i++)
	{
		$r[$i]['summary']['STATUS']['STATUS']           = isset($r[$i]['summary']['STATUS']['STATUS'])           ? $r[$i]['summary']['STATUS']['STATUS']           : 'OFFLINE';
		$r[$i]['summary']['SUMMARY']['MHS av']          = isset($r[$i]['summary']['SUMMARY']['MHS av'])          ? $r[$i]['summary']['SUMMARY']['MHS av']          : 0;
		$r[$i]['summary']['SUMMARY']['Accepted']        = isset($r[$i]['summary']['SUMMARY']['Accepted'])        ? $r[$i]['summary']['SUMMARY']['Accepted']        : 0;
		$r[$i]['summary']['SUMMARY']['Rejected']        = isset($r[$i]['summary']['SUMMARY']['Rejected'])        ? $r[$i]['summary']['SUMMARY']['Rejected']        : 0;
		$r[$i]['summary']['SUMMARY']['Hardware Errors'] = isset($r[$i]['summary']['SUMMARY']['Hardware Errors']) ? $r[$i]['summary']['SUMMARY']['Hardware Errors'] : 0;
		$r[$i]['summary']['SUMMARY']['Work Utility']    = isset($r[$i]['summary']['SUMMARY']['Work Utility'])    ? $r[$i]['summary']['SUMMARY']['Work Utility']    : 0;
		$r[$i]['stats']['STATS0']['Elapsed']            = isset($r[$i]['stats']['STATS0']['Elapsed'])            ? $r[$i]['stats']['STATS0']['Elapsed']            : 'N/A';
		$r[$i]['coin']['COIN']['Hash Method']           = isset($r[$i]['coin']['COIN']['Hash Method'])           ? $r[$i]['coin']['COIN']['Hash Method']           : 'sha256';

		$invalid_ratio = 0;
		$wu_ratio      = 0;

		if (($r[$i]['summary']['SUMMARY']['Accepted'] + $r[$i]['summary']['SUMMARY']['Rejected']) > 0)
		{
			$invalid_ratio = round(($r[$i]['summary']['SUMMARY']['Rejected'] / ($r[$i]['summary']['SUMMARY']['Accepted'] + $r[$i]['summary']['SUMMARY']['Rejected'])) * 100,2);
		}

		if ($r[$i]['stats']['STATS0']['Elapsed'] == 'N/A')
		{
			$running = 'N/A';
		}
		else
		{
			$t = seconds_to_time($r[$i]['stats']['STATS0']['Elapsed']);
			$running = $t['d'] . 'd ' . $t['h'] . ':' . $t['m'] . ':' . $t['s'];
		}

		if ($r[$i]['summary']['SUMMARY']['MHS av'] > 0)
		{
			$wu_ratio = round($r[$i]['summary']['SUMMARY']['Work Utility'] / ($r[$i]['summary']['SUMMARY']['MHS av']*1000),3);
			if ($wu_ratio < 0.9 && $t['d']>=1)
			{
				$wu_ratio = '<span class="error">' . $wu_ratio . '</span>';
			}
		}
		
		$hash_sum = $hash_sum + $r[$i]['summary']['SUMMARY']['MHS av'];
		$a_sum    = $a_sum    + $r[$i]['summary']['SUMMARY']['Accepted'];
		$r_sum    = $r_sum    + $r[$i]['summary']['SUMMARY']['Rejected'];
		$hw_sum   = $hw_sum   + $r[$i]['summary']['SUMMARY']['Hardware Errors'];
		$wu_sum   = $wu_sum   + $r[$i]['summary']['SUMMARY']['Work Utility'];

		?>
		<tr>
			<td><?php echo $r[$i]['name']?></td>
			<td style="text-align:center"><?php echo $r[$i]['summary']['STATUS']['STATUS'] == 'S' ? '<span class="ok">ONLINE</span>' : '<span class="error">OFFLINE</span>' ?></td>
			<td style="text-align:center"><?php echo $running?></td>
			<td style="text-align:center"><?php echo $r[$i]['summary']['SUMMARY']['MHS av']?></td>
			<td style="text-align:center"><?php echo $r[$i]['summary']['SUMMARY']['Accepted']?></td>
			<td style="text-align:center"><?php echo $r[$i]['summary']['SUMMARY']['Rejected']?></td>
			<td style="text-align:center"><?php echo $r[$i]['summary']['SUMMARY']['Hardware Errors'] == 0 ? '<span class="ok">0</span>' : '<span class="error">' . $r[$i]['summary']['SUMMARY']['Hardware Errors'] . '</span>' ?></td>
			<td style="text-align:center"><?php echo $invalid_ratio <= ALERT_STALES  ? $invalid_ratio . '%' : '<span class="error">' . $invalid_ratio . '%</span>' ?></td>
			<td style="text-align:center"><?php echo $r[$i]['summary']['SUMMARY']['Work Utility']?></td>
			<td style="text-align:center"><?php echo $wu_ratio?></td>
		</tr>
		<?php
	}

	if ($a_sum > 0)
	{
		$invalid_sum_ratio = round(($r_sum / $a_sum) * 100, 2);
	}

	?>
	<tr style="font-weight:bold;">
		<td colspan="3"></td>
		<td style="text-align:center;"><?php echo $hash_sum?></td>
		<td style="text-align:center;"><?php echo $a_sum?></td>
		<td style="text-align:center;"><?php echo $r_sum?></td>
		<td style="text-align:center;"><?php echo $hw_sum == 0 ? '<span class="ok">0</span>' : '<span class="error">' . $hw_sum . '</span>' ?></td>
		<td style="text-align:center"><?php echo $invalid_sum_ratio <= 5  ? $invalid_sum_ratio . '%' : '<span class="error">' . $invalid_sum_ratio . '%</span>' ?></td>
		<td style="text-align:center"><?php echo $wu_sum?></td>
		<td colspan="3"></td>
	</tr>
</table>
<br><br>

<?php
for ($i=0; $i<$nr_rigs; $i++)
{
	$pool_priority = 999;
	foreach ($r[$i]['pools'] as $pool)
	{
		if (($pool['Status'] == 'Alive') && ($pool['Priority'] < $pool_priority))
		{
			$pool_priority = $pool['Priority'];
			$pool_active = '<span style="font-weight:normal">Pool ' . $pool['POOL'] . ' - ' . $pool['URL'] . ', user - ' . $pool['User'] . '</span>';
		}
	}
	?>
	<table border="1">
		<tr>
			<th colspan="10" style="background:#ccc;"><?php echo $r[$i]['name']?><br><?php echo $pool_active;?></th>
		</tr>
		<tr>
			<th style="width:50px;">GPU</th>
			<th style="width:120px;">Status</th>
			<th style="width:80px;">Temp</th>
			<th style="width:70px;">Fan</th>
			<th style="width:150px;"><?php echo $r[$i]['coin']['COIN']['Hash Method'] == 'scrypt' ? 'KH/s' : 'MH/s'?> (5s | avg)</th>
			<th style="width:70px;">A</th>
			<th style="width:70px;">R</th>
			<th style="width:50px;">HW</th>
			<th style="width:100px;">Invalid</th>
			<th style="width:200px;">Last Work</th>
		</tr>
		<?php
		if (isset ($r[$i]['devs']))
		{
			$j = 0;
			$k = count($r[$i]['devs']);
			foreach ($r[$i]['devs'] as $gpu)
			{
				if ($j > 0 && $j < $k)
				{
					$invalid_ratio = round(($gpu['Rejected'] / ($gpu['Accepted'] + $gpu['Rejected'])) * 100,2);
					?>
					<tr>
						<td style="text-align:center"><?php echo $gpu['GPU'] ?></td>
						<td style="text-align:center"><?php echo $gpu['Status'] == 'Alive' ? '<span class="ok">' . $gpu['Status'] . '</span>' : '<span class="error">' . $gpu['Status'] . '</span>' ?></td>
						<td style="text-align:center"><?php echo $gpu['Temperature'] > ALERT_TEMP ? '<span class="error">' . round($gpu['Temperature']) . '°C</span>' : round($gpu['Temperature']) . '°C' ?></td>
						<td style="text-align:center"><?php echo $gpu['Fan Percent']?>%</td>
						<td style="text-align:center">
							<?php
							$stats_second = isset ($gpu['MHS 5s']) ? $gpu['MHS 5s'] : (isset ($gpu['MHS 2s']) ? $gpu['MHS 2s'] : FALSE);
							if (100 - (($stats_second / $gpu['MHS av']) * 100) >= ALERT_MHS)
							{
								echo '<span class="error">' . ($r[$i]['coin']['COIN']['Hash Method'] == 'scrypt' ? $stats_second * 1000 . ' | ' . $gpu['MHS av'] * 1000 : $stats_second . ' | ' . $gpu['MHS av']) . '</span>';
							}
							else
							{
								echo ($r[$i]['coin']['COIN']['Hash Method'] == 'scrypt' ? $stats_second * 1000 . ' | ' . $gpu['MHS av'] * 1000 : $stats_second . ' | ' . $gpu['MHS av']);
							}
							?>
						</td>
						<td style="text-align:center"><?php echo $gpu['Accepted']?></td>
						<td style="text-align:center"><?php echo $gpu['Rejected']?></td>
						<td style="text-align:center"><?php echo $gpu['Hardware Errors'] == 0  ? '<span class="ok">0</span>' : '<span class="error">' . $gpu['Hardware Errors'] . '</span>' ?></td>
						<td style="text-align:center"><?php echo $invalid_ratio <= ALERT_STALES  ? $invalid_ratio . '%' : '<span class="error">' . $invalid_ratio . '%</span>' ?></td>
						<td style="text-align:center"><?php echo date('Y-m-d H:i:s', $gpu['Last Valid Work']) ?></td>
					</tr>
					<?php
				}
				$j++;
			}
		}
		else
		{
			?>
			<tr>
				<td colspan="10" style="text-align:center" class="error">OFFLINE</td>
			</tr>
			<?php
		}
		?>
	</table>
	<br>
	<?php
}
?>
</body>
</html>
