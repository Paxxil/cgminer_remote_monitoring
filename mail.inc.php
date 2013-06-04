<?php
/*########################################################
SIMPLE CGMINER REMOTE MONITORING SCRIPT WITH ALERTS
Created by: p4xil
Version: 2.0

If you like it please support it with donating:
LTC : LdQ1UHiRy24Tvmm8NHbhAdHL3Qf3JqrUbG
BTC : 1EA8UrpifP9hi7LZHjJphCJQ6Hh45mb5pP
########################################################*/

class send_email
{
	private $from = '';
	private $from_name = '';
	private $reply = '';
	private $reply_name = '';
	private $to = '';
	private $to_name = '';
	private $subject = '';
	private $message = '';
	private $attachment = '';

	public function __construct($to_name, $to, $from_name, $from, $subject, $message, $type = 'plain', $attachment = '', $reply_name = FALSE, $reply = FALSE, $utf8 = TRUE)
	{
		$this -> from = str_replace(array('\n','\r','\t','\r\n'),'',$from);
		$this -> from_name = str_replace(array('\n','\r','\t','\r\n'),'',$from_name);
		$this -> reply = str_replace(array('\n','\r','\t','\r\n'),'',$reply);
		$this -> reply_name = str_replace(array('\n','\r','\t','\r\n'),'',$reply_name);
		$this -> to = str_replace(array('\n','\r','\t','\r\n'),'',$to);
		$this -> to_name = str_replace(array('\n','\r','\t','\r\n'),'',$to_name);
		$this -> subject = str_replace(array('\n','\r','\t','\r\n'),'',$subject);
		$this -> message = $message;
		$this -> type = $type;
		$this -> attachment = $attachment;
		$this -> utf8 = $utf8;
	}

	public function smtpchars($str)
	{
		$len = strlen($str);
		
		if ($len)
		{
			$out = "=?utf-8?Q?";
			
			for( $i=0; $i<$len; $i++ )
			{
				if((ord($str[$i]) & 0x80) || $str[$i]=="=")
					$out .= sprintf("=%02X", ord($str[$i]));
				else
					$out .= $str[$i];
			}
			
			$out .= "?=";
			return $out;
		}
		else
			return "";
	}

	public function mail()
	{
		if( !empty($this -> attachment) )
		{
			$filename = basename($this -> attachment);
			$filesize = filesize($this -> attachment);
			$handle = fopen($this -> attachment, 'r');
			$content = fread($handle, $filesize);
			fclose($handle);
			$content = chunk_split(base64_encode($content));
			
			$uid = md5(uniqid(time()));
			
			if(!$this -> reply) $this -> reply = $this -> from;
			if(!$this -> reply_name) $this -> reply_name = $this -> from_name;

			$header = "MIME-Version: 1.0\n";
			$header .= "From: " . $this -> from_name . " <" . $this -> from . ">\n";
			$header .= "Reply-To: " . $this -> reply_name . " <" . $this -> reply . ">\n";
			$header .= "Content-Type: multipart/mixed; boundary=\"PHP-mixed-" . $uid . "\"";
			
			$message = "--PHP-mixed-" . $uid . "\n";
			$message .= "Content-Type: multipart/alternative; boundary=\"PHP-alt-" . $uid . "\"\n\n";
			
			$message .= "--PHP-alt-" . $uid . "\n";
			$message .= "Content-type:text/" . $this -> type . "; charset=UTF-8\n";
			$message .= "Content-Transfer-Encoding: 8bit\n\n";
			
			$message .= $this -> message . "\n\n";

			$message .= "--PHP-alt-" . $uid . "--\n\n";
			
			$message .= "--PHP-mixed-" . $uid . "\n";
			$message .= "Content-Type: application/octet-stream; name=\"" . $filename . "\"\n";
			$message .= "Content-Transfer-Encoding: base64\n";
			$message .= "Content-Disposition: attachment; filename=\"" . $filename . "\"\n\n";
			
			$message .= $content . "\n\n";
			
			$message .= "--PHP-mixed-" . $uid . "--\n";

			if ($this -> utf8)
			{
				$this -> subject = $this -> smtpchars($this -> subject);
			}

			if( mail($this -> to_name . " <" . $this -> to . ">", $this -> subject, $message, $header, "-f" . $this -> from) )
				return true;
			else
			  return false;
		}
		else
		{
			$boundary = md5(uniqid(time()));

			if(!$this -> reply) $this -> reply = $this -> from;
			if(!$this -> reply_name) $this -> reply_name = $this -> from_name;

			$header = "MIME-Version: 1.0\n";
			$header .= "Content-Transfer-Encoding: 8bit\n";
			// $header .= "Content-type: text/". $this -> type ."; charset=UTF-8\n";
			$header .= "Content-Type: multipart/alternative; boundary= " . $boundary . "\n";
			$header .= "X-Mailer: PHP/" . phpversion() . "\n";
			$header .= "From: " . $this -> from_name . " <" . $this -> from . ">\n";
			$header .= "Reply-To: " . $this -> reply_name . " <" . $this -> reply . ">\n";

			$msg = "This is a MIME encoded message.\n\n";
			$msg .= "--" . $boundary . "\n";
			$msg .= "Content-Type: text/plain; charset=UTF-8\n";
			$msg .= "Content-Transfer-Encoding: 8bit\n\n";
			$msg .= strip_tags($this -> message) . "\n\n";
			$msg .= "--" . $boundary . "\n";
			$msg .= "Content-Type: text/html; charset=UTF-8\n";
			$msg .= "Content-Transfer-Encoding: 8bit\n\n";
			$msg .= $this -> message . "\n\n";
			$msg .= "--" . $boundary . "--\n";

			if ($this -> utf8)
			{
				$this -> subject = $this -> smtpchars($this -> subject);
			}

			if(mail($this -> to_name . " <" . $this -> to . ">", $this -> subject, $msg, $header, "-f" . $this -> from))
				return true;
			else
				return false;
		}
	}
}
?>
