<?

//********************************************************************************************
//Author: Sergey Stoyan, CliverSoft.com
//        http://cliversoft.com
//        stoyan@cliversoft.com
//        sergey.stoyan@gmail.com
//        27 February 2007
//Copyright: (C) 2007, Sergey Stoyan
//********************************************************************************************

$ABSPATH = dirname(__FILE__)."/../";

include_once("$ABSPATH/common/logger.php");
include_once("$ABSPATH/common/tracer.php");
include_once("$ABSPATH/constants.php");

Db::AddConnectionString(Constants::DataBaseHost, Constants::DataBaseUser, Constants::DataBasePassword, Constants::DataBase);

class Db
{
	public static function AddConnectionString($db_host, $db_user, $db_password, $db_name, $connection_name=null) 
	{
		$connection_name or $connection_name = self::DEFAULT_CONNECTION_NAME;
		if(isset(self::$connections[$connection_name]))
		{	
			$c = self::$connections[$connection_name];
			if($c['db_host'] != $db_host) Logger::Quit("Connection '$connection_name' already exists and has different db_host: $db_host<>".$c['db_host'], Tracer::GetCallerNumber(__FILE__));
			if($c['db_user'] != $db_user) Logger::Quit("Connection '$connection_name' already exists and has different db_user: $db_user<>".$c['db_user'], Tracer::GetCallerNumber(__FILE__));
			if($c['db_name'] != $db_name) Logger::Quit("Connection '$connection_name' already exists and has different db_name: $db_name<>".$c['db_name'], Tracer::GetCallerNumber(__FILE__));
		}
		else
		{
			foreach(self::$connections as $n=>$c)
			{
				if($c['db_host'] == $db_host and $c['db_user'] == $db_user and $c['db_name'] == $db_name) Logger::Quit("Other connection '$connection_name' has the same db_host '$db_host', db_user '$db_user' and db_name '$db_name'", Tracer::GetCallerNumber(__FILE__));
			}
		}
		self::$connections[$connection_name] = array('db_host'=>$db_host, 'db_user'=>$db_user, 'db_password'=>$db_password, 'db_name'=>$db_name);
	}
	
	private static $connections = array();	
	const DEFAULT_CONNECTION_NAME = "_DEFAULT_CONNECTION";
	
	public static function GetLinkIdentifier($connection_name=null)
	{
		return self::get_link($connection_name);
	}
	
	public static function RemoveConnection($connection_name=null) 
	{
		$db_link = self::get_link($connection_name);
		mysqli_close($db_link) or Logger::Quit("Cound not close db connection: $db_link", Tracer::GetCallerNumber(__FILE__));
		unset(self::$connections[$connection_name]);
	}
	
	private static function init_connection($connection_name) 
	{
		$c = &self::$connections[$connection_name];					
		if(isset($c['db_link']))
		{
			$db_link = $c['db_link'];
			if(!mysqli_ping($db_link))
			{
				mysqli_close($db_link) or Logger::Quit("Cound not close db connection: $db_link", Tracer::GetCallerNumber(__FILE__));
				$db_link = mysqli_connect($c['db_host'], $c['db_user'], $c['db_password']) or Logger::Quit("Host:'".$c['db_host']."' User:'".$c['db_user']."'\n", Tracer::GetCallerNumber(__FILE__));
			}
		}
		else
		{
			$db_link = mysqli_connect($c['db_host'], $c['db_user'], $c['db_password']) or Logger::Quit("Host:'".$c['db_host']."' User:'".$c['db_user']."'\n", Tracer::GetCallerNumber(__FILE__));
		}
		mysqli_select_db($db_link, $c['db_name']) or Logger::Quit("Could not select database '".$c['db_name']."'", Tracer::GetCallerNumber(__FILE__));
		self::$connections[$connection_name]['db_link'] = $db_link;		
		//Logger::Write("Db connection set: $connection_name", null, Tracer::GetCallerNumber(__FILE__));
		//Logger::Write("Db connection set: $connection_name");
	}	
				
	private static function get_link($connection_name) 
	{
		$connection_name or $connection_name = self::DEFAULT_CONNECTION_NAME;
		$c = &self::$connections[$connection_name] or Logger::Quit("Db connection '$connection_name' does not exist.", Tracer::GetCallerNumber(__FILE__));
		if(!isset($c['db_link'])) self::init_connection($connection_name);
		return $c['db_link'];
	}
		
	static public function Query($sql, $connection_name=null)
	{
		$db_link = self::get_link($connection_name);
		$result = mysqli_query($db_link, $sql);
		if(!$result)
		{
			switch(mysqli_errno($db_link))
			{
				case 1205:
					if(Tracer::GetRecursionDepth() > 9)	Logger::Quit(mysqli_error($db_link), Tracer::GetCallerNumber(__FILE__));
					Logger::Error(mysqli_error($db_link)."\nRestarting SQL:".substr($sql, 0, 50)."<...>", Tracer::GetCallerNumber(__FILE__));
					return self::Query($sql, $connection_name);					
					break;
				case 2006:
				case 2013:
					if(Tracer::GetRecursionDepth() > 3)	Logger::Quit(mysqli_error($db_link), Tracer::GetCallerNumber(__FILE__));
					Logger::Error(mysqli_error($db_link)."\nRe-establishing connection...", Tracer::GetCallerNumber(__FILE__));
					self::init_connection($connection_name);
					return self::Query($sql, $connection_name);					
					break;
				default:
					Logger::Quit(mysqli_error($db_link)."\nSQL:$sql", Tracer::GetCallerNumber(__FILE__));
			}
		}
		return $result;
	}
	
	/*static public function UnbufferedQuery($sql, $connection_name=null)
	{
		$connection_name or $connection_name = self::DEFAULT_CONNECTION_NAME;
		$result = mysqli_unbuffered_query($sql, $db_link);
		if(!$result)
		{
			if(mysqli_errno($db_link) == 1205)
			{
				Logger::Error(mysqli_error($db_link)."\nRestarting SQL:".substr($sql, 0, 50)."<...>", self::get_caller_number());
				return self::UnbufferedQuery($sql, $db_link);
			}
			Logger::Quit(mysqli_error($db_link)."\nSQL:$sql", self::get_caller_number());
		}
		return $result;
	}*/

	static public function LastAffectedRows($connection_name=null)
	{
		$db_link = self::get_link($connection_name);
		return mysqli_affected_rows($db_link);
	}

	static public function GetRowArray($sql, $connection_name=null)
	{
		$result = self::Query($sql, $connection_name);
		$array = mysqli_fetch_assoc($result);		
		mysqli_free_result($result);
		return $array;
	}

	static public function GetSingleValue($sql, $connection_name=null)
	{
		$result = self::Query($sql, $connection_name);
		$r = mysqli_fetch_row($result);		
		mysqli_free_result($result);
		if(!$r) return null;
		return $r[0];
	}

	static public function GetFirstColumnArray($sql, $connection_name=null)
	{
		$array = array();
		$result = Db::Query($sql, $connection_name);	
		while($a = mysqli_fetch_row($result)) $array[] = $a[0];
		mysqli_free_result($result);	
		return $array;
	}

	static public function GetArray($sql, $connection_name=null)
	{
		$array = array();
		$result = Db::Query($sql, $connection_name);	
		while($a = mysqli_fetch_assoc($result)) $array[] = $a;
		mysqli_free_result($result);	
		return $array;
	}
	
	/*//used to:
	//a) avoid blocking the table for a long time
	//b) save RAM		
	static public function ChunkedQuery($sql, $row_limit=100, $callback=null, $connection_name=null)
	{
		$sql .= " LIMIT $row_limit";
		while($result = Db::SmartQuery($sql, $connection_name)) if($callback and call_user_func($callback, $result2) === false) break;		
	}*/
	
	static public function SmartQuery($sql, $connection_name=null)
	{
		$result = Db::Query($sql, $connection_name);
		if($result === true) return self::LastAffectedRows($connection_name);
		$rs = array();	
		while($a = mysqli_fetch_assoc($result)) $rs[] = $a;
		mysqli_free_result($result);	
		return $rs;		
	}
	
	static public function DataBaseName($connection_name=null)
	{
		$c = &self::$connections[$connection_name];
		return $c['db_name'];		
	}
}

?>