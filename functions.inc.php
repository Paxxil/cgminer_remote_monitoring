<?php
/*########################################################
SIMPLE CGMINER REMOTE MONITORING SCRIPT WITH ALERTS
Created by: p4xil
Version: 2.0

If you like it please support it with donating:
LTC : LdQ1UHiRy24Tvmm8NHbhAdHL3Qf3JqrUbG
BTC : 1EA8UrpifP9hi7LZHjJphCJQ6Hh45mb5pP
########################################################*/

/*--------------
# SETUP MINERS #
--------------*/

// Change YOUR_RIG_REMOTE_IP to your rig REMOTE IP (NOT local IP!)
// To get your remote IP go to http://www.whatismyip.com/
// Uncomment (remove //) if you want to add more miners.
$r[0]['name'] = 'MINER1';
$r[0]['ip'] = 'YOUR_RIG_REMOTE_IP'; 
$r[0]['port'] = '4001';

//$r[1]['name'] = 'MINER2';
//$r[1]['ip'] = 'YOUR_RIG_REMOTE_IP';
//$r[1]['port'] = '4002';

//$r[2]['name'] = 'MINER3';
//$r[2]['ip'] = 'YOUR_RIG_REMOTE_IP';
//$r[2]['port'] = '4003';

//$r[3]['name'] = 'MINER3';
//$r[3]['ip'] = 'YOUR_RIG_REMOTE_IP';
//$r[3]['port'] = '4004';

/*--------------
# SETUP SCRIPT #
--------------*/

// URL to the script
// Change WEBSERVER_IP to your Webserver IP or Webserver Domain if you have it.
define('SCRIPT_URL', 'http://WEBSERVER_IP/monitoring/');

// Time in Seconds to Auto Refresh the script
define('SCRIPT_REFRESH', 20);

/*--------------
# SETUP ALERTS #
--------------*/

// E-Mail for Alerts
define('ALERT_EMAIL', 'your@email.com');

// Maximum allowed GPU Temperature before Alert is sent
define('ALERT_TEMP', 75);

// Maximum allowed difference in percentage (%) between 5s MH/s and (avg) MH/s before Alert is sent.
// Formula : DIFFERENCE = 100 - ((5s / avg) * 100)
//
// e.g. If (avg) is 500 and 5s drops to 400 the difference will be:
// DIFFERENCE = 100 - ((400 / 500) * 100) = 20%
// If ALERT_MHS is set to 20, Alert will be sent.
define('ALERT_MHS', 20);

/*#####################################################################################*/
/*# DO NOT EDIT BELOW THIS LINE #######################################################*/
/*#####################################################################################*/

/* Connection Timeout in Seconds */
define('SOCK_TIMEOUT', '3');
ini_set('default_socket_timeout', SOCK_TIMEOUT);

/* Script Allowed Execution Time */
set_time_limit(0);

function getsock($addr, $port)
{
	$socket = null;
	$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

	if ($socket === false || $socket === null)
	{
		$error = socket_strerror(socket_last_error());
		$msg = "socket create(TCP) failed";
		//echo "ERR: $msg '$error'\n";
		return null;
	}

	socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array('sec' => SOCK_TIMEOUT, 'usec' => 0));
	socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, array('sec' => SOCK_TIMEOUT, 'usec' => 0)); 

	$res = socket_connect($socket, $addr, $port);
	if ($res === false)
	{
		$error = socket_strerror(socket_last_error());
		$msg = "socket connect($addr,$port) failed";
		//echo "ERR: $msg '$error'\n";
		socket_close($socket);
		return null;
	}

	return $socket;
}

function readsockline($socket)
{
	$line = '';
	while (true)
	{
		$byte = socket_read($socket, 1);
		if ($byte === false || $byte === '')
			break;
		if ($byte === "\0")
			break;
		$line .= $byte;
	}
	return $line;
}

function request($cmd, $ip, $port)
{
	$socket = getsock($ip, $port);
	
	if ($socket != null)
	{
		socket_write($socket, $cmd, strlen($cmd));
		$line = readsockline($socket);
		socket_close($socket);

		if (strlen($line) == 0)
		{
			//echo "WARN: '$cmd' returned nothing\n";
			return $line;
		}

		if (substr($line,0,1) == '{')
			return json_decode($line, true);

		$data = array();

		$objs = explode('|', $line);
		foreach ($objs as $obj)
		{
			if (strlen($obj) > 0)
			{
				$items = explode(',', $obj);
				$item = $items[0];
				$id = explode('=', $items[0], 2);
				if (count($id) == 1 or !ctype_digit($id[1]))
					$name = $id[0];
				else
					$name = $id[0].$id[1];

				if (strlen($name) == 0)
					$name = 'null';

				if (isset($data[$name]))
				{
					$num = 1;
					while (isset($data[$name.$num]))
						$num++;
					$name .= $num;
				}

				$counter = 0;
				foreach ($items as $item)
				{
					$id = explode('=', $item, 2);
					if (count($id) == 2)
						$data[$name][$id[0]] = $id[1];
					else
						$data[$name][$counter] = $id[0];

					$counter++;
				}
			}
		}

		return $data;
	}

	return null;
}

function seconds_to_time($input_seconds)
{
	$seconds_in_minute = 60;
	$seconds_in_hour   = 60 * $seconds_in_minute;
	$seconds_in_day    = 24 * $seconds_in_hour;

	// extract days
	$days = floor($input_seconds / $seconds_in_day);

	// extract hours
	$hour_seconds = $input_seconds % $seconds_in_day;
	$hours = floor($hour_seconds / $seconds_in_hour);

	// extract minutes
	$minute_seconds = $hour_seconds % $seconds_in_hour;
	$minutes = floor($minute_seconds / $seconds_in_minute);

	// extract the remaining seconds
	$remaining_seconds = $minute_seconds % $seconds_in_minute;
	$seconds = ceil($remaining_seconds);

	// return the final array
	$obj = array(
		'd' => (int)$days,
		'h' => sprintf('%02d', (int)$hours),
		'm' => sprintf('%02d', (int)$minutes),
		's' => sprintf('%02d', (int)$seconds)
	);
	return $obj;
}
?>
