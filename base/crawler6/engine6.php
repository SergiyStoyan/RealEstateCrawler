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

include_once("$ABSPATH/base/crawler6/engine.php");
//include_once("$ABSPATH/common/html_utilities.php");
include_once("$ABSPATH/common/db.php");
include_once("$ABSPATH/base/crawler6/product.php");
include_once("$ABSPATH/base/crawler6/downloader.php");
include_once("$ABSPATH/base/crawler6/table_routines.php");
include_once("$ABSPATH/common/misc.php");

class Mode extends ModeTemplate
{
   	const TEST_PAGE = "t";
   	const RAW_DEBUG = null;
   	const CLEAR_SESSION = "c";
   	const CLEAR_CACHE = "a";
   	const PRODUCTION = "r";
   	const DEBUG = "d";
}

class Engine6 extends Engine
{		
	protected function Initialize()
	{
		//parent::initialize($crawler_type);
		
		Mode::PrintUsage(array(
			Mode::RAW_DEBUG=>" - raw debug mode using cache",
			Mode::TEST_PAGE=>"=<test_page_url> - download the page and output parsing results",
			Mode::CLEAR_SESSION=>" - clear previous session data and exit",
			Mode::CLEAR_CACHE=>" - clear the cache and exit",
			Mode::PRODUCTION=>" - production mode",
			Mode::DEBUG=>" - like production mode but products are marked as debug"
			)
		);
		
		Logger::Write2("MODE: ".Mode::Name());

		if(Mode::This() == Mode::CLEAR_CACHE)
		{
			Logger::$CopyToConsole = true;
			$cache_dir = Constants::CacheDirectory."/".$this->Crawler()->Id();
			if(is_dir($cache_dir))
			{
				if(Misc::ClearDirectory($cache_dir)) Logger::Write("Cache $cache_dir was deleted.");
				else Logger::Write("Cache $cache_dir could not be deleted completely.");
			}
			else Logger::Write("No cache $cache_dir exists.");
			Logger::Write2("COMPLETED");
			exit();
		}
		elseif(Mode::This() == Mode::CLEAR_SESSION)
		{
			Logger::$CopyToConsole = true;
			$this->session->Destroy();
			Logger::Write2("COMPLETED");
			exit();
		}
			
		/*TableRoutines::CreateCrawlersTable();		
		if($delay = Db::GetSingleValue("SELECT delay_between_requests FROM crawlers WHERE id='$this->Crawler()->Id()'")) Downloader::SetRequestDelayInMss($delay);
		else Db::SmartQuery("INSERT INTO crawlers SET id='$this->Crawler()->Id()', state='debug'") or Logger::Quit("Crawler '$this->Crawler()->Id()' does not exist in the database.");*/
										
		switch(Mode::This())
		{
			case Mode::TEST_PAGE:
				Logger::$CopyToConsole = true;
					
				Downloader::Initialize($this->Crawler(), true, true, $this->session->StartTime());
				$this->Crawler()->TestPage(Mode::OptionValue());
				Logger::Write2("COMPLETED");							
				exit();
				
				break;				
			case Mode::RAW_DEBUG:			
				Logger::$CopyToConsole = true;
				
				Downloader::Initialize($this->Crawler(), true, true, $this->session->StartTime());
							
				break;						
			case Mode::DEBUG:
				TableRoutines::CreateCrawlersTable();
					
			case Mode::PRODUCTION:	
				$state = Db::GetSingleValue("SELECT state FROM crawlers WHERE id='".addslashes($this->Crawler()->Id())."'"); 
				if(Mode::This() == Mode::DEBUG)
				{
					if($state == 'disabled') Logger::Quit("Crawler '".$this->Crawler()->Id()."' is disabled.");
					elseif($state === null) Db::SmartQuery("INSERT INTO crawlers SET id='".addslashes($this->Crawler()->Id())."', state='debug'") or Logger::Quit("Could not add crawler '".$this->Crawler()->Id()."' to the database.");
				}
				else
				{
					if($state != 'enabled') Logger::Quit("Crawler '".$this->Crawler()->Id()."' is not enabled.");
				}
									
				Logger::$CopyToConsole = false;				 

				$this->products_table = TableRoutines::CreateProductsTableForCrawler($this->Crawler()->Id());
								
				//starting session
				$c = Db::GetRowArray("SELECT * FROM crawlers WHERE id='".addslashes($this->Crawler()->Id())."'");
				$archive = "session_start_time=".$c["_last_start_time"]." end_time=".$c["_last_end_time"]." state=".$c["_last_session_state"]." log=".$c["_last_log"]."\n".$c["_archive"];
				$archive = substr($archive, 0, 10000);
				$archive = preg_replace("@[^\n]+$@is", "", $archive, 1);
				if($this->session->IsNew()) $_last_start_time_ = "FROM_UNIXTIME(".$this->session->StartTime().")";
				else $_last_start_time_ = "NOW()";
				Db::Query("UPDATE crawlers SET _last_process_id=".getmypid().", _next_start_time=0, _last_start_time=$_last_start_time_, _last_end_time=0, _last_session_state='started', _last_log='".Logger::CurrentLogFile()."', _archive='$archive' WHERE id='".addslashes($this->Crawler()->Id())."'");
				
				if(Mode::This() == Mode::DEBUG)	Downloader::Initialize($this->Crawler(), true, true, $this->session->StartTime());
				else Downloader::Initialize($this->Crawler(), false, false, $this->session->StartTime());
				
				break;
			default:
				Logger::Quit("Unknown mode: ".Mode::Name());
				break;
		}
		
		$this->initialize_counters();
	}
	
	private function initialize_counters()
	{
		$this->product_error_going_together_counter = new Counter("PRODUCT_ERROR_GOING_TOGETHER_COUNT", $this->Crawler()->Get('MAX_PRODUCT_ERROR_GOING_TOGETHER_COUNT'));
		$this->duplicated_product_id_counter = new Counter("DUPLICATED_PRODUCT_ID_COUNT", $this->Crawler()->Get('MAX_DUPLICATED_PRODUCT_ID_COUNT'));
	}
	private $product_error_going_together_counter;
	private $duplicated_product_id_counter;	
	
	private $products_table;
		
	public function SaveProduct(Product $product)
	{
		//if(get_class($product) != Product::ClassName()) Logger::Quit("Product is not ".Product::ClassName());
		
		$this->product_count++;
		
		Downloader::GetImage($product, $this->Crawler()->Id());	
					
		$errors = $product->Errors();
		if($errors) $this->product_error_going_together_counter->Increment();
		else $this->product_error_going_together_counter->Reset();
		
		if(!$product->Id) 
		{
			Logger::Error("Id is empty. The product is passed out.");
			return;
		}
				
		if(Mode::This() == Mode::RAW_DEBUG)			
		{
			$p = $product->GetDbFields2ValuesForTest();
			Logger::Write("Product $product->Id:\n".Misc::GetArrayAsString($p));
			return;	
		}
		
		$id_ = "'".addslashes($product->Id)."'";
		if(Mode::This() == Mode::PRODUCTION) $state_ = "'new'";
		else $state_ = "'debug'";
		
		$old_p = Db::GetRowArray("SELECT id, UNIX_TIMESTAMP(crawl_time) AS crawl_time, crawl_parameters, url, _state FROM $this->products_table WHERE id=$id_");	
		if($old_p)
		{				
			if($old_p['url'] != $product->Url) Logger::Error_("Product $id_ had different url:\n".$old_p['url']." != ".$product->Url);
			if($old_p['crawl_parameters'] != $product->CrawlParameters) 
			{
				if(!preg_match("@(^|\|)".preg_quote($product->CrawlParameters)."($|\|)@is", $old_p['crawl_parameters'])) $product->CrawlParameters .= "|".$old_p['crawl_parameters'];
			}
			else
			{					
				if($old_p['crawl_time'] >= $this->session->StartTime())
				{
					Logger::Error_("Duplicated product id: $product->Id crawled already at ".date("Y-m-d H:i:s", $old_p['crawl_time']));
					$this->duplicated_product_id_counter->Increment();
					if($this->product_count == 1) Logger::Warning_("First product of the restored session has duplicated id: '$product->Id'. Most likely it was insterted into DB but not marked as completed due to breaking the process.");
				}
			}			
		
			Db::Query("UPDATE $this->products_table SET raw_data='".addslashes($product->GetRawDataAsJson())."' WHERE id=$id_");			
			if(Db::LastAffectedRows()) $sql = "UPDATE $this->products_table SET crawl_time=NOW(), _state=$state_, change_time=NOW() WHERE id=$id_";
			elseif($old_p['_state'] == 'deleted') Db::Query("UPDATE $this->products_table SET crawl_time=NOW(), _state=$state_ WHERE id=$id_");
			elseif($this->Crawler()->Get('DROP_ITEM_BRANCH_WHEN_OLD_PRODUCT_FOUND'))
			{
				Logger::Write("An old product was found id: $id_");
				$this->session->DropCurrentBranchDownToTrunkQueue($this->Crawler()->Get('DROP_ITEM_BRANCH_WHEN_OLD_PRODUCT_FOUND/TRUNK_QUEUE_NAME'));
			}
			else Db::Query("UPDATE $this->products_table SET crawl_time=NOW() WHERE id=$id_");
		}
		else
		{
			$sql = "INSERT INTO $this->products_table SET url='".addslashes($product->Url)."', crawl_parameters='".addslashes($product->CrawlParameters)."', raw_data='".addslashes($product->GetRawDataAsJson())."', publish_time=NOW(), crawl_time=NOW(), change_time=NOW(), _state=$state_, id=$id_";
			Db::Query($sql);			
		}
				
		static $time_2_update_last_product_time = 0;		
		if(time() > $time_2_update_last_product_time)
		{//it is used because getting MIN from products is very slow
			Db::Query("UPDATE crawlers SET _last_product_time=NOW() WHERE id='".addslashes($this->Crawler()->Id())."'");
			$time_2_update_last_product_time = time() + self::UPDATE_LAST_PRODUCT_TIME_SPAN_IN_SECS;
		}						
	}	
	private $product_count = 0;
	const UPDATE_LAST_PRODUCT_TIME_SPAN_IN_SECS = 100;
				
	public function CompleteSuccessfully()
	{
		Logger::Write("Product count in this process: ".$this->product_count);
		if($this->duplicated_product_id_counter->Count()) Logger::Warning_("Duplicated product id count: ".$this->duplicated_product_id_counter->Count());
		
		switch(Mode::This())
		{
			case Mode::RAW_DEBUG:
			
				break;
			case Mode::PRODUCTION:			
			case Mode::DEBUG:
			
				if(!$this->Crawler()->Get('DROP_ITEM_BRANCH_WHEN_OLD_PRODUCT_FOUND'))
				{
					Db::Query("UPDATE $this->products_table SET _state='deleted' WHERE crawl_time<FROM_UNIXTIME(".$this->session->StartTime().")");
					Logger::Write("Marked as deleted old products: ".Db::LastAffectedRows());
				}
				else
				{					
					Db::Query("UPDATE $this->products_table SET _state='deleted' WHERE crawl_time<ADDDATE(NOW(), INTERVAL -".$this->Crawler()->Get('DROP_ITEM_BRANCH_WHEN_OLD_PRODUCT_FOUND/DELETE_PRODUCTS_OLDER_THAN_DAYS')." DAY)");
					Logger::Write("Marked as deleted products older than ".$this->Crawler()->Get('DROP_ITEM_BRANCH_WHEN_OLD_PRODUCT_FOUND/DELETE_PRODUCTS_OLDER_THAN_DAYS')." days: ".Db::LastAffectedRows());
					
					/*Logger::Write("Touching image files of old but not-deleted products.");
					$last_id = null;
					while($rs = Db::GetArray("SELECT id, raw_data FROM $this->products_table WHERE _state<>'deleted' AND crawl_time<FROM_UNIXTIME(".$this->session->StartTime().") AND id>'".$last_id."' ORDER BY id LIMIT 1000"))
					{
						foreach($rs as $r)
						{			
							$rd = json_decode($r['raw_data'], true);
							isset($rd['image_path']) and $this->image_file_section->Touch(basename($rd['image_path']));
						}
						$last_id = $r['id'];
					}*/					
				}
		
				Db::Query("UPDATE crawlers SET _last_end_time=NOW(), _last_session_state='_completed', _next_start_time=ADDDATE(_last_start_time, INTERVAL run_time_span SECOND) WHERE id='".addslashes($this->Crawler()->Id())."'") or $this->ExitOnError("Could not update crawlers table.");	
				
				if($this->session->IsError()) Logger::Warning_("In the end, Session contains error Item's.");
				
				break;
			default:
			
				Logger::Quit("Unknown mode: ".Mode::Name());
		}	
		
		$this->session->Destroy();		
		
		Logger::Write2("COMPLETED");
		exit();
	}
	
	//should be called if fatal error
	public function ExitOnError($m)
	{	
		Logger::Write("Product count in this process: ".$this->product_count);
		if($this->duplicated_product_id_counter->Count()) Logger::Warning_("Duplicated product id count: ".$this->duplicated_product_id_counter->Count());
		
		switch(Mode::This())
		{
			case Mode::RAW_DEBUG:
				break;
			case Mode::PRODUCTION:			
			case Mode::DEBUG:
				Db::Query("UPDATE crawlers SET _last_end_time=NOW(), _last_session_state='_error', _next_start_time=ADDDATE(NOW(), INTERVAL restart_delay_if_broken SECOND) WHERE id='".addslashes($this->Crawler()->Id())."'");	
				break;
			default:
				Logger::Quit("Unknown mode: ".Mode::Name());
				break;
		}		
		
		Logger::Quit($m, 1);							
	}
}

?>