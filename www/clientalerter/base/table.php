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
include_once(dirname(__FILE__)."/../db_connection.php");
include_once("utilities.php");

class Table extends Base
{
	protected $columns = array();  
	protected $sql;
	protected $rows_per_page = 50;

	private $delete_ask_message;
	private $delete_id;

	private $even_row_class;

	public function __construct($columns, $sql, $rows_per_page, $class='list', $even_row_class='')
	{
		parent::__construct($class);
		$this->even_row_class = $even_row_class;

		if(isset($_REQUEST['action']) and $_REQUEST['action'] == "delete")
		{
			$this->on_delete();
			$url = getenv("HTTP_REFERER");   // print("!".$url);exit();
			//$url = Utilities::GetCurrentPage();
			header("Location: $url");
			exit();
		}

		if(!$columns)
		{
			$columns = array();
			$res = mysql_query($sql);
			$cs = mysql_num_fields($res);
			for($i = 0; $i < $cs; $i++)
			{
				$cn = mysql_field_name($res, $i);
				$columns[$cn] = $cn;
			}
		}

		$this->columns = $columns;
		$this->sql = $sql;

		if(isset($_COOKIE["row_number_per_page"]) and $_COOKIE["row_number_per_page"] > 0) $this->rows_per_page = $_COOKIE["row_number_per_page"];
		elseif($rows_per_page > 1) $this->rows_per_page = $rows_per_page;

		if(!empty($_SESSION['error']))
		{
			self::error($_SESSION['error']);
			unset($_SESSION['error']);
		}
	}

	protected function add_delete_column($ask_message="ATTENTION!\\nSelected row and all the related information will be deleted. Proceed?", $delete_id='id')
	{
		$this->delete_ask_message = $ask_message;
		$this->delete_id = $delete_id;
		$this->columns = Utilities::ArrayInsert2ArrayByKey($this->columns, "-1", array("_delete"=>""));
	}

	function on_delete()
	{
	}

	function build_row(&$db_row)
	{
		if(isset($this->columns["_delete"])) $db_row["_delete"] = "<a href='#' onclick='return _delete(\"".$db_row[$this->delete_id]."\", this);' title='delete'>X</a>";

        foreach($db_row as $name=>$value)
        {
			$value = preg_replace('/'.chr(160).'/is', ' ', $value);
			$value = preg_replace('/^\s+|\s\s+|\s+$/is', '', $value);
			$value = preg_replace('@[\n\r]+@is', '<br/>', $value);
			$value = preg_replace("@(?<=^|[\s:>]|&nbsp;)(www\.[\w-]+\.(?:[\w-]+\.)*[a-z]{2,4})(?=[^\w]|&nbsp;|$)@is", "<a href='http://$1'>$1</a>", $value);
			$value = preg_replace("@(?<=^|[^\w']|&nbsp;)(https?://.*?)(?=[<\s]|&nbsp;|$)@is", "<a href='$1'>$1</a>", $value);
			$db_row[$name] = $value;
		}
	}

	public function Display()
	{
		/*print("<script type='text/javascript' src='base/sorttable.js'></script>");  */
		//print("<script type='text/javascript' src='base/resizable-tables.js'></script>");
		print("<script type='text/javascript' src='base/utilities.js'></script>");
		print("<script type='text/javascript' src='base/prototype.js'></script>");
		print("<script type='text/javascript' src='base/fastinit.js'></script>");
		print("<script type='text/javascript' src='base/tablekit.js'></script>");

		if($this->delete_ask_message) print(<<<STR
<script>
function _delete(id, clicked_e)
{
	for(e = clicked_e; e.nodeName != 'TR'; e = e.parentNode);
	row = e;
	c = row.className;
	row.className += ' edit';
	if(confirm("$this->delete_ask_message")) location.href='?action=delete&id=' + id;
	row.className = c;
	return false;
}
</script>
STR
);

        if(empty($_REQUEST['table_page'])) $_REQUEST['table_page'] = 1;
		$sql = preg_replace("@SELECT .*? FROM @is", "SELECT COUNT(*) AS count FROM ", $this->sql, 1);
		$result = self::query($sql);
		$r = mysql_fetch_assoc($result);
		$rows_number = $r['count'];
		$pages = array();
		$last_page = ceil($rows_number / $this->rows_per_page);
		$PAGE_RADIUS = 10;
		for($page = 1; $page <= $last_page; $page++)
		{
			if($page == $_REQUEST['table_page']) $pages[] = "<span class='selected_page'>$page</span>";
			elseif(abs($_REQUEST['table_page'] - $page) % $PAGE_RADIUS == 0 or $page == 1 or ($page > $_REQUEST['table_page'] - $PAGE_RADIUS and $page < $_REQUEST['table_page'] + $PAGE_RADIUS) or $page == $last_page)
			{
				$_get['table_page'] = $page;
				$pages[] = "<a href='?".Utilities::BuildSuccessorRequest($_get)."'>".$page."</a>";
			}
			else
			{
				//if($pages[count($pages) - 1] != "...") $pages[] = "...";				
			}			
		}
		$pagination_pages = "";	
		if(count($pages) > 0) $pagination_pages = "page: ".join(" ", $pages);

		$pagination_arrows = "";
		if($_REQUEST['table_page'] > 1)
		{
			$_get['table_page'] = $_REQUEST['table_page'] - 1;
			$pagination_arrows .= "<a href='?".Utilities::BuildSuccessorRequest($_get)."' title='Previous page'>&laquo;&laquo;&laquo;</a>&nbsp;\n";
		}
		else $pagination_arrows .= "&laquo;&laquo;&laquo;&nbsp;\n";
		if($_REQUEST['table_page'] * $this->rows_per_page < $rows_number)
		{
			$_get['table_page'] = $_REQUEST['table_page'] + 1;
			$pagination_arrows .= "<a href='?".Utilities::BuildSuccessorRequest($_get)."' title='Next page'>&raquo;&raquo;&raquo;</a>\n";
		}
		else $pagination_arrows .= "&raquo;&raquo;&raquo;\n";

		//print("<table><tr><td><table width='100%'><tr><td>$pagination_pages</td><td align='right'>$pagination_arrows</td></tr></table></td></tr><tr><td>");
		$pagination_menu = "<div class='pagination'><span class='arrow'>$pagination_arrows</span>&nbsp;&nbsp;&nbsp;&nbsp;$pagination_pages</div>";
		//print("<br>");

		print("<div class='pagination'>Found $rows_number records</div>".$pagination_menu);

		print "<table class='sortable resizable ".$this->class."'>\n";
		print("<tr>\n");
		foreach($this->columns as $column=>$field)
		{
			if(preg_match("@^_@", $column)) $column = "";
			if(!$column) $class = "class='nosort'";
			else $class = "";
			print("<th $class>$column</th>\n");
		}
		print("</tr>\n");
		$sql = $this->sql." LIMIT ".($_REQUEST['table_page'] - 1) * $this->rows_per_page.", ".($this->rows_per_page);
		$result = self::query($sql);
		$even_row = false;
		while($db_row = mysql_fetch_assoc($result))
		{
			$this->build_row($db_row);
			if($even_row and $this->even_row_class) $class = "class='".$this->even_row_class."'";
			else $class = "";
			$even_row = !$even_row;
			print("<tr $class>\n");
			foreach($this->columns as $column=>$field)
			{
				if($field) $cell = $db_row[$field];
				else $cell = $db_row[$column];
				print("<td>$cell</td>\n");
			}
			print("</tr>\n");
		}
		print("</table>\n");

		$rows_per_page_menu = "<span class='table_pager'>(show &nbsp;<input type='text' value='".$this->rows_per_page."' onchange='SetCookie(\"row_number_per_page\", value, 0, \"/".Utilities::GetCurrentPage()."\"); window.location.reload(true);' style='width:40px;'>&nbsp; rows per page)</span>";
        print("$rows_per_page_menu<br>");
		//print("</td></tr><tr><td><table width='100%'><tr><td>$pagination_pages</td><td align='right'>$pagination_arrows</td></tr></table></td></tr></table>");
		print($pagination_menu);
		print("</div>");
	}
}





?>