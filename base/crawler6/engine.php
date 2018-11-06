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

include_once("$ABSPATH/base/crawler6/crawler.php");
include_once("$ABSPATH/base/crawler6/session.php");
	
abstract class Engine
{					
	const CRAWLER_USER_GROUP = "crawler";
	
	function __construct($crawler_class_name)
	{		
		Logger::Write2("STATRED");
		umask(002);							
				
		$this->crawler = new $crawler_class_name($this);
		$this->session = new Session($this->crawler);		
		$this->Initialize();
	}	
	protected $session;
				
	final public function Crawler()
	{
		return $this->crawler;
	}
	private $crawler;
	
	final private function rename_old_logs()	
	{
		$current_file_name = basename(Logger::CurrentLogFile());
		$dh = opendir(Logger::GetLogDir()) or Logger::Quit("Could not open image dir: ".Logger::GetLogDir());
		while($fn = readdir($dh)) 
		{
 			if($fn == "." or $fn == "..") continue;
			if(preg_match("@\-@is", $fn)) continue;
			if($fn == $current_file_name) continue;
			$f = Logger::GetLogDir()."/$fn";
			if(is_dir($f)) continue;
			$f2 = Logger::GetLogDir()."/$fn-$current_file_name";
			rename($f, $f2);
		}
	}
		
	final public function Run()
	{					
		if($this->session->IsNew()) $this->rename_old_logs();
		
		try
		{			
			$this->Crawler()->Begin();
			while($item = $this->session->Move2NextItem())
			{
				$queue = $this->session->GetQueue($item->QueueName());
				$processor = $queue->Processor;	
				$queue->CompleteItem($item, $this->Crawler()->$processor($item));
			}				
			$this->Crawler()->End();	
			$this->CompleteSuccessfully();			
		}
		catch(Exception $e)
		{
			//$this->ExitOnError("Exception happend: ".$e->getMessage()."\nIn ".$e->getFile().",".$e->getLine());
			$this->ExitOnError($e);
		}
	}
	
	//get currently picked up item
	final public function Item()
	{
		return $this->session->Item();
	}	
	
	final public function AddItems2Queue($queue_name, $items)
	{
		$queue = $this->session->GetQueue($queue_name);
		$c = 0;
		foreach((array)$items as $item)	if($queue->Add($item)) $c++;		
		Logger::Write("Added to queue '$queue_name' items: $c");		
		if(Mode::This() == Mode::RAW_DEBUG or Mode::This() == Mode::DEBUG) Logger::Write("Queue $queue_name new items: ".$queue->NewCount());
	}
	
	final public function GetTotalItemCountInQueue($queue_name)
	{	
		return $this->session->GetQueue($queue_name)->TotalCount();
	}	
	
	final public function GetLastItemStateInQueue($queue_name)
	{	
		return $this->session->GetQueue($queue_name)->GetLastItemState();
	}
	
	//**************************************************************************************************************
	//functions to be overridden
	//**************************************************************************************************************
	abstract protected function Initialize();
	abstract public function SaveProduct(Product $product);				
	abstract public function CompleteSuccessfully();	
	//should be called if fatal error
	abstract public function ExitOnError($m);
}

?>