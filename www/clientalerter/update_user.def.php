<?
//********************************************************************************************
//Author: Sergey Stoyan, CliverSoft.com
//        http://cliversoft.com
//        stoyan@cliversoft.com
//        sergey.stoyan@gmail.com
//        27 February 2007
//Copyright: (C) 2007, Sergey Stoyan
//********************************************************************************************

include_once("base/back.php");
include_once("menu1.def.php");

include_once("base/update.php");
class update_user extends Update
{
	function get_form_parameters()
	{
		if(!$_REQUEST['id']) $values = $_REQUEST;
		else
		{
			$sql = "SELECT id, name, password, email, level, created_date FROM users WHERE id=".$_REQUEST['id'];
			$result = self::query($sql);
			$values = mysql_fetch_assoc($result);
		}
		$parameters["User"] = "<input type='text' name='name' value='".$values["name"]."'>";
		$parameters["Password"] = "<input type='password'  name='password' value='".$values["password"]."'>";
		$parameters["Confirm Password"] = "<input type='password' name='password2' value='".$values["password2"]."'>";
		$parameters["Level"] = self::BuildSelectParameter('level', $values["level"], array('user', 'admin'));
		$parameters["Email"] = "<input type='text' name='email' value='".$values["email"]."'>";
		$parameters[] = "<input type='hidden' name='id' value='".$values["id"]."'>";
		return $parameters;
	}

	function save()
	{
		if($_REQUEST['id']) $sql = "UPDATE users SET level='".addslashes($_REQUEST['level'])."', name='".addslashes($_REQUEST['name'])."', password='".addslashes($_REQUEST['password'])."', email='".addslashes($_REQUEST['email'])."' WHERE id=".$_REQUEST['id'];
		else $sql = "INSERT INTO users SET level='".addslashes($_REQUEST['level'])."', name='".addslashes($_REQUEST['name'])."', password='".addslashes($_REQUEST['password'])."', email='".addslashes($_REQUEST['email'])."', created_date=NOW()";
		return self::query($sql);
	}

	function validate()
	{
	    if(!$_REQUEST["name"]) $errors[] = "User field cannot be empty";
	    if(strlen($_REQUEST["password"]) < 5) $errors[] = "Password is too short";
	    if($_REQUEST["password"] != $_REQUEST["password2"]) $errors[] = "Password is not confirmed properly";
	    if(!$_REQUEST["email"]) $errors[] = "Email field cannot be empty";
	    if($_REQUEST["level"] != 'admin')
	    {
			$sql = "SELECT * FROM users WHERE id=".$_REQUEST['id'];
			$result = self::query($sql);
			$values = mysql_fetch_assoc($result);
			if($values['level'] == 'admin')
			{
				$sql = "SELECT COUNT(id) FROM users WHERE level='admin'";
				$result = self::query($sql);
				$values = mysql_fetch_assoc($result);
				if(count($values) < 2)
				$errors[] = "You cannot discard the last admin";
			}
		}

	    if(count($errors))
	    {
	    	$this->error($errors);
	    	return false;
	    }
	    return true;
	}
}
?>