<?

//********************************************************************************************
//Author: Sergey Stoyan, CliverSoft.com
//        http://cliversoft.com
//        stoyan@cliversoft.com
//        sergey.stoyan@gmail.com
//        27 February 2007
//Copyright: (C) 2007, Sergey Stoyan
//********************************************************************************************

include_once("base.php");
include_once("back.php");
include_once("utilities.php");

class Update extends Base
{
	private $form_parameters = array();
	public $BlockId = "update_block";

	public function __construct($class='update')
	{
		parent::__construct($class);
		switch($_REQUEST['action'])
		{
			case 'save':
				if($this->validate() and $this->save())	header("Location: ".Back::GetBackUrlFromSession());
					//print("<script>document.location.href='".Back::GetBackUrlFromSession()."'</script>");
			break;
			default:
				Back::SaveBackUrl2Session();
			break;
		}
		$this->form_parameters = $this->get_form_parameters();
	}
	
	static public function GetEnumValues($table, $field)
	{
	    $result = self::query("SHOW COLUMNS FROM $table WHERE Field = '$field'");
		$r = mysql_fetch_assoc($result);
	    preg_match("@^(enum|set)\((.*?)\)$@i", $r["Type"], $matches);
	    preg_match_all("@[^,']+@is", $matches[2], $matches);
	    return $matches[0];
	}

	public static function BuildSelectParameter($name, $value, $options)
	{
		$str = "<select name='".$name."'><option";
		foreach($options as $o)
		{
			if($value == $o) $str .= " selected";
			$str .= ">$o<option";
		}
		$str .= "</select>";
		return $str;
	}

	function get_form_parameters()
	{
		return $this->form_parameters;
	}

	function save()
	{
		return false;
	}

	public function Display()
	{
		$block_id = $this->BlockId;
		$form_id = "form_".$this->BlockId;
		$class = $this->class;
        $action = Utilities::GetCurrentFile();
		print(<<<STRING
<form action="$action" id="$form_id" method="POST" enctype="multipart/form-data">
<table class='$class'>
STRING
		);
		
		$additional_html = "";
		foreach($this->form_parameters as $field_name=>$field_html)
			if($field_name and !is_numeric($field_name)) print("<tr><td>".$field_name.":</td><td>".$field_html."</td></tr>");
			else $additional_html .= $field_html;

		print(<<<STRING
<input type="hidden" name="action" value="save">
<tr><td></td><td align="right"><a href="#" onclick="javascript: getElementById('$form_id').submit();" title="save">Save</a></td>
</tr>
</table>
$additional_html
</form>
STRING
		);
	}

	function validate()
	{
		return true;
	}
}


?>