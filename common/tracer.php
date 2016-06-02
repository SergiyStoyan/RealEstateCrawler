<?
//********************************************************************************************
//Author: Sergey Stoyan, CliverSoft.com
//        http://cliversoft.com
//        stoyan@cliversoft.com
//        sergey.stoyan@gmail.com
//        27 February 2007
//Copyright: (C) 2007, Sergey Stoyan
//********************************************************************************************

class Tracer
{
	static public function GetInvokerInfoString($after_file=__FILE__, $get_args=true)
	{
		$caller = self::GetInvokerInfo($after_file);
		$file = preg_replace('#.*[\\\/]#is', '', $caller['file']);
		if($get_args)
		{
			ob_start();
			print_r($caller['args']);
			$args = ob_get_clean();
			return "In ".$file.",".$caller['line'].", ".$caller['function']."()\nargs: ".$args;
		}
		return "In ".$file.",".$caller['line'].",".$caller['function']."()";
	}

	static public function GetInvokerInfo($after_file=__FILE__)
	{
		$stack = debug_backtrace();
		$stack_length = sizeof($stack);
		$file_passed = false;
		for($i = 0; $i < $stack_length; $i++)
		{
			if($stack[$i]['file'] == $after_file) $file_passed = true;
			elseif($file_passed) break;
		}
		if($i >= $stack_length) $i--;
		$caller = $stack[$i];
		if(++$i < $stack_length) $caller['function'] = $stack[$i]['function'];
		else $caller['function'] = "GLOBAL CODE";
		return $caller;
	}
	
	static public function GetCallStackInfoString($caller_number)
	{
		$stack = debug_backtrace();
		$stack_length = sizeof($stack);
		//$caller_number = $stack_length - 1 - $caller_number;//reverse number back
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
	
	static public function GetRecursionDepth()
	{
		$recursion_depth = 1;
		$stack = debug_backtrace();
		$stack_length = sizeof($stack);
		if($stack_length < 2) return $recursion_depth;
		$caller = $stack[1];
		for($i = 2; $i < $stack_length; $i++) if($stack[$i]['file'] == $caller['file'] and $stack[$i]['line'] == $caller['line']) $recursion_depth++;
		return $recursion_depth;
	}
	
	static public function GetCallerNumber($after_file=null)
	{
		$stack = debug_backtrace();
		$stack_length = sizeof($stack);
		$after_file or $after_file = $stack[0]['file'];
		$file_passed = false;
		for($caller_number = 0; $caller_number < $stack_length; $caller_number++)
		{
			if(preg_match("@^include_once|require_once|include|require$@s", $stack[$caller_number]['function']))
			{
				$caller_number--;
				break;
			}
			if($stack[$caller_number]['file'] == $after_file) $file_passed = true;
			elseif($file_passed) break;
		}
		if($caller_number >= $stack_length) $caller_number = $stack_length - 1;		
		//return $stack_length - 1 - $caller_number;//reversed number		
		return $caller_number;
	}
}
?>