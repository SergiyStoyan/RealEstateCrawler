<?
//********************************************************************************************
//Author: Sergey Stoyan, CliverSoft.com
//        http://cliversoft.com
//        stoyan@cliversoft.com
//        sergey.stoyan@gmail.com
//        27 February 2007
//Copyright: (C) 2007, Sergey Stoyan
//********************************************************************************************


include_once("db_connection.php");
include_once("base/utilities.php");
include_once("base/base.php");

switch($_REQUEST['data'])
{
	case "reports":

		include_once("reports.def.php");
		$sql = $sql = reports::GetSqlByRequest();
		header("Content-Type: text/csv/tab");
		header("Content-Disposition: attachment; filename=reports.tab");
		print Utilities::PrintAsTab($sql);

	break;
	default:
		Base::_error("Error: unknown data export request");
	break;
}


?>
