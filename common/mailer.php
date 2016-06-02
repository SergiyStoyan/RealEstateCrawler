<?
include_once("logger.php");

class Mailer
{
	static function decode($data, $encoding)
	{
		if($encoding == 0) return $data;
		elseif($encoding == 1) return imap_8bit($data);
		elseif($encoding == 2) return imap_binary($data);
		elseif($encoding == 3) return imap_base64($data);
		elseif($encoding == 4) return imap_qprint($data);
		elseif($encoding == 5) return imap_base64($data);
		else return $data;
	}

	public static function ProcessAttachments1($connection, $login, $password, $process_attachments)
	{
		$process_attachments = split("::", $process_attachments);
		$mbox = imap_open($connection, $login, $password) or die("Can't connect: ".imap_last_error());
		//$message = array();
		//$message["attachment"]["type"] = array("text", "multipart", "message", "application", "audio", "image", "video", "other");

		foreach(imap_search($mbox, 'ALL') as $j)
		{
			$structure = imap_fetchstructure($mbox, $j , FT_UID);
			if(!property_exists($structure, "parts")) continue;
			$parts = $structure->parts;
			$attachments = array();
			$fpos = 2;
			for($i = 1; $i < count($parts); $i++)
			{
				$part = $parts[$i];
                if($part->disposition != "ATTACHMENT") continue;
				$filename = $part->dparameters[0]->value;
			  	$body = imap_fetchbody($mbox, $j, $fpos);
				$data = self::decode($body, $part->type);
				$attachments[$filename] = $data;
				$fpos++;
			}
			if(call_user_func($process_attachments, $attachments)) imap_delete($mbox, $j);
		}
		imap_expunge($mbox);
		imap_close($mbox);
	}

	public static function ProcessAttachments($connection, $login, $password, $process_attachments, $filter_attachment=NULL)
	{
		$filter_attachment = split("::", $filter_attachment);
		$process_attachments = split("::", $process_attachments);
		$mbox = imap_open($connection, $login, $password) or die("Can't connect: ".imap_last_error());

		$ms = imap_search($mbox, 'UNSEEN', SE_UID);
		if(!$ms) return;
		//foreach(imap_search($mbox, 'ALL', SE_UID) as $j)      //FT_PEEK
		foreach($ms as $j)
		{
			$structure = imap_fetchstructure($mbox, $j , FT_UID | FT_PEEK);
			if(!property_exists($structure, "parts"))
			{				imap_setflag_full($mbox, $j, "\\Seen", ST_UID);
				continue;
			}
			$parts = $structure->parts;
			$attachments = array();
			$fpos = 1;
			for($i = 1; $i < count($parts); $i++)
			{
				$fpos++;
				$part = $parts[$i];
				if(property_exists($part, "ifdparameters") and $part->ifdparameters > 0) $filename = $part->dparameters[0]->value;
				elseif(property_exists($part, "ifparameters") and $part->ifparameters > 0) $filename = $part->parameters[0]->value;
				else continue;
				if($filter_attachment and !call_user_func($filter_attachment, $filename)) continue;
			  	$data = imap_fetchbody($mbox, $j, $fpos, FT_UID | FT_PEEK);
				$data = self::decode($data, $part->type);//encoding); type
				$attachments[$filename] = $data;
			}
			if(count($attachments) < 1 or call_user_func($process_attachments, $attachments))
			{
				imap_setflag_full($mbox, $j, "\\Seen", ST_UID);//imap_delete($mbox, $j);
				//print("<br>--".$j."<br>");
			}
		}
		imap_expunge($mbox);
		imap_close($mbox);
	}
}

/*
function save1($as)
{
	print(count($as));	foreach($as as $n=>$d)
	{		$f = fopen("c:\\temp\\$n", "w");
		fwrite($f, $d);
		fclose($f);	}
	return false;}

Mailer::ProcessAttachmentsInPOP3("{mail.cliversoft.com:110/pop3/notls}INBOX", "test+cliversoft.com", "qwerty", "save1");
*/
?>
