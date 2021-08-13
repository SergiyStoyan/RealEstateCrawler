<?
//********************************************************************************************
//Author: Sergey Stoyan, CliverSoft.com
//        http://cliversoft.com
//        stoyan@cliversoft.com
//        sergey.stoyan@gmail.com
//        1 February 2012
//Copyright: (C) 2012, Sergey Stoyan
//********************************************************************************************
error_reporting(E_ALL);
set_time_limit(0);
ini_set('memory_limit', '-1');

$ABSPATH = dirname(__FILE__);

include_once("$ABSPATH/common/shell_utilities.php");
Shell::ExitIfTheScriptRunsAlready();

include_once("$ABSPATH/common/logger.php");
include_once("$ABSPATH/constants.php");
Logger::Init(Constants::LogDirectory."/_alert_sender", 10);
//Logger::$CopyToConsole = 1;

include_once("$ABSPATH/common/html_utilities.php");
include_once("$ABSPATH/common/db.php");
include_once("$ABSPATH/base/crawler6/table_routines.php");

const MAX_PRODUCTS_NUMBER_PER_EMAIL = 50;
const MAX_TRY_COUNT = 2;
const SENDER_EMAIL = "mavee.rea@gmail.com";//"deals@yourdealsdetective.co.uk";- does not work in the FROM clause
const ADMIN_EMAILS = "support@tycoonsystem.com";

Logger::Write2("Process owner: ".Shell::GetProcessOwner());
Logger::Write2("STATRED");

process_client_type("platinum");
process_client_type("gold");

Logger::Write2("COMPLETED");

function process_client_type($client_type)
{
	switch($client_type)
	{
		case 'gold':
			$where2 = "AND found_time<ADDDATE(NOW(), INTERVAL -7 DAY)";
		break;
		case 'platinum':
			$where2 = "";
		break;
		default:
			Logger::Quit("Unknown client_type '$client_type'.");
	}
	$sql = "SELECT filter_id, client_id, product_id, product_agent, product_url, product_image_path, product_description, product_price, product_town, product_status, product_features, product_postcode, matched_types, _state, try_count FROM alert_notifications n INNER JOIN alert_clients c ON n.client_id=c.id WHERE _state<>'sent' AND try_count<".MAX_TRY_COUNT." AND c.type='$client_type' $where2 ORDER BY client_id, _state DESC, found_time DESC, filter_id";	
		
	$client_id = false;
	$notifications = array();
	foreach(Db::GetArray($sql) as $n)
	{		
		if($client_id != $n['client_id'])
		{
			send_message($notifications);		
			sleep(1);//to avoid overflowing(?)
			
			$client_id = $n['client_id'];
			$notifications = array();
		}
		
		$notifications[] = $n;
	}
	send_message($notifications);
}

function send_message($notifications)
{	
	if(empty($notifications)) return;
		
	$client_id = $notifications[0]['client_id'];
	Logger::Write("Total notifications for client #$client_id: ".count($notifications));
	
	$sent_notifications = array();
	$message = "";
	$filter_id = false;
	$sent_count = 0;
	foreach($notifications as $n)
	{
		if($filter_id != $n['filter_id'])
		{
			$filter_id = $n['filter_id'];
			$filter_state = Db::GetSingleValue("SELECT state FROM alert_filters WHERE id='$filter_id'");
			//add_filter2message($message, $n);
		}	
		if($filter_state != 'active') continue;	
		$sent_notifications[] = $n;
		if(++$sent_count >= MAX_PRODUCTS_NUMBER_PER_EMAIL) break;	
		
		add_notification2message($message, $n['product_image_path'], $n['product_town'], $n['product_status'], $n['product_features'], $n['product_postcode'], $n['product_price'], $n['product_description'], $n['matched_types'], $n['product_agent'], $n['product_url']);
		
		add_filter2message($message, $n);
	}
	
	if($sent_count)
	{
		$client = Db::GetRowArray("SELECT emails, name FROM alert_clients WHERE id='$client_id'");
		complete_message($message, $client['name'], $sent_count);
		
		$subject = "Deals Detective: ".$client['name'].", $sent_count new property leads as of ".date("Y-m-d");
		$additional_headers   = array();
		$additional_headers[] = "MIME-Version: 1.0";
		$additional_headers[] = "Content-type: text/html; charset=iso-8859-1";
		$additional_headers[] = "From: Your Deals Detective <".SENDER_EMAIL.">";
		$additional_headers[] = "Reply-To: Your Deals Detective <".SENDER_EMAIL.">";
		$additional_headers[] = "Bcc: ".ADMIN_EMAILS;
		$return_path = "-f ".SENDER_EMAIL;
		
		$emails = $client['emails'];
		$state = false;			
		Logger::Write("Sending an alert with $sent_count notifications...");
		if(mail($emails, $subject, $message, implode("\r\n", $additional_headers), $return_path)) 
		{
			$state = 'sent';
		}
		else
		{				
			Logger::Error("Failed to send to $emails");
			$state = 'error';
		}
		
		$alert = false;
		foreach($sent_notifications as $n)
		{
	 		Db::SmartQuery("UPDATE alert_notifications SET _state='$state', sent_time=NOW(), try_count=try_count + 1 WHERE client_id='".$n['client_id']."' AND product_id='".$n['product_id']."'") or Logger::Quit("Can't update.");
			
			if($state == 'error' and $n['try_count'] >= MAX_TRY_COUNT) $alert = true; 
		}
		if($alert) mail(Constants::AdminEmail, "Crawler system: error by alert_sender", "Could not email a alert notification to $emails (client #$client_id)") or Logger::Error("Could not email to Constants::AdminEmail");
	}
	
	$omitted_count = 0;
	foreach($notifications as $n)
	{
		if(in_array($n, $sent_notifications)) continue;
 		Db::SmartQuery("UPDATE alert_notifications SET _state='omitted', sent_time=NOW(), try_count=try_count + 1 WHERE client_id='".$n['client_id']."' AND product_id='".$n['product_id']."'") or Logger::Quit("Can't update.");
		$omitted_count++;
	}
	if($omitted_count) Logger::Write("Omitted notifications: $omitted_count");
}

function add_filter2message(&$message, $n)
{
	$filter_id = $n['filter_id'];
	
	$af = Db::GetRowArray("SELECT * FROM alert_filters WHERE id='$filter_id'");
	$f = json_decode($af['filter'], true);
	
	$fields = array();	
	$fields[] = "Search ID: $filter_id";
	$fields[] = "Town: ".(isset($f['town'])?prepare_field($f['town']):"");
	$fields[] = "Postcode: ".(isset($f['postcode'])?strtoupper($f['postcode']):"");
	$fields[] = "Type: ".(isset($f['status'])?prepare_field($f['status']):"");
	$fields[] = "Features: ".(isset($f['features'])?prepare_field($f['features']):"");
	$fields[] = "Min price: ".(isset($f['price_min'])?prepare_price($f['price_min']):"");
	$fields[] = "Max price: ".(isset($f['price_max'])?prepare_price($f['price_max']):"");
	
	$filter = join(" | ", $fields);
	
	$message .=  <<<STRING
<div class='filter' style="font-size: 8px; font-style: italic;">
The property lead above are based on the following criteria: $filter
</div>
<hr>
<br>
STRING;
}
	
function complete_message(&$message, $client_name, $notification_count)
{	
	$message = <<<STRING
<html>
<!--head>head is not supported
<style type="text/css">
body, td, tr {font-size: 12px; font-family: Arial, Helvetica, sans-serif;}
.footer {font-size: 8px;}
.filter {font-size: 8px; font-style: italic;}
</style>
</head-->

<body style="font-size: 12px; font-family: Arial, Helvetica, sans-serif;">
Hello $client_name, here are $notification_count property alert that match your criteria.
<br>
<br>
$message
<br>
<br>
In order to find out which of these properties is the most profitable, we have also created you an easy to use <a href='http://www.tycoonsystem.com/resources/deal-analysis-tool'>deal analyser</a> which will crunch the numbers for you, saving you time and energy. <a href='http://www.tycoonsystem.com/resources/deal-analysis-tool'>Click here to access the deal analyser</a>
<br>
<br>
Please do not reply to this email and send any questions or feedback to <a href="mailto:support@tycoonsystem.com">support@tycoonsystem.com</a>
<br>
<br>
<div class='footer' style="font-size: 8px;">
You are receiving these emails as you have opted to receive regular email alerts. If you would like to change your alert criteria or opt-out of future emails please send an email to <a href="mailto:support@tycoonsystem.com">support@tycoonsystem.com</a>
</div>
</body>
</html>
STRING;
}

function add_notification2message(&$message, $image_path, $town, $status, $features, $postcode, $price, $description, $matched_types, $agent, $url)
{	//Connells - Central Milton Keynes 01908 711248
	$price = prepare_price($price);
	
	if(!$image_path) $image_path = Constants::DefaultImageName;
	$image_url = Constants::ImageBaseUrl."/".$image_path;
	
	$fields = array();
	$fields[] = prepare_field($town);
	$fields[] = prepare_field($status, true);
	//$fields[] = prepare_field($features);
	$fields[] = strtoupper($postcode);
	$fields[] = $price;
	$header = implode(', ', array_filter($fields));
	
	$matched_types = json_decode($matched_types, true);
	if(isset($matched_types['status'])) $matched_status = prepare_field(join(" | ", $matched_types['status']));
	else $matched_status = "";
	if(isset($matched_types['features'])) $matched_features = prepare_field(join(" | ", $matched_types['features']));
	else $matched_features = "";
	
	$vendor = "";			
	if(preg_match("@(.*?)\s*((?:\+?[\d][\d\-\s]{6,})[\n\,]?){1,}(.*)@is", $agent, $res))
	{
		$vendor = trim($res[1])." ".trim($res[3])."<br>";
		$vendor_phone = preg_replace("@[\n\,]+@is", "<br>", trim($res[2]));
	}
	else
	{
		$vendor_phone = "";		
	}	
	$vendor .= "<a href='$url'>Click for more info</a>";
	
	$m = <<<STRING
	<table><tr><td>
		<b>$header</b>
	</td></tr><tr><td>
		$description
	</td></tr><tr><td>
		<table><tr><td>
			<table><tr><td>
				Type:
			</td><td>
				$matched_status
			</td></tr><tr><td>
				Features:
			</td><td>
				$matched_features
			</td></tr></table>
		</td><td>
			<table><tr><td>
				Vendor:
			</td><td>
				$vendor
			</td></tr><tr><td>
				Phone:
			</td><td>
				$vendor_phone
			</td></tr></table>
		</td></tr></table>
	</td></tr></table>
STRING;
	
	$message .= $m;
}

function prepare_field($field, $enum_type=false)
{
	if(!$enum_type)	return preg_replace("@_@is", " ", ucwords(trim($field)));
	
	$vs = array();
	foreach(explode(",", $field) as $v) $vs[] = prepare_field($v);
	return implode(", ", $vs);
}

function prepare_price($price)
{
	$price = preg_replace("@[^\d]+@s", "", $price);
	for($i = strlen($price) - 3; $i > 0; $i -= 3) $price = substr($price, 0, $i).",".substr($price, $i);
	return "£".$price;
}

?>