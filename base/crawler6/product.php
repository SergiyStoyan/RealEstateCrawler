<?
//********************************************************************************************
//Author: Sergey Stoyan, CliverSoft.com
//        http://cliversoft.com
//        stoyan@cliversoft.com
//        sergey.stoyan@gmail.com
//        1 February 2012
//Copyright: (C) 2012, Sergey Stoyan
//********************************************************************************************

$ABSPATH = dirname(__FILE__)."/../..";

include_once("$ABSPATH/common/logger.php");
include_once("$ABSPATH/common/html_utilities.php");
include_once("$ABSPATH/base/crawler6/table_routines.php");

class Product
{				
	/*public static function ClassName()
	{
		if(!self::$class_name) self::$class_name = get_called_class();
		return self::$class_name;
	}
	static private $class_name;*/
	
	function __construct($crawl_parameters, $url, $id, array &$raw_data_array)
	{
		$this->RawData = $raw_data_array;
		$this->Id = $id;
		$this->CrawlParameters = $crawl_parameters;
		$raw_data;
		$this->Url = $url;
				
		if(!$this->Id) $this->errors[] = "id is empty";
		if(!$this->Url) $this->errors[] = "url is empty";				
		if(!$this->RawData) $this->errors[] = "raw_data is empty"; 
		//if(!$this->CrawlParameters) $this->errors[] = "crawl_parameters is empty";
						
		if(count($this->errors)) Logger::Error_($this->errors);
		else $this->errors = null;	
	}
	
	public $Id;
	public $RawData = null;	
	public $Url = null;
	public $CrawlParameters = null;
		
	final public function GetRawDataAsJson()
	{
		$rdj = json_encode($this->RawData) or Logger::Quit("json_encode error: ".json_last_error());
		return $rdj;
	}
	
	final public function Errors()
	{
		return $this->errors;
	}
	protected $errors = array();
		
	final public function GetDbFields2ValuesForTest()
	{		
		$fs2vs = array();
		$fs2vs['crawl_parameters'] = $this->CrawlParameters;
		$fs2vs['id'] = $this->Id;
		$fs2vs['url'] = $this->Url;
		foreach($this->RawData as $n=>$v) $fs2vs['_'.$n] = $v;
		return $fs2vs;	
	}
}

?>