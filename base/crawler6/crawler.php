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

include_once("$ABSPATH/common/shell_utilities.php");
Shell::ExitIfTheScriptRunsAlready();

include_once("$ABSPATH/common/logger.php");
include_once("$ABSPATH/constants.php");
Logger::Init(Constants::LogDirectory."/".GetCrawlerClass(), 10);

Logger::Write2("Process owner: ".Shell::GetProcessOwner());
	
abstract class Crawler
{	
	final function __construct(Engine $engine)
	{
		$this->id = get_class($this);
		$this->engine = $engine;
		$this->Init();
		Logger::Write("CONFIGURATION: ".Misc::GetArrayAsString($this->configuration));
		//$this->seal_configuration();
	}	
	private $configuration = null;
			
	final public function Set($path, $value)
	{
		if($this->Engine()->Crawler()) Logger::Quit("Crawler object has been already created and so key '$path' cannot be set.", 1);
		if(!$path)
		{
			if($this->configuration) Logger::Quit("Configuration is already set and its key collection cannot be redefined:\n".Misc::GetArrayAsString($this->configuration), 1);
			$this->configuration = $value;
			return;
		}
		$p = &$this->configuration;
		foreach(explode("/", $path) as $k) 
		{
			if(!isset($p[$k])) Logger::Quit("Configuration key '$k' in '$path' is not defined.", 1);
			$p = &$p[$k];
		}
		$p = $value;
	}
	
	/*private function seal_configuration()
	{
		Logger::Write2("CONFIGURATION: ".Misc::GetArrayAsString($this->configuration));
		foreach(array_keys($this->configuration) as $k) if(is_array($this->configuration[$k])) $this->prepare_configuration($this->configuration[$k], $k);
	}
	
	private function prepare_configuration(&$configuration, $path)
	{
		foreach(array_keys($configuration) as $k)
		{
			$p = "$path/$k";
			$this->configuration[$p] = &$configuration[$k];
			if(is_array($configuration[$k])) $this->prepare_configuration($configuration[$k], $p);
		}	
	}*/
	
	final public function Get($path)
	{
		$p = &$this->configuration;
		foreach(explode("/", $path) as $k)
		{
			if(!isset($p[$k])) Logger::Quit("Configuration key '$k' in $path' does not exist.", 1);
			$p = &$p[$k];
		}
		return $p;
		/*if(!isset($this->configuration[$path])) Logger::Quit("Configuration key '$path' does not exist.", 1);
		return $this->configuration[$path];*/
	}
	
	final public function Engine()
	{
		return $this->engine;
	}
	private $engine;
	
	final public function Id()
	{
		return $this->id;
	}
	private $id;
	
	//it is where configuraion must be set
	abstract public function Init();
	//called before starting session cycle
	abstract public function Begin();
	//called when no item is in session
	abstract public function End();
}

function GetCrawlerClass()
{
	$trace = debug_backtrace();
	$start_file = $trace[sizeof($trace) - 1]['file'];
	preg_match("@.*[\\\\/](.*?)\.php$@si", $start_file, $res);
	return $res[1];
}

?>