<?
//********************************************************************************************
//Author: Sergey Stoyan, CliverSoft.com
//        http://cliversoft.com
//        stoyan@cliversoft.com
//        sergey.stoyan@gmail.com
//        27 February 2007
//Copyright: (C) 2007, Sergey Stoyan
//********************************************************************************************

class Misc
{
	static public function GetArrayAsString($a)
	{
		ob_start();
		print_r((array)$a);
		return trim(ob_get_clean());
	}
	
	static public function TrimArray(&$a)
	{
		foreach($a as $k=>$v)
		{
			if(empty($v)) unset($a[$k]);
			elseif(is_array($v)) self::TrimArray($v);
			else $a[$k] = trim($v);
		}
		return $a;
	}
	
	//delete directories and files in the directory that are older than the specified time and not filtered by the filter;
	//returns true if the directory was deleted completely.
	static public function ClearDirectory1($directory, $delete_older_than_time=null, $regex_filter=null)
	{
		$empty = true;
		$dh = opendir($directory) or trigger_error("Could not open dir: $directory");
		while($d = readdir($dh)) 
		{
 			if($d == "." or $d == "..") continue;			
			$path = "$directory/$d";
			if(is_dir($path)) 
			{	
				$empty2 = self::ClearDirectory($path, $delete_older_than_time, $regex_filter);
				if($empty) $empty = $empty2;
				continue;
			}
			if($delete_older_than_time and filemtime($path) >= $delete_older_than_time)
			{
				$empty = false; 
				continue;
			}
			if($regex_filter and preg_match($regex_filter, $path))
			{
				$empty = false; 
				continue;
			}
			if(!unlink($path)) 
			{
				trigger_error("Could not delete file: $path");
				$empty = false; 
				continue;
			}
		}		
		closedir($dh);
		if($empty) 
		{
			if(rmdir($directory)) return true;
			trigger_error("Could not delete dir: $directory");
			return false;
		}
		return false;
	}
	
	static public function ClearDirectory($directory, $delete_older_than_time=null, $save_callback=null)
	{
		$empty = true;
		$dh = opendir($directory) or trigger_error("Could not open dir: $directory");
		while($d = readdir($dh)) 
		{
 			if($d == "." or $d == "..") continue;			
			$path = "$directory/$d";
			if(is_dir($path)) 
			{	
				$empty2 = self::ClearDirectory($path, $delete_older_than_time, $save_callback);
				if($empty) $empty = $empty2;
				continue;
			}
			if($delete_older_than_time and filemtime($path) >= $delete_older_than_time)
			{
				$empty = false; 
				continue;
			}
			if($save_callback and $save_callback($path))
			{
				$empty = false; 
				continue;
			}
			if(!unlink($path)) 
			{
				trigger_error("Could not delete file: $path");
				$empty = false; 
				continue;
			}
		}		
		closedir($dh);
		if($empty) 
		{
			if(rmdir($directory)) return true;
			trigger_error("Could not delete dir: $directory");
			return false;
		}
		return false;
	}
	
	static public function GetMicroseconds() 
	{
    	list($usec, $sec) = explode(" ", microtime());
    	return ((float)$usec + (float)$sec);
	}
	
	static public function GetDurationAsString($seconds) 
	{
  		$ds = floor($seconds / 86400);
  		$seconds -= $ds * 86400;
  		$hs = floor($seconds / 3600);
  		$seconds -= $hs * 3600;
  		$ms = floor($seconds / 60);
  		$seconds -= $ms * 60;
		
		$str = "";
  		if($ds)
		{
			if(count($ds) == 1) $str .= "$ds day ";
			else $str .= "$ds days ";
		}
  		$str .= "$hs:$ms:$seconds";
  		return $str;
	}
}

//Misc::ClearDirectory("c:/temp/3", null, "@/temp[^/]*$@");
//print(Misc::GetCommandOpt2("r", "def")."\n\n");

include_once("logger.php");

//used to amass errors and brake execution when too much
class Counter
{	
	function __construct($name, $max_count)
	{
		$this->name = $name;
		$this->max_count = $max_count;
	}
	private $name;
	private $max_count;
	
	public function Increment()
	{
		if($this->max_count < 0) return;
		$this->count++;
		if($this->count >= $this->max_count)	/*throw new Exception*/Logger::Quit("Counter $this->name reached $this->max_count", 1);
	}
	
	public function Reset()
	{
		$this->count = 0;
	}
	
	/*public function Set($increment=true)
	{
		if(!$increment)
		{
			$this->count = 0;
			return;			
		}
		if($this->max_count < 0) return;
		$this->count++;
		if($this->count > $this->max_count)	throw new Exception("Counter $this->name exceeded $this->max_count");			
	}*/
		
	public function Count()
	{
		return $this->count;
	}
	private $count;
}


?>