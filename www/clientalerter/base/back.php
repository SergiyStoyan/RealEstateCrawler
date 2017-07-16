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

class Back extends Base
{
	protected $value = "Back";
	protected $title = "return back";

	public function __construct($class='')
	{
		parent::__construct($class);
		Back::SaveBackUrl2Session();
	}

	public function Display()
	{
		print($this->GetHtml());
	}

	public function GetHtml()
	{
		$back = Back::GetBackUrlFromSession();
		if(!$back) $back = "javascript:history.go(-1);";
		return "<a class='".$this->class."' href='$back' title='".$this->title."'>".$this->value."</a>";
	}

	static public function SaveBackUrl2Session()
	{
		$referer = getenv("HTTP_REFERER");
		if($referer and !strstr($referer, Utilities::GetCurrentPage()))	$_SESSION["back_url"] =	$referer;
		//print "!!!".$_SESSION["back_url"];
	}

	static public function GetBackUrlFromSession()
	{
		return isset($_SESSION["back_url"]) ? $_SESSION["back_url"] : null;
	}
}





?>