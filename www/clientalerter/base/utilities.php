<?

//********************************************************************************************
//Author: Sergey Stoyan, CliverSoft.com
//        http://cliversoft.com
//        stoyan@cliversoft.com
//        sergey.stoyan@gmail.com
//        27 February 2007
//Copyright: (C) 2007, Sergey Stoyan
//********************************************************************************************

class Utilities
{
	static public function GetQueryStringFromAll()
	{
		foreach($_REQUEST as $k => $v) $str .= ($str ? "&" : "")."$k=$v";
		return $str;
	}

	static public function GetCurrentUrl()
	{
		if(empty($_SERVER["HTTPS"])) $url = "http";
		else $url = 'https';
		$url .= "://";
		if($_SERVER["SERVER_PORT"] != "80") $url .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
		else $url .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
		return $url;
	}

	static public function GetCurrentPage()
	{
		preg_match("@(.*?)(\?|\#|$)@is", $_SERVER["REQUEST_URI"], $res);
		return ltrim($res[1], "/");
	}

	static public function GetCurrentFile()
	{
		preg_match("@.*/(.*?)(\?|\#|$)@is", $_SERVER["REQUEST_URI"], $res);
		return ltrim($res[1], "/");
	}

	static public function PrintAsCsv($sql)
	{
		$result = mysql_query($sql) or die("Query failed: $sql\n".mysql_error());
		$cs = mysql_num_fields($result);
		for($i = 0; $i < $cs; $i++)
		{
			$cn = mysql_field_name($result, $i);
			$head[] = $cn;
		}
		print(self::safe_join_csv($head)."\r\n");
		while($r = mysql_fetch_assoc($result)) print(self::safe_join_csv(array_values($r))."\r\n");
	}

	static function safe_join_csv($array)
	{
		$r = "";
		foreach($array as $v)
		{
			$v = preg_replace('/[\n\r\t,]/is', ' ', $v);
			$v = preg_replace('/\s\s+/is', ' ', $v);
			$v = trim($v);
			if($r) $r .= ",".$v;
			else $r = $v;
		}
		return $r;
	}

	static public function PrintAsTab($sql)
	{
		$result = mysql_query($sql) or die("Query failed: $sql\n".mysql_error());
		$cs = mysql_num_fields($result);
		for($i = 0; $i < $cs; $i++)
		{
			$cn = mysql_field_name($result, $i);
			$head[] = $cn;
		}
		print(self::safe_join_tab($head)."\r\n");
		while($r = mysql_fetch_assoc($result)) print(self::safe_join_tab(array_values($r))."\r\n");
	}

	static function safe_join_tab($array)
	{
		$r = "";
		foreach($array as $v)
		{
			$v = preg_replace('/[\n\r\t]/is', ' ', $v);
			$v = preg_replace('/\s\s+/is', ' ', $v);
			$v = trim($v);
			if($r) $r .= "\t".$v;
			else $r = $v;
		}
		return $r;
	}

	static public function BuildSuccessorRequest($_get_merge=false, $_get_substruct=false)
	{
		if($_get_merge) $_get = array_merge($_GET, $_get_merge);
		else $_get = $_GET;
		if($_get_substruct)	foreach($_get_substruct as $name=>$value) unset($_get[$name]);
       	return http_build_query($_get);
	}

	static public function BuildCurrentUrlWithSuccessorRequest($_get_merge=false, $_get_substruct=false)
	{
		preg_match("@(.*?)(\?|\#|$)@is", self::GetCurrentUrl(), $res);
		return $res[1]."?".self::BuildSuccessorRequest($_get_merge, $_get_substruct);
	}

	static public function IsPageCalledFirst()
	{
		$referer = getenv("HTTP_REFERER");
		return (!$referer or !strstr($referer, self::GetCurrentPage()));
	}

	static function GetFilesInDir($directory)
	{
		$files = array();
    	$handler = opendir($directory);
	    while($file = readdir($handler))
    	    if($file != '.' && $file != '..') $files[] = $file;//$files[$file] = "";
	    closedir($handler);
    	return $files;
	}

	static function ArrayInsert2ArrayByKey($arr1, $key, $arr2, $before = FALSE)
	{
		$index = array_search($key, array_keys($arr1));
		if($index === FALSE) $index = 0;
		elseif(!$before) $index++;
		$end = array_splice($arr1, $index);
		return array_merge($arr1, $arr2, $end);
	}
}

?>