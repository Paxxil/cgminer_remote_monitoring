#!/usr/bin/php
<?php
/*########################################################
SIMPLE CGMINER REMOTE MONITORING SCRIPT WITH ALERTS
Created by: p4xil
Version: 2.0

If you like it please support it with donating:
LTC : LdQ1UHiRy24Tvmm8NHbhAdHL3Qf3JqrUbG
BTC : 1EA8UrpifP9hi7LZHjJphCJQ6Hh45mb5pP
########################################################*/

$path = isset ($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : FALSE;
if (!$path)
{
	$path = explode('/', $_SERVER['PHP_SELF']);
	array_pop($path);
	$path = implode('/', $path);
}

include_once ($path . '/functions.inc.php');
include_once ($path . '/mail.inc.php');

$nr_rigs = count($r);

for ($i=0; $i<$nr_rigs; $i++)
{
	$r[$i]['summary'] = request('summary', $r[$i]['ip'], $r[$i]['port']);
	if ($r[$i]['summary'] != null)
	{
		$r[$i]['devs'] = request('devs', $r[$i]['ip'], $r[$i]['port']);
	}
}

$error = FALSE;
$subject = 'PROBLEM: ';
$fn = $path . '/email.lock';

for ($i=0; $i<$nr_rigs; $i++)
{
	$status = isset($r[$i]['summary']['STATUS']['STATUS']) ? $r[$i]['summary']['STATUS']['STATUS'] : FALSE;
	if ($status != 'S')
	{
		sleep(10);

		$r[$i]['summary'] = request('summary', $r[$i]['ip'], $r[$i]['port']);

		$status = isset($r[$i]['summary']['STATUS']['STATUS']) ? $r[$i]['summary']['STATUS']['STATUS'] : FALSE;

		if ($status != 'S')
		{
			$error .= $r[$i]['name'] . " Down\r\n<br>";
			$subject .= 'M' . $i . ' ';
		}
	}
}

for ($i=0; $i<$nr_rigs; $i++)
{
	if (isset ($r[$i]['devs']))
	{
		$j = 0;
		$k = count($r[$i]['devs']);
		foreach ($r[$i]['devs'] as $gpu)
		{
			if ($j > 0 && $j < $k)
			{
				if ($gpu['Status'] != 'Alive')
				{
					$error .= $r[$i]['name'] . '  - GPU ' . $j . " Down\r\n<br>";
					$subject .= 'M' . $i . ' - G' . $j . ' ';
				}
				else if ($gpu['Temperature'] >= ALERT_TEMP)
				{
					$error .= $r[$i]['name'] . '  - GPU ' . $j . ' Temp High (' . round($gpu['Temperature']) . "C)\r\n<br>";
					$subject .= 'M' . $i . ' - G' . $j . ' (' . round($gpu['Temperature']) . 'C) ';
				}
				else if (100 - (($gpu['MHS 5s'] / $gpu['MHS av']) * 100) >= ALERT_MHS)
				{
					$error .= $r[$i]['name'] . ' GPU ' . $j . ' ' . $gpu['MHS 5s'] . " MH/s\r\n<br>";
					$subject .= 'M' . $i . ' - G' . $j . ' (' . $gpu['MHS 5s'] . ' MH/s) ';
				}
			}
			$j++;
		}
	}
}

if ($error)
{
	if (filesize($fn) == 0)
	{
		file_put_contents($fn, $error);
		$mail = new send_email('', ALERT_EMAIL, 'Monitoring', ALERT_EMAIL, $subject, $error, 'html', '', FALSE, FALSE, FALSE);
		$mail -> mail();
	}
}
else
{
	if (filesize($fn) > 0)
	{
		file_put_contents($fn, '');
	}
}
?>
