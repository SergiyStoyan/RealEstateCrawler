<?
//********************************************************************************************
//Author: Sergey Stoyan, CliverSoft.com
//        http://cliversoft.com
//        stoyan@cliversoft.com
//        sergey.stoyan@gmail.com
//        27 February 2007
//Copyright: (C) 2007, Sergey Stoyan
//********************************************************************************************

//include_once("tracer.php");

class Logger
{
	static public $CopyToConsole = false;
	static public $WriteToLog = true;
	static public $PrintInvokerArgs = false;
	static public $IgnoreMessagePatterns = array();
	static public $ConsoleIsWebBrowser = null;
	static public $LogPermissionMode = 0777;

	static private $log = false;
	static private $log_dir = false;

	static public function Init($log_dir=null, $delete_logs_older_than_days=10, $time_zone='Europe/London')
	{
		if(self::$initiated) return;
		self::$initiated = true;	
		
		if(!self::$WriteToLog) return;

		date_default_timezone_set($time_zone);
		self::GetLogDir($log_dir);

		if($delete_logs_older_than_days > 0) $old_logs_threshold_time = time() - ($delete_logs_older_than_days * 24 * 60 * 60);
		else $old_logs_threshold_time = 0;
		//$old_logs = glob(self::$log_dir."/*.*.*.*.*.*.log");
		$old_logs = glob(self::$log_dir."/*");
		foreach($old_logs as $log){
			//	is_file()
			if(filemtime($log) < $old_logs_threshold_time and is_file($log)) unlink($log);
		}

		self::$log_file = self::$log_dir."/".date("Y.m.d.H.i.s").".log";
		self::$log = fopen(self::$log_file, "w") or die("Could not create a log file: ".self::$log_file);
		if(!chmod(self::$log_file, self::$LogPermissionMode))
		{
			print("ERROR: Could not set permission for the log file '".self::$log_file."'");
			exit();
		}
	}
	static private $initiated = false;
	
	static private $log_file;
	
	static public function CurrentLogFile()
	{
		return self::$log_file;
	}

	//must be public to be called!
	static public function write_error($errno, $errstr, $errfile, $errline)
	{
		$errfile = preg_replace('#.*[\\\/]#is', '', $errfile);
		self::Write2("$errstr\nIn $errfile,$errline", 'ERROR(I)');
		return true;
	}

	static public function GetLogDir($log_dir=null)
	{
		if(self::$log_dir) return self::$log_dir;
		
		$log_dir = trim($log_dir);

		if(!$log_dir)
		{
			$stack = debug_backtrace();
			$file = $stack[sizeof($stack) - 1]['file'];
			preg_match('#(.*)[\\\/](.*)\..*#is', $file, $res);
			$log_dir = $res[1]."/_logs_".$res[2]."/";
		}
		elseif(substr($log_dir, -1) == '/')
		{
			$stack = debug_backtrace();
			$file = $stack[sizeof($stack) - 1]['file'];
			preg_match('#(.*)[\\\/](.*)\..*#is', $file, $res);
			$log_dir .= "_logs_".$res[2]."/";
		}

		if(!file_exists($log_dir))
		{
			if(!mkdir($log_dir, self::$LogPermissionMode, true))
			{
				print("ERROR: Could not create the log dir '".$log_dir."'");
				exit();
			}
			if(!chmod($log_dir, self::$LogPermissionMode))
			{
				print("ERROR: Could not set permission for the log dir '".$log_dir."'");
				exit();
			}
		}

		self::$log_dir = $log_dir;
		return self::$log_dir;
	}

	//must be public to be called!
	static public function write_last_message()
	{
		//get fatal error because it is not intercepted by set_error_handler
		if($e = error_get_last() and $e['type'] == E_ERROR) Logger::Write2($e['message']."\nIn ".$e['file'].",".$e['line'], 'ERROR(I)');

		if(count(self::$ignored_message_counts) > 0) foreach(self::$ignored_message_counts as $k=>$v) self::Write("WARNING: Ignored message count: ".$v." matched to '".$k."'");
	}
	static private $ignored_message_counts = array();

	//$m can be string, array of strings, Exception
	static public function Write($m, $label=null, $caller_number=null)
	{
		if(!self::$initiated) self::Init();

		if($m instanceof Exception)
		{
			$m = $m->getMessage()."\nIn ".$m->getFile().",".$m->getLine()."\nTrace: ".$m->getTraceAsString();
			if(!$label) $label = 'ERROR(E)';
		}
		elseif(!is_string($m))
		{
			if($label and is_array($m)) 
			{
				$m = join("\n- ", $m);
				if($m) $m = "\n- $m";
			}
			else 
			{
				ob_start();
				print_r($m);
				$m = trim(ob_get_clean());
			}
		}

		foreach(self::$IgnoreMessagePatterns as $imp=>$v)
		{
		 	if(!preg_match($imp, $m)) continue;
			if(!isset(self::$ignored_message_counts[$imp])) self::$ignored_message_counts[$imp] = 1;
			else self::$ignored_message_counts[$imp]++;
			return;
		}

		$label = strtoupper($label);

		if($caller_number !== null) $m = $m."\n".self::GetCallStackInfoString($caller_number, __FILE__);
				
		$m = date("[Y-m-d H:i:s]")."$label: $m\n";

        if(self::$WriteToLog)
        {
			fwrite(self::$log, $m);
			fflush(self::$log);
		}
		
		if(self::$CopyToConsole or !self::$WriteToLog)
		{
			if(self::$ConsoleIsWebBrowser) 
			{
				$m = preg_replace('#[\r\n]+#is', "<br>", $m);
				switch($label)
				{
					case 'ERROR':
					case 'ERROR(I)':
					case 'ERROR(E)':
					case 'FATAL_ERROR':
					case 'ERROR_':
						$m = "<div style='color:red;'>$m</div>";
						break;
					case 'WARNING':
					case 'WARNING_':
						$m = "<div style='color:green;'>$m</div>";
						break;
					default:
						$m = "<div>$m</div>";
						break;
				}
			}
			print($m);
			flush();
		}

		if($label == 'FATAL_ERROR') exit();
	}

	static public function GetCallStackInfoString($caller_number, $after_file=null)
	{
		$stack = debug_backtrace();
		$stack_length = sizeof($stack);	
		if($after_file)
		{
			for($i = 0; $i < $stack_length; $i++) if($stack[$i]['file'] != $after_file) break;
		}
		$caller_number += $i;
		if($caller_number >= $stack_length) $caller_number = $stack_length - 1;
		$caller = $stack[$caller_number];
		if($caller_number + 1 < $stack_length)
		{
			if(!preg_match("@^include_once|require_once|include|require$@s", $stack[$caller_number + 1]['function'])) $function = $stack[$caller_number + 1]['function']."()";
			else $function = "GLOBAL CODE (by ".preg_replace('#.*[\\\/]#is', '', $stack[$caller_number + 1]['file']).",".$stack[$caller_number + 1]['line'].")";
		}
		else $function = "GLOBAL CODE";		
		$file = preg_replace('#.*[\\\/]#is', '', $caller['file']);
		return "In ".$file.",".$caller['line'].",$function";
	}
	
	static public function IsRunningInWebContext() 
	{
		return !(php_sapi_name() == 'cli' && empty($_SERVER['REMOTE_ADDR']));
	}

	//the same like Write + copy to console
	static public function Write2($m, $label=null, $caller_number=null)
	{
		$c2c = self::$CopyToConsole;
		self::$CopyToConsole = true;
		self::Write($m, $label, $caller_number);
		self::$CopyToConsole = $c2c;
	}

	static public function Error($m, $caller_number=0)
	{
		self::Write2($m, 'ERROR', $caller_number);
	}

	static public function Error_($m)
	{
		self::Write2($m, 'ERROR_');
	}

	static public function Warning($str, $caller_number=0)
	{
		self::Write2($str, 'WARNING', $caller_number);
	}

	static public function Warning_($str)
	{
		self::Write2($str, 'WARNING_');
	}

	static public function Quit($m, $caller_number=0)
	{
		self::Write2($m, 'FATAL_ERROR', $caller_number);
	}
}

register_shutdown_function("Logger::write_last_message");
set_error_handler("Logger::write_error", error_reporting());		
Logger::$ConsoleIsWebBrowser = Logger::IsRunningInWebContext();	


/*
Logger_::Init('Europe/Helsinki', null, E_ALL);
Logger_::$CopyToConsole = true;
Logger_::Write2(null/1);

$q = 1/0;

function t()
{
  try{  throw new Exception("werq");
}
		catch (Exception $e)
		{
			Logger_::Error($e);
		}
}
t();
*/
?>