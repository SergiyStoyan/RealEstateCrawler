<?
//********************************************************************************************
//Author: Sergey Stoyan, CliverSoft.com
//        http://cliversoft.com
//        stoyan@cliversoft.com
//        sergey.stoyan@gmail.com
//        1 February 2012
//Copyright: (C) 2012, Sergey Stoyan
//********************************************************************************************

echo phpinfo();exit();


$ABSPATH = dirname(__FILE__)."/..";
include_once("$ABSPATH/common/logger.php");

$v = "queens zhopa";
			if(preg_match("@([a-z]s)(?=$|[^\w])@is", $v, $res)) Logger::Warning($res);
			$v_ = preg_replace("@([a-z])s(?=$|[^\w])@is", "$1's", $v);
			Logger::Warning($v_);
			exit;


$r = microtime(true);
Logger::Write2((int)($r * 10000));
$r = microtime(true);
Logger::Write2((int)($r * 10000));
exit();
Logger::Write2(strtotime("2010-01-01 00:00:00"));
Logger::Write2($r - strtotime("2010-01-01 00:00:00"));

class btest
{
	public static function ClassName()
	{
		if(!self::$class_name) self::$class_name = get_called_class();
		return self::$class_name;
	}
	static private $class_name;
	
}


class test2 extends btest
{
	
}

class_alias('test2', 'test') or Logger::Quit("Could not alias test class");

Logger::Write2(test::ClassName());


?>