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
include_once("utilities.php");
include_once("base/back.php");

class Owner extends Base
{
	protected $columns = array();

	public function __construct($columns, $sql, $class='owner')
	{
		parent::__construct($class);
		$this->columns = $columns;
		$this->sql = $sql;
	}

	public function Display()
	{
		$result = mysql_query($this->sql) or $this->Error("Query failed: ".$this->sql."<br>".mysql_error());
		$db_row = mysql_fetch_assoc($result);
		$table = $this->build_array($db_row);
        $html = "<table class='".$this->class."'><tr><th>";
		$html .= join("</th><th>", array_keys($table));
		$html .= "</th></tr><tr><td>";
		$html .= join("</td><td>", array_values($table));
        $html .= "</td></tr></table>";
        print $html;
        print "<br>";
	}

	function build_array($db_row)
	{
		$table = array();
		//if(!is_array($db_row)) return $table;
		$values = array_values($db_row);
		for($i = 0; $i < count($this->columns); $i++)
			$table[$this->columns[$i]] = $values[$i];
		return $table;
	}
}


?>