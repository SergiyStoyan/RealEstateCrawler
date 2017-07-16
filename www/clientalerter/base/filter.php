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
//include_once("utilities.php");

class Filter extends Base
{
	private $parameters = array();

	public $BlockId = "filter_block";
	public $ShowClose = true;

	public function __construct($parameters, $show_filter_first=false, $class='filter')
	{
		parent::__construct($class);
		$this->parameters = $parameters;

		if($show_filter_first and Utilities::IsPageCalledFirst()) $_REQUEST["show_filter"] = "on";
	}

	public function IsFilterVisible()
	{
		foreach($this->parameters as $parameter_name=>$parameter_html)
		{
			preg_match("@\sid=[\'\"](.*?)[\'\"]@is", $parameter_html, $res); 
			if(!empty($_GET[$res[1]])) return true;
		}
		return !empty($_REQUEST["show_filter"]);
	}

	public function GetPageUrlWithClearedFilter()
	{
		/*
		$parameters = array();
		foreach($this->parameters as $parameter_name=>$parameter_html)
		{
			preg_match("@\sid=[\'\"](.*?)[\'\"]@is", $parameter_html, $res);
			$parameters[$res[1]] = "";
		}
		return Utilities::BuildSuccessorRequest(false, $parameters + array("show_filter"=>""));
		*/
		
		$_get = $_GET;	
		foreach($this->parameters as $parameter_name=>$parameter_html)
		{
			preg_match("@\sid=[\'\"](.*?)[\'\"]@is", $parameter_html, $res); 
			unset($_get[$res[1]]);
		}
        $_get = http_build_query($_get);
	}

	public function Display()
	{
		$block_id = $this->BlockId;
		$form_id = "form_".$this->BlockId;

		if(!self::IsFilterVisible()) $hide_filter = " style='display:none;'";
		else $hide_filter = "";
		
		$_get = $this->GetPageUrlWithClearedFilter();
        //foreach($_GET as $n=>$v) $hidden_parameters .= "<INPUT TYPE='hidden' NAME='".htmlentities($n)."' VALUE='".htmlentities($v)."' />";

		print(<<<STRING
<div id="$block_id" $hide_filter>
<form action="?$_get" id="$form_id" method='get'>
<!--a style="display:none;" id="filter_button" href="#" onclick="javascript: getElementById('$block_id').style.display='block';this.style.display='none'; return false;" title="show filter">Filter</a-->
<table class="$this->class">
<tr>
STRING
		);

		foreach($this->parameters as $parameter_name=>$parameter_html) print("<td>".$parameter_name.":</td><td>".$parameter_html."</td>");
		
		if($this->ShowClose) $sc = '<td align="right" style="border-left: #000000 1px solid;">&nbsp;<a href="?'.$_get.'" title="remove filter" style="font-style: normal;">Clear</a></td>';
		else $sc = "";

		print(<<<STRING
<td>
<input type="hidden" name="show_filter" value="on">
<!--a id='$this->BlockId apply' href="#" onclick="submit();" title="apply filter">Apply</a-->
<!--a id='$this->BlockId clear' href="?$_get" title="clear filter" style="font-style: normal;">Clear</a--></td>
$sc
</tr>
</table>
</form>
</div>
STRING
		);

	}
}


?>