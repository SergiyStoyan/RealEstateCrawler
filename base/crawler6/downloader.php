<?
//********************************************************************************************
//Author: Sergey Stoyan, CliverSoft.com
//        http://cliversoft.com
//        stoyan@cliversoft.com
//        sergey.stoyan@gmail.com
//        1 February 2012
//Copyright: (C) 2012, Sergey Stoyan
//********************************************************************************************

error_reporting(E_ALL);
set_time_limit(0);

$ABSPATH = dirname(__FILE__)."/../..";

include_once("$ABSPATH/common/logger.php");
include_once("$ABSPATH/constants.php");
include_once("$ABSPATH/common/curler.php");
include_once("$ABSPATH/base/crawler6/request.php");
include_once("$ABSPATH/base/crawler6/response.php");
	
class Downloader
{		
	static public $UserAgent = "CliverCrawler";
	static public $AdditionalHttpHeaders = null;			
	static public $UseCookie = true;			
	
	static public function Initialize($crawler, $store_files2disk, $use_cached_files, $session_start_time)
	{	
		self::$crawler = $crawler;
		
		Curler::$RequestDelayInMss = self::$crawler->Get('TIME_INTERVAL_BETWEEN_HTTP_REQUESTS_IN_MSS');
		self::$curler = new Curler($store_files2disk, $use_cached_files, Constants::CacheDirectory."/".$crawler->Id());
		
		//self::$curler->TimeoutInSecs = self::$crawler->Get('HTTP_REQUEST_TIMEOUT_IN_SECS');
		if(self::$TimeoutInSecs) self::$curler->TimeoutInSecs = self::$TimeoutInSecs;
		
		self::$image_file_section = new FileSection(Constants::ImageDirectory, $crawler->Id(), Engine::CRAWLER_USER_GROUP, $session_start_time);		
		
		self::$network_error_going_together_counter = new Counter("NETWORK_ERROR_GOING_TOGETHER_COUNT", self::$crawler->Get('MAX_NETWORK_ERROR_GOING_TOGETHER_COUNT'));
		
		if(self::$image_file_section)
			self::$image_error_going_together_counter = new Counter("IMAGE_ERROR_GOING_TOGETHER_COUNT", self::$crawler->Get('MAX_IMAGE_ERROR_GOING_TOGETHER_COUNT'));			
	}
	static private $crawler;
	static private $curler;
	static private $image_file_section;
	static private $network_error_going_together_counter;
	static private $image_error_going_together_counter;
	
	static public function SetRequestDelayInMss($delay)
	{
		Logger::Write2("Curler::RequestDelayInMss = $delay");
		Curler::$RequestDelayInMss = $delay;
	}
	
	static public function SetRequestTimeoutInSecs($timeout)
	{
		Logger::Write2("Downloader::curler->TimeoutInSecs = $timeout");
		//if(!self::$curler) throw new Exception("Curler is not initialized yet.");
		if(self::$curler) self::$curler->TimeoutInSecs = $timeout;
		else self::$TimeoutInSecs = $timeout;
	}
	static $TimeoutInSecs = null;
	
	static public function ClearCookies()
	{
		self::$curler->ClearCookies();
	}
	
	static public function Get2($url, $post_parameters=null, $send_cookie=true, $is_binary=false)
	{
		$send_cookie1 = Request::$SendCookie;
		Request::$SendCookie = $send_cookie;
		$request = new Request($url, $post_parameters, $is_binary);
		$rc = self::Get($request);
		Request::$SendCookie = $send_cookie1;
		return $rc;
	}
	
	static public function Get(Request $request)
	{		
		self::$curler->AdditionalHeaders = self::$AdditionalHttpHeaders;
		if($request::$AdditionalHttpHeaders)	
		{
			if(self::$curler->AdditionalHeaders) self::$curler->AdditionalHeaders = array_merge(self::$curler->AdditionalHeaders, $request::$AdditionalHttpHeaders);
			else self::$curler->AdditionalHeaders = $request::$AdditionalHttpHeaders;
		}
		self::$curler->UseCookie = self::$UseCookie;
		self::$curler->UserAgent = self::$UserAgent;
		
		if(!$request->PostParameters())
		{	
			if($request->IsBinary()) $page = self::$curler->GetBinary($request->Url(), $request::$SendCookie);
			else $page = self::$curler->GetPage($request->Url(), $request::$SendCookie);
		}
		else
		{
			if($request->IsBinary()) throw new Exception("POST for binary is not implemented.");
			$page = self::$curler->PostPage($request->Url(), $request->PostParameters(), $request::$SendCookie);
		}
		self::$response = new Response($request, $page, self::$curler->ResponseUrl, self::$curler->ResponseHttpCode, self::$curler->ResponseCached);
		if(self::$response->IsError())
		{		
			self::$network_error_going_together_counter->Increment();
			return false;
		}
		self::$network_error_going_together_counter->Reset();	
		return true;
	}
		
	//Response of last Download() call
	static final public function Response()
	{
		return self::$response;
	}
	static private $response;
	
	//Request of last Download() call
	static final public function Request()
	{
		return self::$response->Request();
	}
	
	//Belongs to page of last Download() call
	static final public function Regex()
	{
		return self::$response->Regex();
	}
	
	//Belongs to page of last Download() call
	static final public function Xpath()
	{
		return self::$response->Xpath();
	}
		
	static public function GetImage(Product &$product, $crawler_id)
	{				
		if(!$product->RawData['image_url'])	
		{
			if($product->RawData['image_url'] === false)	
			{
				Logger::Write("Image url is false.");
				self::$image_error_going_together_counter->Reset();
				return true;
			}	
			Logger::Warning_("Image url is empty.");
			self::$image_error_going_together_counter->Increment();
			return false;
		}
		
		//$extension = Regex::GetFirstValue("@(\.\w+?)(?:[\?\&\#]|$)@is", $product->RawData['image_url']);			
		$name = preg_replace("@[\\/\s]@is", "_", $product->Id);
		$product->RawData['image_path'] = $crawler_id."/".$name;
		$file = self::$image_file_section->BasePath()."/".$product->RawData['image_path'];
		//$file2 = self::$image_file_section2->BasePath()."/".$product->RawData['image_path'];
				
		if(Mode::This() == Mode::RAW_DEBUG)
		{			
			if(file_exists($file))
			{
				self::$image_file_section->Touch($name);
				//self::$image_file_section2->Touch($name);
				Logger::Write("Image exists on disk: ".$product->RawData['image_url']);
				self::$image_error_going_together_counter->Reset();
				return true;
			}
		}
		
		if(preg_match("@(^\s*/)@is", $product->RawData['image_url']))
		{//url is local path (used when parsing pdf)
			copy($product->RawData['image_url'], $file) or self::$ExitOnError("Could not copy ".$product->RawData['image_url']." to $file");
			self::$image_file_section->Touch($name);
			//self::$image_file_section2->Touch($name);
			self::$image_error_going_together_counter->Reset();
			return true;
		}		
		
		if(!self::GetFile($product->RawData['image_url'], $file, self::$crawler->Get('MAX_IMAGE_FILE_LENGTH_IN_BYTES')))
		{
			Logger::Error("Image was not downloaded.");
			$product->RawData['image_path'] = null;
			self::$image_error_going_together_counter->Increment();
			return false;
		}
		
		if(self::$downloading_file_was_not_changed)	Logger::Write("Image was not changed.");
		else
		{
  			/*$i = new Gmagick();
			$i->readImage($file) or Logger::Error("Could not read $file");
			$i->negateImage(true) or Logger::Error("Could not negateImage"); 
			$i->writeImage($file2) or Logger::Error("Could not write $file2"); 
			$i->destroy() or Logger::Error("Could not destroy");
			
			self::$image_error_going_together_counter->Increment();*/
		}
		self::$image_error_going_together_counter->Reset();
		return true;
	}
	
	static public function GetFile($url, $file, $max_length=300000)
	{
		self::$downloading_file_path = $file;				
		self::$downloading_file_was_not_changed = false;
		self::$curler->ReadHeaderCallback = "Downloader::read_header_callback";
		$max_length1 = self::$curler->MaxDownloadedLength;
		self::$curler->MaxDownloadedLength = $max_length;
		$binary = self::$curler->GetBinary($url);
		self::$curler->ReadHeaderCallback = null;
		self::$curler->MaxDownloadedLength = $max_length1;
		if(self::$downloading_file_was_not_changed)
		{
			touch(self::$downloading_file_path) or Logger::Error("Could not change time of the file ".self::$downloading_file_path);		
			return true;
		}
		if(!$binary) return false;
		if(file_exists($file) and !unlink($file)) self::$ExitOnError("Could not unlink $file");
		$if = fopen($file, "wb") or self::$ExitOnError("Could not create $file");
		fwrite($if, $binary);
		fclose($if);
		chmod($file, 0775) or self::$ExitOnError("Could not set permission for $file");
		chgrp($file, Engine::CRAWLER_USER_GROUP) or Logger::Error("Could not set group for $file");//self::$ExitOnError("Could not set group for $file");		
		return true;
	}
		
	//to check if the old file is the same
	final public static function read_header_callback($header)
	{
		if(preg_match("@Content-Length:\s*(\d+)@", $header, $res))
		{	
			$file_length = $res[1];
			if(file_exists(self::$downloading_file_path) and $file_length == filesize(self::$downloading_file_path))
        	{
				self::$downloading_file_was_not_changed = true;
        		return false;
        	}
		}
        return true;
 	}
	private static $downloading_file_path;
	private static $downloading_file_was_not_changed;
}

//used to store and clean up crawler's fileslike downloads, images etc
class FileSection
{
	public function __construct($base_path, $crawler_id, $crawler_user_group, $start_time_in_secs)
	{	
		$this->base_path = $base_path;
		
		$path = $base_path."/".$crawler_id;				
		if(file_exists($path)) Logger::Write("Directory exists: $base_path");
		else
		{
			mkdir($path, 0775, true) or Logger::Quit("Could not create directory: $path");		
			chmod($path, 0775) or Logger::Quit("Could not set permission for $path");
			chgrp($path, $crawler_user_group) or Logger::Error("Could not set group for $path");			
			Logger::Write("Directory created: $base_path");
		}
		$this->path = $path;
				
		$this->start_time_in_secs = $start_time_in_secs;		
	}
	private $start_time_in_secs;
	
	public function Path()
	{
		return $this->path;
	}
	private $path;	
	
	public function BasePath()
	{
		return $this->base_path;
	}
	private $base_path;	
	
	public function Touch($file_name)
	{		
		$file = $this->Path()."/$file_name";
		touch($file) or Logger::Error("Could not change time of the file $file");
	}
	
	public function DeleteFilesOlderThanSession()
	{				
		Logger::Write("Deleting files in $this->path older than ".date("Y-m-d H:i:s", $this->start_time_in_secs));
		//all actual files were "touched" during the session
		Misc::ClearDirectory($this->path, $this->start_time_in_secs);
	}
}

?>