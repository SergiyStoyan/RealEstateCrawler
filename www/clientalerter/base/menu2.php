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

class Menu2 extends Base
{
	protected $items = array();
	protected $class = "menu2";

	function __construct($items, $class='')
	{
		parent::__construct($class);
		$this->items = $items;
	}

	public function Display()
	{
        $html = "<table class='".$this->class."'><tr>\n<td>";
		$html .= join("</td>\n<td>|</td>\n<td>", array_values($this->items));
        $html .= "</td>\n</tr></table>";
        print $html;
		print("\n<br>\n");
	}
}


?>