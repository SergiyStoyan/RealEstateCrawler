<?
//********************************************************************************************
//Author: Sergey Stoyan, CliverSoft.com
//        http://cliversoft.com
//        stoyan@cliversoft.com
//        sergey.stoyan@gmail.com
//        27 February 2007
//Copyright: (C) 2007, Sergey Stoyan
//********************************************************************************************


include_once("menu1.def.php");
include_once("base/back.php");
include_once("base/table.php");

class users_table extends Table
{
	function __construct()
	{
		$columns = array("#"=>'id', "Created"=>'created_date', "User"=>'name', "Level"=>'level', "Email"=>'email', "");
		$sql = "SELECT id, created_date, name, level, email FROM users ORDER BY level DESC";
        parent::__construct($columns, $sql, 0, "list");
        parent::add_delete_column("ATTENTION!\\nSelected user and all related information will be deleted. Proceed?");
	}

	function on_delete()
	{
		$sql = "SELECT * FROM users WHERE id=".$_REQUEST['id'];
		$result = self::query($sql);
		$values = mysql_fetch_assoc($result);
		if($values['level'] == 'admin')
		{
			$_SESSION['error'] = "You cannot delete admin. First change level of the user and then delete it.";
			return;
		}
		$sql = "DELETE FROM users WHERE level<>'admin' AND id=".$_REQUEST['id'];
		self::query($sql);
	}

	function build_row($db_row)
	{
		$key_id = $db_row["id"];
		$date = substr($db_row["created_date"], 0, 10);
		$edit = "<a href='update_user.php?action=update&id=$key_id' title='edit'>edit</a>";

		$db_row['created_date'] = $date;
		$db_row[] = $edit;
		return parent::build_row($db_row);
	}
}
?>