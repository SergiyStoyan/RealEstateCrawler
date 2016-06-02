<?
//********************************************************************************************
//Author: Sergey Stoyan, CliverSoft.com
//        http://cliversoft.com
//        stoyan@cliversoft.com
//        sergey.stoyan@gmail.com
//        27 February 2007
//Copyright: (C) 2007, Sergey Stoyan
//********************************************************************************************

class Shell
{	
	/*//for some reason it does not work in some cases
	static public function GetCommandOpt2($opt, $default_value_if_exists=true)
	{
		$opts = getopt("$opt");
		if($opts and array_key_exists($opt, $opts))
		{
			if(!$opts[$opt] and $default_value_if_exists) return $default_value_if_exists;	
			else return $opts[$opt];	
		}
		return null;
	}*/
	
	static public function GetCommandOpt($opt, $default_value_if_exists=true)
	{
		global $argv;
		isset($argv) or trigger_error("Command line parameters are not acessible.");
		foreach($argv as $a) 
		{
			if(preg_match("@^-$opt(?:=(.*)|$)@s", $a, $res)) 
			{
				if($res[1]) return $res[1];
				else return $default_value_if_exists;
			}
		}
		return null;
	}
	
	static public function GetCommandOpts()
	{
		global $argv;
		isset($argv) or trigger_error("Command line paramters are not accessible.");
		$opts = array();
		foreach($argv as $a) if(preg_match("@^-([a-z])(?:=([^\s]*))?@is", $a, $res)) $opts[$res[1]] = isset($res[2])?$res[2]:null;
		return $opts;
	}

	static public function IsProcessAlive($pid)
	{
		exec("ps $pid", $state);
		return count($state) >= 2;
	}

	static public function IsStartFile($file=null)
	{
		$stack = debug_backtrace();
		$file or $file = $stack[0]['file'];
		if($stack[sizeof($stack) - 1]['file'] == $file) return true;
		return false;
	}

	static public function ExitIfTheScriptRunsAlready($start_file=null, $activity_dir=null, $kill_old_process_if_no_file_written_within_secs=200)
	{
		if(!$start_file)
		{
			$trace = debug_backtrace();
			$start_file = $trace[sizeof($trace) - 1]['file'];
		}
		$start_file = trim($start_file);
		$start_file_name = preg_replace('#.*[\\\/]#is', '', $start_file);
		$command = "ps -ef";
		$o = shell_exec($command);
		$ps = preg_split("@\n@is", $o);
		array_shift($ps);//remove header
		$processes = array();
		$my_pid = getmypid();
		foreach($ps as $p)
		{
			if(!$p) continue;
			if(!preg_match("@\d+\:\d+\:\d+\s+(?'cmd'.*)@is", $p, $res)) die("No cmd found!");			
			//print($res['cmd']."\n--\n");							
			if(!preg_match("@^(/.+/)?php\d*\s+(?'cmd2'.*)@is", $res['cmd'], $res)) continue; //it is not php command
			if(!preg_match("@(^|/)".preg_quote($start_file_name)."(\s|$)@", $res['cmd2'])) continue;
			if(!preg_match("@\s(?'pid'\d+)\s@is", $p, $res)) die("No pid found!");
			if($my_pid == $res['pid']) continue;
			$script_dir = readlink("/proc/".$res['pid']."/cwd");//not always ps returns absolute path to the script; so it should be gotten separately 
			//print("$script_dir/$start_file_name != $start_file\n@@@\n");
			if("$script_dir/$start_file_name" != $start_file) continue;
			$processes[$res['pid']] = $p;
		}
    	if(!count($processes)) return;
   		if($activity_dir)
   		{
   			if(!file_exists($activity_dir)) print("Warning: no $log_dir exists!\n");
    		$old_logs = glob("$activity_dir/*");
    		if(count($old_logs) < 1) print("Warning: no log was found!\n");
   			$last_log_time = 0;
			foreach($old_logs as $log)
			{
				$t = filemtime($log);
				if($t > $last_log_time) $last_log_time = $t;
			}
			if($last_log_time < time() - $kill_old_process_if_no_file_written_within_secs)
			{
				foreach($processes as $pid=>$p)
				{
					$command = "kill -9 $pid";
					trigger_error("Killing the old process: $command :".shell_exec($command));
				}
				flush();
				return;
			}
		}
    	trigger_error("Exit as the script $start_file runs already:\n".join("\n", $processes)."\n");
		exit();
	}
	
	static public function GetProcessOwner() 
	{
		if(!function_exists('posix_getpwuid')) return "-- not defined --";
		$pu = posix_getpwuid(posix_geteuid());
		return $pu['name'];
	}	
	
	static public function GetYesOrNoFromConsole() 
	{
		$console = fopen('php://stdin', 'r');
		while(true)
		{
			print("\nPlease enter 'y' (yes) or 'n' (no):\n");
			$reply = fgetc($console);
			$reply = strtolower($reply);
			if($reply == "y") return true;
			if($reply == "n") return false;
		}
	}	
}

class ModeTemplate
{ 	
	//values are command line options
   	//const TEST_PAGE = "t[f]";
   	//const DEBUG = "d";
   	//const MAIN = "";
	
	static public function PrintUsage(array $descriptions)
	{
		if(count(array_flip($descriptions)) != count($descriptions)) trigger_error("Some mode descriptions are not unique.");
		$r = new ReflectionClass(get_called_class());
		$ms = $r->getConstants();
		print("\n----- USAGE: -----\n");
		foreach($ms as $n=>$os)	
		{
			if($o = self::get_main_option($os)) $line = "-".$o;
			else $line = "<no option>";
			isset($descriptions[$os]) or trigger_error("Mode '$n' has no description.");
			print($line.$descriptions[$os].";\n");
		}
		print("\n");
	}
	
	static private function determine_mode()
	{		
		$r = new ReflectionClass(get_called_class());
		$ms = $r->getConstants();
		$oos2ns = array();
		$oos2oss = array();
		foreach($ms as $n=>$os)
		{
			$os = preg_replace("@\s+@s", "", $os);
			while(true)
			{
				$os_ = preg_replace("@[\[\]]+@s", "", $os);
				$oos = self::get_options_ordered($os_);
				if(isset($oos2oss[$oos])) trigger_error("Mode $n=>'$os' defines not unique option collection: '$oos'");
				$oos2oss[$oos] = $os;
				$oos2ns[$oos] = $n;
				
				$os2 = preg_replace("@(?<=\[)\w(?=\w*\])@s", "", $os, 1);
				if($os == $os2) break;
				$os = $os2;			
			}
		}
		self::$option2values = Shell::GetCommandOpts();
		$os = join("", array_keys(self::$option2values)) or $os = "";
		$oos = self::get_options_ordered($os);
		if(!isset($oos2ns[$oos])) throw new Exception("Entered option collection '$oos' has no respective mode.");
		//if(!isset($o2ns[""])) throw new Exception("No optionless mode exists.");
		self::$mode_name = $oos2ns[$oos];
		self::$mode_options = $oos2oss[$oos];
		return self::$mode_name;
	}
	private static $mode_options = null;
	private static $mode_name = null;
	private static $option2values = null;
	
	private static function get_options_ordered($s)
	{
		$ss = str_split($s);
		$ss = array_unique($ss);
		sort($ss);
		return implode($ss);
	}
	
	static public function This()
	{		
		self::$mode_name or self::determine_mode();
		return self::$mode_options;
	}
	
	static public function Name()
	{		
		self::$mode_name or self::determine_mode();
		return self::$mode_name;
	}
	
	static private function get_main_option($options)
	{
		if(preg_match("@(?:\[.*?\])*([a-z])@is", $options, $res)) return $res[1];
	}
	
	static public function OptionValue($option=null)
	{		
		self::$mode_name or self::determine_mode();
		if(!$option) $option = self::get_main_option(self::$mode_options);
		return self::$option2values[$option];
	}
}



?>