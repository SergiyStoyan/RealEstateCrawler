<?
//********************************************************************************************
//Author: Sergey Stoyan, CliverSoft.com
//        http://cliversoft.com
//        stoyan@cliversoft.com
//        sergey.stoyan@gmail.com
//        27 February 2007
//Copyright: (C) 2007, Sergey Stoyan
//********************************************************************************************

include_once(dirname(__FILE__)."/../constants.php");
include_once("base.php");

class Login extends Base
{
	function __construct($class='')
	{
		parent::__construct($class);
		switch($_REQUEST['action'])
		{
			case "logout":
				self::logout();
			break;
			case "login":
				if(self::login()) self::locate();
				else self::_error("You entered non-registered user name or incorrect password.");
			break;
			default:
			break;
		}
	}

	public function Display()
	{
  	    $name = $_REQUEST['Name'];

		$class = $this->class;
		print(<<<STRING
<form action='' method='POST'>
<table class=$class>
<tr><td align='center'>
$error
<table>
<tr><td>Name:</td><td><input type="text" name="Name" value='$name'></td></tr>
<tr><td>Password:</td><td><input type="password" name="Password"></td></tr>
<tr><td>(<input type="checkbox" name="Remember" value='on'> remember me)</td><td align='right'>
<input id="submit" type="submit" name="Login" value="Login"></td></tr>
</table>
</td></tr>
</table>
<input type="hidden" name="action" value="login">
</form>
STRING
		);
	}

	static function login()
	{
		$old_user = false;
		if(strlen($_COOKIE['permanent_session_id']) > 10)
		{
			$sql = "SELECT * FROM users WHERE session_id='".addslashes($_COOKIE['permanent_session_id'])."'";
			$result = Base::_query($sql);
			$r = mysql_fetch_assoc($result);
			if($r) $old_user = true;
		}

		if(!$old_user)
		{
			$sql = "SELECT * FROM users WHERE name='".addslashes($_REQUEST['Name'])."' AND password='".addslashes($_REQUEST['Password'])."'";
			$result = Base::_query($sql);
			$r = mysql_fetch_assoc($result);
			if(!$r) return false;
		}

		$_SESSION["user_id"] = $r["id"];
		$_SESSION["level"] = $r["level"];
		$_SESSION["user_name"] = $r["name"];

	    if(!$old_user)
	    {
	    	$sql = "UPDATE users SET session_id='".session_id()."' WHERE id=".$_SESSION['user_id'];
			Base::_query($sql);
		}
        if($_REQUEST['Remember']) setcookie("permanent_session_id", session_id(), time() + 360*24*3600, "/");

		return true;
	}

	/*Must be invoked for each secured page.*/
	public static function Authorize(&$user_permits=array())
	{
		self::$user_permits = array_merge(self::$user_permits, $user_permits);

		session_start();
		ini_set("session.gc_maxlifetime", Constants::SessionTimeout);

		$_SESSION["user_id"] or self::login();
        self::locate();
	}

	static $user_permits = array("_UNDEFINED"=>array("login.php"));

	static function locate()
	{
        $user_level = $_SESSION["level"];
		$asked_file = Utilities::GetCurrentFile();

		if(!$_SESSION["user_id"] or empty(self::$user_permits[$user_level]) or !$asked_file)
		{
			if(Utilities::GetCurrentFile() != self::$user_permits['_UNDEFINED'][0]) header("Location: ".self::$user_permits['_UNDEFINED'][0]);
			else return;
		}

		if($asked_file == self::$user_permits['_UNDEFINED'][0])
		{
	        $referer = getenv("HTTP_REFERER");
			if($referer and strpos($referer, $asked_file) === false) header("Location: $referer");
			else header("Location: ".self::$user_permits[$user_level][0]);
		}
                      //print(self::$user_permits[$user_level][$asked_file]."###$user_level!!!$asked_file@@@");
		if($redirect_file = self::$user_permits[$user_level][$asked_file] and $redirect_file != $asked_file) header("Location: $redirect_file");
		elseif(in_array($asked_file, self::$user_permits[$user_level])) return;//header("Location: $asked_file");
		else
		{
			self::_error("User level '$user_level' has no permition for '$asked_file'");
			exit();
		}
	}

	static function logout()
	{
		if($_SESSION['user_id'])
		{
	       	$sql = "UPDATE users SET session_id='' WHERE id=".$_SESSION['user_id'];
			Base::_query($sql);
        }
		//session_unset();
		session_destroy();
		setcookie("permanent_session_id", "", time() - 1, "\/");
	}
}


?>
