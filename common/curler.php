<?
//********************************************************************************************
//Author: Sergey Stoyan, CliverSoft.com
//        http://cliversoft.com
//        stoyan@cliversoft.com
//        sergey.stoyan@gmail.com
//        27 February 2007
//Copyright: (C) 2007, Sergey Stoyan
//********************************************************************************************

include_once("logger.php");

class Curler{
	public $ProxyFile = false;
	public $ProxyLogin = false;
	public $ProxyPassword = false;
	public static $RequestDelayInMss = 0;  //milliseconds.
	public $StoreFiles2Disk = true;
	public $UseCachedFiles = true;
	public $UseCacheMapInDB = false;
	public $UserAgent = "";//"Mozilla/5.0";
	public $TimeoutInSecs = 60; //seconds
	//public $AcceptTextHeader = "text/xml,text/html;q=0.9,text/plain;q=0.8;";
	//public $AcceptBinaryHeader = "text/xml,text/html;q=0.9,text/plain;q=0.8;";
	public $MaxDownloadedLength = 2000000;
	public $AllowRedirection = true;
	public $UseCookie = true;
	public $IgnoreSSLCertificate = false;
	public $ResponseUrl;
	public $ResponseHttpCode;
	public $ResponseCached;
	public $ReadHeaderCallback = null; //if defined, must return true to proceed
	public $AdditionalHeaders = false;

	private $cache_dir;

	public function __construct($store_files2disk=true, $use_cached_files=true, $cache_dir=null, $use_cookie=true)
	{
		if(!$cache_dir) $cache_dir = dirname(__FILE__)."/_cache";
		$this->StoreFiles2Disk = $store_files2disk;
		$this->UseCachedFiles = $use_cached_files;
		if($cache_dir) $this->cache_dir = $cache_dir;
		if(/*$this->StoreFiles2Disk and */!file_exists($this->cache_dir)) mkdir($this->cache_dir, 0777, true) or die("Could not create a cache dir:".$this->cache_dir);
		$this->start_time_str = date("YmdHis");
		$this->UseCookie = $use_cookie;
	}

	private function escape($url)
	{
		return str_replace("%2F", "/", urlencode($url));
    }

	public function ClearCookies()
	{
		if(unlink($this->cache_dir.'/cookies.txt')) Logger::Write("Cookies were deleted.");
	}
	
	function GetBinary($url, $send_cookie=null)
	{
		$this->accepted_content_type_pattern = "@.*@";
		return $this->download($url, $send_cookie, true);
	}

	public function GetPage($url, $send_cookie=null)
	{
		$this->accepted_content_type_pattern = "@.*text.*@";
		return $this->download($url, $send_cookie, false);
	}
	
	public function PostPage($url, $parameters, $send_cookie=null)
	{
		$this->accepted_content_type_pattern = "@.*text.*@";
		return $this->download($url, $send_cookie, false, $parameters);
	}
	
	function download($url, $send_cookie=null, $binary=false, $parameters=null)
	{
		$this->ResponseUrl = null;
		$this->ResponseHttpCode = 0;
		$this->ResponseCached = false;

		if($send_cookie === null) $send_cookie = $this->UseCookie;
		
		$url = str_replace(" ", "%20", $url);
		
		if($parameters === null)
		{
			if($this->UseCachedFiles)
			{
				if($html = $this->get_cached_file($url))
				{
					Logger::Write("FROM CACHE: $url");	
					$this->ResponseCached = true;
					$this->ResponseHttpCode = 200;
					return $html;
				}
			}		
			
			Logger::Write("GETTING: $url");	
			
			$headers = array(
				//Some servers (like Lighttpd) will not process the curl request without this header and will return error code 417 instead.
				//Apache does not need it, but it is safe to use it there as well.
				"Expect:",
				//"Accept: ".$this->AcceptHeader
			);
		}
		else
		{
			$post_string = http_build_query($parameters);
		
			if($this->UseCachedFiles)
			{
				if($html = $this->get_cached_file($url."|".$post_string))
				{
					Logger::Write("FROM CACHE: ".$url."|".$post_string);
					$this->ResponseCached = true;
					$this->ResponseHttpCode = 200;
					return $html;
				}
			}		
			
			Logger::Write("POSTING: $url\n$post_string");
		
			$headers = array(
				"Content-Type: application/x-www-form-urlencoded",
				"Content-length: ".strlen($post_string),
				//Some servers (like Lighttpd) will not process the curl request without this header and will return error code 417 instead.
				//Apache does not need it, but it is safe to use it there as well.
				"Expect:",
				//"Accept: ".$this->AcceptHeader
			);		
		}

		$this->current_downloaded_file = false;

		self::delay($url);

		$curl = curl_init();

		curl_setopt($curl, CURLOPT_FAILONERROR, false);
		curl_setopt($curl, CURLOPT_URL, $url);
		if($this->UserAgent) curl_setopt($curl, CURLOPT_USERAGENT, $this->UserAgent);
		//curl_setopt($curl, CURLOPT_REFERER, 'http://www.google.com');
		curl_setopt($curl, CURLOPT_ENCODING, 'gzip,deflate');
		//curl_setopt($curl, CURLOPT_AUTOREFERER, true);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_TIMEOUT, $this->TimeoutInSecs);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, $this->AllowRedirection);    // redirect flag
				
		if($parameters !== null)
		{
			curl_setopt($curl, CURLOPT_POST, 1);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $post_string);			
		}
		
	    //curl_setopt($cUrl, CURLOPT_PROXY, 'proxy_ip:proxy_port');
	    $proxy = $this->get_next_proxy();
	    if($proxy){
	        curl_setopt($curl, CURLPROXY_HTTP, $proxy);
	        if($this->ProxyLogin)
	            curl_setopt($curl, CURLOPT_PROXYUSERPWD, $this->ProxyLogin.":".$this->ProxyPassword);
	    }
		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		if($this->AdditionalHeaders) curl_setopt($curl, CURLOPT_HTTPHEADER, $this->AdditionalHeaders);
		curl_setopt($curl, CURLOPT_HEADERFUNCTION, array(&$this, 'read_header'));
		curl_setopt($curl, CURLOPT_WRITEFUNCTION, array(&$this, 'read_body'));
		curl_setopt($curl, CURLOPT_BUFFERSIZE, 1024);

		if($this->IgnoreSSLCertificate)
		{
			curl_setopt ($curl, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt ($curl, CURLOPT_SSL_VERIFYPEER, 0);
		}

		if($this->UseCookie) curl_setopt($curl, CURLOPT_COOKIEJAR, $this->cache_dir.'/cookies.txt');
		if($send_cookie) curl_setopt($curl, CURLOPT_COOKIEFILE, $this->cache_dir.'/cookies.txt');

		if($binary) curl_setopt($curl, CURLOPT_BINARYTRANSFER, 1);

		curl_exec($curl);

		if(!$this->ignore_error and curl_errno($curl))
		{
			trigger_error(curl_error($curl));
        	$this->ignore_error = false;
		}

        $ri = curl_getinfo($curl);
		curl_close($curl);

		$this->ResponseHttpCode = $ri['http_code'];
		$this->ResponseUrl = $ri['url'];
		
		if(rtrim($this->ResponseUrl, " /") != rtrim($url, " /")) Logger::Write("Redirected to $this->ResponseUrl");
		
		if($this->ResponseHttpCode != 200)
		{		
			Logger::Error_("ResponseHttpCode:".$this->ResponseHttpCode);
		 	return false;
		}

		if($this->StoreFiles2Disk and $this->current_downloaded_file)
		{
			if($parameters === null) $this->put_file2cache($url, $this->ResponseUrl, $this->current_downloaded_file);
			else $this->put_file2cache($url."|".$post_string, $this->ResponseUrl, $this->current_downloaded_file);
		}

		return $this->current_downloaded_file;
	}
	private $ignore_error = false;

	/*private static function delay($url)
	{
		if(self::$RequestDelayInMss <= 0) return;

		$old_time = microtime() - self::$RequestDelayInMss * 1000;
        foreach(self::$last_domains as $d=>$t)
        {
        	if($t <= $old_time) unset($last_domains[$d]);
        	else break;//domains are expected to be ordered by last time used
        }

		$parts = parse_url($url);
		$domain = $parts['host'];
	
		if(array_key_exists($domain, self::$last_domains))
		{
			$time = self::$last_domains[$domain];					
			usleep($time - $old_time);
			unset(self::$last_domains[$domain]);//to move it to the end of the array
		}
		self::$last_domains[$domain] = microtime();
	}
	private static $last_domains = array();*/
	private static function delay($url)
	{
		if(self::$RequestDelayInMss <= 0) return;
		
		static $domains = array();
		$parts = parse_url($url);
		$domain = $parts['host'];	
		if(array_key_exists($domain, $domains)) $next_time = $domains[$domain];
		else $next_time = 0;
		$time = microtime(false);				
		if($next_time > $time) usleep($next_time - $time);
		else
		{//clean up			
        	//foreach($domains as $d=>$t) if($t <= $time) unset($domains[$d]);
		}
		$domains[$domain] = $time + self::$RequestDelayInMss * 1000;
	}
	
	private function read_header($curl, $header)
	{
        if(preg_match("@Content-Type:\s*(.+)@", $header, $res) and !preg_match($this->accepted_content_type_pattern, $res[1], $res))
        {
        	Logger::Warning("Unacceptable $header");
        	$this->ignore_error = true;
        	//curl_close($curl);
        	return;
        }
		if($this->ReadHeaderCallback and !call_user_func($this->ReadHeaderCallback, $header))
        {
        	//Logger::Write("ReadHeaderCallback returned false");
        	$this->ignore_error = true;
        	//curl_close($curl);
        	return;
        }
        return strlen($header);
 	}
	private $accepted_content_type_pattern;

	private function read_body($curl, $chunk)
	{
		$this->current_downloaded_file .= $chunk;
		if(strlen($this->current_downloaded_file) > $this->MaxDownloadedLength)
        {
           	Logger::Warning("The file was truncated up to $this->MaxDownloadedLength");
        	$this->ignore_error = true;
           	//curl_close($curl);
           	return;
        }
        return strlen($chunk);
 	}
 	private $current_downloaded_file = false;

	private $current_proxy_file = false;
	private $proxies = array();
	private $current_proxy_i = -1;
	private $proxies_count = false;

	private function get_next_proxy(){
		if(!$this->ProxyFile) return;
		if($this->current_proxy_file != $this->ProxyFile){
			$proxies = array();
			$proxies_str = file_get_contents($this->ProxyFile);
			if(preg_match_all("/^\s*(.*?)[\s\:]\s*(\d+)/im", $proxies_str, $res)){
				$l = count($res[1]);
				for($i = 0; $i < $l; $i++) $this->proxies[] = $res[1][$i].":".$res[2][$i];
			}
			$this->current_proxy_file = $this->ProxyFile;
			$this->proxies_count = count($this->proxies);
			if($this->proxies_count < 1) die("There is no proxies in the file specified.");
		}
		if(++$this->current_proxy_i >= $this->proxies_count) $this->current_proxy_i = 0;
		Logger::Write("CURRENT PROXY: ".$this->proxies[$this->current_proxy_i]);
		return $this->proxies[$this->current_proxy_i];
	}

	private $cached_files = false;
	const CacheMapFile = "cache_map.txt";
	private $current_cache_map_file_handle;
	private $start_time_str;
	private $stored_file_count = 0;
	const CacheMapTable = "cache_map";

	private function get_cached_file($url)
	{
		if(!$this->cached_files)
		{
			$this->cached_files = array();
			if($this->UseCacheMapInDB)
			{
				mysql_query("DROP TABLE IF EXISTS '".Curler::CacheMapTable."'");
				mysql_query("CREATE TABLE '".Curler::CacheMapTable."' ('url' varchar(255) NOT NULL, 'file' varchar(255) NOT NULL, 'response_url' varchar(255), PRIMARY KEY ('url'))");
			}
			$map_file = $this->cache_dir."/".Curler::CacheMapFile;
			if(!file_exists($map_file))
			{
				Logger::Warning_("Cache map file was not found: ".$map_file);
				$this->cached_files = array();
				return;
			}
			$map_str = file_get_contents($map_file);
			if(preg_match_all("@^(.*?)\t(.*?)\t(.*?)$@im", $map_str, $res))
			{
				$len = count($res[1]);
				for($i = 0; $i < $len; $i++){
					$u = $res[1][$i];
					$file = $res[2][$i];
					$response_url = $res[3][$i];
					if(!$response_url) $response_url = $u;
					if($this->UseCacheMapInDB)
						mysql_query("REPLACE ".Curler::CacheMapTable." SET url='$u', file='$file', response_url='$response_url'");
					else
						$this->cached_files["$u"] = array("$file", "$response_url");
				}
			}
		}
		if($this->UseCacheMapInDB)
		{
			If($result = mysql_query("SELECT file, response_url FROM ".Curler::CacheMapTable." WHERE url='".addslashes($url)."'"))
			{
				$item_row = mysql_fetch_assoc($result);
				$this->ResponseUrl = $item_row['response_url'];
				return file_get_contents($this->cache_dir."/".$item_row['file']);
			}
		}
		else
		{					
			if(array_key_exists($url, $this->cached_files))
			{
				$this->ResponseUrl = $this->cached_files[$url][1];
				return file_get_contents($this->cache_dir."/".$this->cached_files[$url][0]);
			}
		}
	}

	private function put_file2cache($url, $response_url, $html)
	{
		if(!$url or !$html) return;

		if(!$this->current_cache_map_file_handle and !$this->UseCacheMapInDB)
		{
			$map_file = $this->cache_dir."/".Curler::CacheMapFile;
			$this->current_cache_map_file_handle = fopen($map_file, "a") or Logger::Quit("Could not write cache map file: ".$map_file);
		}

		$file = $this->start_time_str."_".$this->stored_file_count++.".htm";
		$fh = fopen($this->cache_dir."/".$file, "w");
		fwrite($fh, $html);
		fclose($fh);

		if($this->UseCacheMapInDB) mysql_query("INSERT ".Curler::CacheMapTable." SET url='$url', file='$file', response_url='$response_url'");
		else
		{
			if($url == $response_url) $response_url = "";
			fwrite($this->current_cache_map_file_handle, "$url\t$file\t$response_url\n");
			fflush($this->current_cache_map_file_handle);
		}
	}
}


/*
//$cr = new Curler(true, true);
$cr = new Curler(false, false);
$cr->UserAgent = "Mozilla/5.0";
//$cr->ProxyFile = "proxy.txt";
//$html = $cr->GetPage("http://www.your-move.co.uk/property-for-sale/detached-house-for-sale-newacres-road-west-thamesmead-london-se28-0-sale-id-527860884");
//print($html);

//$p = $cr->PostPage('http://numberone4property.co.uk/sale_properties.asp?Page=0&P=Borders&L=3&A=&C=', array('SearchAll' => 'Search'));

$cr->AdditionalHeaders = array(
//"Connection: keep-alive",
//"Cache-Control: max-age=0",
//"User-Agent: Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/536.11 (KHTML, like Gecko) Chrome/20.0.1132.57 Safari/536.11",
//
//"Referer: http://thelittlehousecompany.com/search/results/40cd750bba9870f18aada2478b24840a",
//"Accept-Encoding: gzip,deflate,sdch",
//"Accept-Language: en-US,en;q=0.8",
"Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.3"
);

$p = $cr->GetPage('http://www.google.com.ua');
//$p = $cr->GetPage('http://thelittlehousecompany.com/search/do_search/');
//$p = $cr->PostPage('http://thelittlehousecompany.com/search/do_search/', array('searchToRent'=>'To Rent'));

print($p);
print("OK");
*/

?>