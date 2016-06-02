<?
session_start();

if(!isset($_REQUEST["Captcha"]))
{	
//	if(!preg_match("@captcha\.php@is", $_SERVER['SCRIPT_NAME']))
//	//it is included to another script to put captcha image
//	{
//		print('<img id="captcha" src="" title="captcha image"/> <input name="Captcha" type="text" size="2">
//<script>document.getElementById("captcha").src = "../../common/captcha.php?" + new Date().getTime();</script>');		
//		extt();
//	}

////by number
//	$code=rand(1000,9999);
//	$_SESSION["CaptchaAnswer"]=$code;
//	$im = imagecreatetruecolor(50, 24);
//	$bg = imagecolorallocate($im, 22, 86, 165); //background color blue
//	$fg = imagecolorallocate($im, 255, 255, 255);//text color white
//	imagefill($im, 0, 0, $bg);
//	imagestring($im, 5, 5, 5,  $code, $fg);

////by expression1
//	$n1 = rand(0, 9);
//	$o = rand(0, 1);
//	$n2 = rand(0, 9);
//	if($o) $r = $n1 + $n2;
//	else $r = $n1 - $n2;
//	$_SESSION["CaptchaAnswer"] = $r;
//	$e = get_number_word($n1);
//	$e .= " ".($o ? "plus" : "minus");
//	$e .= " ".get_number_word($n2);
//	$im = imagecreatetruecolor(200, 24);
//	$bg = imagecolorallocate($im, 22, 86, 165); //background color blue
//	$fg = imagecolorallocate($im, 255, 255, 255);//text color white
//	imagefill($im, 0, 0, $bg);
//	imagestring($im, 5, 5, 5, $e, $fg);
	
////by expression2
	$n = rand(0, 99);
	$_SESSION["CaptchaAnswer"] = $n;	
	$n = (string)$n;	
	if(strlen($n) < 2)
		$e = get_number_word($n[0]);
	else
	{	
		if($n[0] == "1") $e = get_number_word($n);
		else
		{
			$e = get_number_word(10 * $n[0]);
			if($n[1] != "0") $e .= " ".get_number_word($n[1]);
		}
	}

	$font_i = 5;
	$fs = get_image_string_size($e, $font_i);
	$im = imagecreatetruecolor($fs[0] + 10, 17);
	$bc = imagecolorallocate($im, 22, 86, 165); //background color blue
	//imagecolortransparent($im, $bc);
	$fc = imagecolorallocate($im, 255, 255, 255);//text color white
	imagefill($im, 0, 0, $bc);
	imagestring($im, $font_i, 5, 0, $e, $fc);
	
	header("Cache-Control: no-cache, must-revalidate");
	header('Content-type: image/png');
	imagepng($im);
	imagedestroy($im);
	exit();
}

function get_image_string_size($text, $font) {
    
    // font sizes
    $width  = array(1 => 5, 6, 7, 8, 9);
    $height = array(1 => 6, 8, 13, 15, 15);
    
    $x = $width[$font] * strlen($text);
    $y = $height[$font];

    return array($x, $y);
}

function get_number_word($n)
{
	switch($n)
	{
		case 0: return "zero";
		case 1: return "one";
		case 2: return "two";
		case 3: return "three";
		case 4: return "four";
		case 5: return "five";
		case 6: return "six";
		case 7: return "seven";
		case 8: return "eight";
		case 9: return "nine";
		case 10: return "ten";
		case 11: return "eleven";
		case 12: return "twelve";
		case 13: return "thirteen";
		case 14: return "fourteen";
		case 15: return "fifteen";
		case 16: return "sixteen";
		case 17: return "seventeen";
		case 18: return "eighteen";
		case 19: return "nineteen";
		case 20: return "twenty";
		case 30: return "thirty";
		case 40: return "forty";
		case 50: return "fifty";
		case 60: return "sixty";
		case 70: return "seventy";
		case 80: return "eighty";
		case 90: return "ninety";
		//case 100: return "hundred";
		default: throw new Exception("No such case");		
	}	
}

function CheckCaptcha($auto_redirect=true)
{
	if(strlen($_POST["Captcha"]) > 0 and $_SESSION["CaptchaAnswer"] == $_POST["Captcha"])
	{
		$_SESSION["CaptchaAnswer"] = "";
		return true;
	}
	$_SESSION["CaptchaAnswer"] = "";
	if($auto_redirect)
	{
		?>
<script>
alert("Unfrotunately, the captcha answer was incorrect. Please try again.");
window.history.back();
</script>
		<?		
	}
	return false;
}

function CheckClientIp($frequency=5, $stop_for_secs=1000)
{
	session_start();
//	if(!isset($_SESSION['client_ips'])) $_SESSION['client_ips'] = array();
//	$ip = $_SERVER['REMOTE_ADDR'];
//	$ip2 = $_SERVER['HTTP_X_FORWARDED_FOR'];
//	if(!isset($_SESSION['client_ips'][$ip]))
//	{
//		$_SESSION['client_ips'][$ip]['times'] = 1;
//		$_SESSION['client_ips'][$ip]['last_time'] = time();
//		return true;
//	}
//	if($_SESSION['client_ips'][$ip]['times'] < 5)
//	{
//		$_SESSION['client_ips'][$ip]['times'] += 1;
//		$_SESSION['client_ips'][$ip]['last_time'] = time();
//		return true;
//	}
//	if(time() - $_SESSION['client_ips'][$ip]['last_time'] < $stop_for_secs)	return false;
//	else
//	{
//		$_SESSION['client_ips'][$ip]['times'] = 1;
//		$_SESSION['client_ips'][$ip]['last_time'] = time();
//		return true;		
//	}
//	return false;	
}


?>