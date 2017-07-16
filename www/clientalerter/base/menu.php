<?

//********************************************************************************************
//Author: Sergey Stoyan, CliverSoft.com
//        http://cliversoft.com
//        stoyan@cliversoft.com
//        sergey.stoyan@gmail.com
//        27 February 2007
//Copyright: (C) 2007, Sergey Stoyan
//********************************************************************************************

include_once("utilities.php");
include_once("base.php");

class Menu extends Base
{
	private $items = array();
	protected $selected_class;

	public function __construct($items, $class='menu', $selected_class='selected_item')
	{
		parent::__construct($class);
		$this->items = $items;
		$this->selected_class = $selected_class;
	}

	public function Display()
	{
		$this_page = Utilities::GetCurrentFile();

		print "<div class='$this->class' style='width:100%;'><table width='100%'><tr><td align='left'>\n";
		//print "<table width='100%'><tr><td>\n";

		print "<table><tr>\n";
		foreach($this->items as $item => $url)
		{
			$class = "";
			if(strstr($url, $this_page) or (isset($_REQUEST['page']) and strstr($url, $_REQUEST['page']))) print("<td class='".$this->selected_class."'><a href='$url'>$item</a></td>\n");
		//	else print("<td class='".$this->class."'><a href='$url'>$item</a></td>\n");
			else print("<td><a href='$url'>$item</a></td>\n");
			print("<td>&nbsp;</td>\n");
		}
		print "</tr></table>\n";

		print "</td><td style='text-align:right;'>";
//		print "<table><tr><td><a href='login.php?action'>Logout</a></td><td>";
//		print "</tr><tr>";
//		print "</td>&nbsp;<td>";
		//print "You are logged as <b>".$_SESSION["user_name"]."</b>&nbsp;";
//		print "</td></tr></table>";
		print "</td></tr></table></div>\n";
		print "<br>\n";
	}
}





?>