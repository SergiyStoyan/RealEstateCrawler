<?
//********************************************************************************************
//Author: Sergey Stoyan, CliverSoft.com
//        http://cliversoft.com
//        stoyan@cliversoft.com
//        sergey.stoyan@gmail.com
//        1 February 2012
//Copyright: (C) 2012, Sergey Stoyan
//********************************************************************************************

$ABSPATH = dirname(__FILE__)."/..";

include_once("$ABSPATH/common/html_utilities.php");

class ValueParser
{		
	static public function Parse(&$rd, &$pd)
	{	
		if(!isset($pd['headline'])) $pd['headline'] = Html::PrepareField($rd['headline']);
		if(!isset($pd['description'])) $pd['description'] = Html::PrepareField($rd['description']);	
	
		if(array_key_exists('is_sold', $rd))
		{//sale
			self::ParseIsSold($rd, $pd);
		}
		else
		{//auction
		}
		
		self::ParseBedroomNumber($rd, $pd);
		self::ParsePrice($rd, $pd);
		self::ParseType($rd, $pd);
		self::ParseFeatures($rd, $pd);
		self::ParseStatus($rd, $pd);
		self::ParseTenure($rd, $pd);
	}
	
	private static $r1 = "(?<=^|[^\w])";
	private static $r2 = "(?=$|[^\w])";
	
	static private function ParseBedroomNumber(&$rd, &$pd, &$matches=false)
	{			
		$r = self::ParseBedroomNumber_($pd['headline'], $matches) or $r = self::ParseBedroomNumber_($pd['description'], $matches);
		$pd['bedroom_number'] = $r;
	}
	
	static private function ParseBedroomNumber_($text, &$matches=false)
	{	
		$bedroom_number = null;
	
		$bedroom1 = " (?:(?:double |large |well proportioned |good sized? |principal |secondary )?bedrooms?|beds?)(?=[^\w]|$)";		
		//$bedroom1 = " (?:bedrooms?|beds?)(?=[^\w]|$)";
		$bedroom2 = "(?<=^|[^\w])(?:Bedroom(?:s|\(s\))?|Bed(?:s|\(s\))?):? ";
				
		if(preg_match("@".self::$r1."(\d+)$bedroom1".self::$r2."@is", $text, $res, PREG_OFFSET_CAPTURE)) $bedroom_number = $res[1][0];
		elseif(preg_match("@".self::$r1."(one)$bedroom1".self::$r2."@is", $text, $res, PREG_OFFSET_CAPTURE)) $bedroom_number = 1;
		elseif(preg_match("@".self::$r1."(two)$bedroom1".self::$r2."@is", $text, $res, PREG_OFFSET_CAPTURE)) $bedroom_number = 2;
		elseif(preg_match("@".self::$r1."(three)$bedroom1".self::$r2."@is", $text, $res, PREG_OFFSET_CAPTURE)) $bedroom_number = 3;
		elseif(preg_match("@".self::$r1."(four)$bedroom1".self::$r2."@is", $text, $res, PREG_OFFSET_CAPTURE)) $bedroom_number = 4;
		elseif(preg_match("@".self::$r1."(five)$bedroom1".self::$r2."@is", $text, $res, PREG_OFFSET_CAPTURE)) $bedroom_number = 5;
		elseif(preg_match("@".self::$r1."$bedroom2(\d+)".self::$r2."@s", $text, $res, PREG_OFFSET_CAPTURE)) $bedroom_number = $res[1][0];
		elseif(preg_match("@".self::$r1."(studio)".self::$r2."@is", $text, $res, PREG_OFFSET_CAPTURE)) $bedroom_number = 1;
		
		if($matches !== false) $matches[] = array('text'=>$text, 'matches'=>array($res[1]));
		return $bedroom_number;
	}
	
	static private function ParseType(&$rd, &$pd, &$matches=false)
	{			
		$r = self::ParseType_($pd['headline'], $matches) or $r = self::ParseType_($pd['description'], $matches);
		$pd['type'] = $r;
	}
	
	static private function ParseType_($text, &$matches=false)
	{			
		$type = null;
		
		if(preg_match("@".self::$r1."(bungalow)".self::$r2."@is", $text, $res, PREG_OFFSET_CAPTURE)) $type = "bungalow";
		elseif(preg_match("@".self::$r1."(land|plot)".self::$r2."@is", $text, $res, PREG_OFFSET_CAPTURE)) $type = "land";
		elseif(preg_match("@".self::$r1."(house|cottege|home|farmhouse)".self::$r2."@is", $text, $res, PREG_OFFSET_CAPTURE)) $type = "house";
		elseif(preg_match("@".self::$r1."(flat|apartment|maisonette|studio)".self::$r2."@is", $text, $res, PREG_OFFSET_CAPTURE)) $type = "flat";
		elseif(preg_match("@".self::$r1."(warehouse|retail premises|retail unit|retail shop|industrial unit|fri lease)".self::$r2."@is", $text, $res, PREG_OFFSET_CAPTURE)) $type = "commercial";
			
		if($matches !== false) $matches[] = array('text'=>$text, 'matches'=>array($res[1]));
		return $type;	
	}
				
	static private function ParsePrice(&$rd, &$pd, &$matches=false)
	{			
		$r = self::ParsePrice_($rd['headline'], $matches) or $r = self::ParsePrice_($rd['description'], $matches) or $r = self::ParsePrice_2($rd['headline'], $matches);
		
		$price = preg_replace("@[^\d]+@s", "", $r);
		if(!$price) $price = 0;
		$pd['price'] = $price;
	}
	
	static private function ParsePrice_($text, &$matches=false)
	{
		$price0 = "@((?:£|".chr(163)."|&pound;|&#163;|GBP)(?:\s+|<.*?>)*\d[\s\d,]*[\d])".self::$r2."@is";
		if(!preg_match($price0, $text, $res, PREG_OFFSET_CAPTURE)) return;
		$price = $res[1][0];
		$price = Html::PrepareField($price);
		
		if($matches !== false) $matches[] = array('text'=>$text, 'matches'=>array($res[1]));
		return $price;
	}
	
	static private function ParsePrice_2($text, &$matches=false)
	{
		$price0 = "@(?:\s+|<.*?>)(\d+[\s\d,](?:\d{3}[\s\d,])*000)".self::$r2."@is";
		if(!preg_match($price0, $text, $res, PREG_OFFSET_CAPTURE)) return;
		$price = $res[1][0];
		$price = Html::PrepareField($price);
		
		if($matches !== false) $matches[] = array('text'=>$text, 'matches'=>array($res[1]));
		return $price;
	}
		
	static private function ParseStatus(&$rd, &$pd, &$matches=false)
	{					
		$ss1 = self::ParseStatus_($pd['headline'], $matches);
		$ss2 = self::ParseStatus_($pd['description'], $matches);
		
		$ss = array_merge($ss1, $ss2);
		$pd['status'] = join(",", array_keys($ss));
	}
	
	static private function ParseStatus_($text, &$matches=false)
	{
		$statuses = array();
		
		if($matches !== false) $ms = array(); else $ms = false;
		
		if(preg_match("@".self::$r1."(receipt of an offer|submit any higher offers|public notice|(?<!roof top|satellite|speech|intercom) receivers|(?<!not all )mortgagees?|notice of offer)".self::$r2."@is", $text, $res, PREG_OFFSET_CAPTURE))
		{
			if($matches !== false) $ms[] = $res[1];
			$statuses["repossession"] = 1;
		}
		elseif(self::pattern_not_within_pattern_match($text, "repossessed", "may be repossessed if you", $ms))
		{
			$statuses["repossession"] = 1;
		}
				
		if(preg_match("@".self::$r1."(buy to let|btl|investors?|p\.c\.m\.|pcm|tenanted|portfolio|registered rent|fully let|long lease|yield|multi let|hmo|multiple occupation|(?<!proof of )income|(?<!sales and )rental)".self::$r2."@is", $text, $res, PREG_OFFSET_CAPTURE))
		{
			if($matches !== false) $ms[] = $res[1];
			$statuses["investment"] = 1;
		}
		elseif(self::pattern_not_within_pattern_match($text, "investment", "please contact our investment team|\/investments", $ms) or self::pattern_not_within_pattern_match($text, "tenant(?!s)", "intending purchasers? or tenant|purchasers\s*\/\s*tenants", $ms) or self::pattern_not_within_pattern_match($text, "tenants", "prospective purchasers\s*\/\s*tenants|prospective buyers or tenants", $ms))
		{
			$statuses["investment"] = 1;
		}
		
		if(preg_match("@".self::$r1."(requiring refurbishment|requires refurbishment|some refurbishment|needs renovation|requires renovation|needs work|re\-furbishment|disrepair|unfinished|un\-finished|unconditional offers|incomplete|diy project|diy enthusiasts?|keen diy|diy experts?|for diy|with diy|need of repair|water damage|fixer upper|neglected|remedial work|unmodernised|some repair|cosmetic(?! finish| mirror))".self::$r2."@is", $text, $res, PREG_OFFSET_CAPTURE))
		{
			if($matches !== false) $ms[] = $res[1];
			$statuses["light_renovation"] = 1;
		}
		elseif(self::pattern_plus_pattern_match($text, "refurbishment|renovation", "need of", $ms, 20)
			//or self::pattern_plus_pattern_match($text, "loft conversion", "potential", $ms, 20)
		)
		{
			$statuses["light_renovation"] = 1;
		}
		elseif(self::pattern_and_not_pattern_match($text, "modernisation|updating|improvement|modernising|total refurbishment|renovation|redecoration|tlc", "undergone|underwent|completed|finished|undertaken|subject of|carried", $ms, 5)
		)
		{
			$statuses["light_renovation"] = 1;
		} 
		
		if(preg_match("@".self::$r1."(subsidence|additional dwelling|building plot|dilapidated|unmortgageable|development potential|development opportunity|derelict|potential redevelopment|opportunity redevelopment|development site|shell condition|cash offers|cash purchase|cash buyers|fire damaged|self build|structural repair|development plot|conversion opportunity|part completed|part\-completed|part developed|construction dwellings|change of use|erection|partially converted|partially built|partially complete|partially developed|uninhabitable|poor condition|building site|poor repair|development land|development possibilities|possible development|poor state|cash purchaser(?! then we will))".self::$r2."@is", $text, $res, PREG_OFFSET_CAPTURE))
		{
			if($matches !== false) $ms[] = $res[1];
			$statuses["major_renovation"] = 1;
		}
		elseif(self::pattern_not_within_pattern_match($text, "cash buyer", "sell to a cash buyer|your cash buyer with|cash buyer available", $ms))
		{
			$statuses["major_renovation"] = 1;
		}
		elseif(self::pattern_and_not_pattern_match($text, "upgrading", "undergone|underwent|completed|finished|undertaken", $ms, 5)
			or self::pattern_and_not_pattern_match($text, "own risk", "school catchment", $ms)
		)
		{
			$statuses["major_renovation"] = 1;
		} 
		
		if(preg_match("@".self::$r1."(quick sale|fast sale|below market value|BMV)".self::$r2."@is", $text, $res, PREG_OFFSET_CAPTURE))
		{
			if($matches !== false) $ms[] = $res[1];
			$statuses["motivated_seller"] = 1;
		}
		
		if($matches !== false) $matches[] = array('text'=>$text, 'matches'=>$ms);
		return $statuses; 	
	}
			
	static private function ParseTenure(&$rd, &$pd, &$matches=false)
	{			
		$r = self::ParseTenure_($pd['headline'], $matches) or $r = self::ParseTenure_($pd['description'], $matches);
		$pd['tenure'] = $r;
	}
	
	static private function ParseTenure_($text, &$matches=false)
	{	
		$tenure = null;				
		if(preg_match("@".self::$r1."(free\-?hold)".self::$r2."@is", $text, $res, PREG_OFFSET_CAPTURE)) $tenure = "freehold";
		elseif(preg_match("@".self::$r1."(lease\-?hold)".self::$r2."@is", $text, $res, PREG_OFFSET_CAPTURE)) $tenure = "leasehold";
		elseif(preg_match("@".self::$r1."(share of freehold|shared freehold)".self::$r2."@is", $text, $res, PREG_OFFSET_CAPTURE)) $tenure = "shared_freehold";
		
		if($matches !== false) $matches[] = array('text'=>$text, 'matches'=>array($res[1]));
		return $tenure;
	}
				
	static private function ParseIsSold(&$rd, &$pd, &$matches=false)
	{			
		$r = self::ParseIsSold_($pd['headline'], $matches) or $r = self::ParseIsSold_($pd['description'], $matches);
		$pd['is_sold'] = $r;
	}
	
	static private function ParseIsSold_($text, &$matches=false)
	{			
		if($matches !== false) $ms = array(); else $ms = false;
		
		$is_sold = 0;	
		
		if(preg_match("@".self::$r1."(sold stc|sale agreed|STC|sstc)".self::$r2."@is", $text, $res, PREG_OFFSET_CAPTURE))
		{
			if($matches !== false) $ms[] = $res[1];
			$is_sold = 1;
		}
		elseif(self::pattern_plus_pattern_match($text, "sold", "subject to contract", $ms, 5)) 
		{
			$is_sold = 1;
		}
		elseif(self::pattern_not_within_pattern_match($text, "sold", "sold on behalf", $ms)) 
		{
			$is_sold = 1;
		}
			
		if($matches !== false) $matches[] = array('text'=>$text, 'matches'=>$ms);
		return $is_sold;	
	}
				
	static private function ParseFeatures(&$rd, &$pd, &$matches=false)
	{			
		$fs1 = self::ParseFeatures_($pd['headline'], $matches);
		$fs2 = self::ParseFeatures_($pd['description'], $matches);
		
		$fs = array_merge($fs1, $fs2);
		$pd['features'] = join(",", array_keys($fs));
	}
	
	static private function ParseFeatures_($text, &$matches=false)
	{	
		$features = array();
				
		if($matches !== false) $ms = array(); else $ms = false;
		
		if(preg_match("@".self::$r1."(basement(?! flat)|cellar)".self::$r2."@is", $text, $res, PREG_OFFSET_CAPTURE))
		{
			$features["basement"] = 1;
			if($matches !== false) $ms[] = $res[1];
		}		
		
		if(preg_match("@".self::$r1."(no deposit|deposit paid|no money down)".self::$r2."@is", $text, $res, PREG_OFFSET_CAPTURE)) 
		{
			$features["gifted_deposit"] = 1;
			if($matches !== false) $ms[] = $res[1];
		}		
		
		if(preg_match("@".self::$r1."(no chain|chain free|no\-chain|chain\-free|no onward chain)".self::$r2."@is", $text, $res, PREG_OFFSET_CAPTURE)) 
		{
			$features["no_chain"] = 1;
			if($matches !== false) $ms[] = $res[1];
		}		
		
		if(preg_match("@".self::$r1."(no stamp duty|stamp duty paid|stamp duty exempt|stamp duty threshold)".self::$r2."@is", $text, $res, PREG_OFFSET_CAPTURE)) 
		{
			$features["no_stamp_duty"] = 1; 
			if($matches !== false) $ms[] = $res[1];
		}		
		
		if(preg_match("@".self::$r1."(attic|loft)".self::$r2."@is", $text, $res, PREG_OFFSET_CAPTURE)) 
		{
			$features["attic"] = 1;
			if($matches !== false) $ms[] = $res[1];
		}
		
		if(preg_match("@".self::$r1."(vacant possession(?!".self::$word_r1."{0,3} (?:upon|on) completion))".self::$r2."@is", $text, $res, PREG_OFFSET_CAPTURE))
		{
			$features["vacant_possession"] = 1;
			if($matches !== false) $ms[] = $res[1];
		}		
		
		if(preg_match("@".self::$r1."((?<!garden and )outbuildings)".self::$r2."@is", $text, $res, PREG_OFFSET_CAPTURE)) 
		{
			$features["outbuildings"] = 1;
			if($matches !== false) $ms[] = $res[1];
		}
		
		if(self::pattern_and_not_pattern_match($text, "end of terrace", "flat", $ms)) 
		{	
			$features["end_of_terrace"] = 1;
		}		
				
		if(preg_match("@".self::$r1."(under offer)".self::$r2."@is", $text, $res, PREG_OFFSET_CAPTURE))
		{
			$features["under_offer"] = 1;
			if($matches !== false) $ms[] = $res[1];
		}		
		
		if(preg_match("@".self::$r1."(garage)".self::$r2."@is", $text, $res, PREG_OFFSET_CAPTURE))
		{
			$features["garage"] = 1;
			if($matches !== false) $ms[] = $res[1];
		}		
		
		if(preg_match("@".self::$r1."(corner plot|corner position)".self::$r2."@is", $text, $res, PREG_OFFSET_CAPTURE))
		{
			$features["corner_plot"] = 1;
			if($matches !== false) $ms[] = $res[1];
		}		
		
		if(preg_match("@".self::$r1."(stpp|stp|s\.t\.p|s\.t\.p\.p\.|subject to planning|subject to necessary|subject to the necessary|subject to relevant|subject to the relevant|planning consents?|outline planning|detailed planning|to pp|pp for|p\.p\. for|P\/P|dpp|opp development|planning for|copies of (?:the )?plans|subject to consents?|planning enquiries|subject to planning|lapsed planning|expired planning|planning passed|(?<!references to condition and necessary )permission for)".self::$r2."@is", $text, $res, PREG_OFFSET_CAPTURE))
		{
			$features["planning_permission"] = 1;			
			if($matches !== false) $ms[] = $res[1];
		}
		elseif(self::pattern_not_within_pattern_match($text, "planning permission", "any reference is made to planning permission|unable to confirm that the appropriate planning permission|cannot guarantee that building regulations or planning permission has been approved|not checked the legal title, nor any planning permission", $ms))
		{
			$features["planning_permission"] = 1;
		}
		elseif(self::pattern_and_not_pattern_match($text, "planning permission", "conservatory", $ms))
		{
			$features["planning_permission"] = 1;
		}
				
		if(preg_match("@".self::$r1."(short lease)".self::$r2."@is", $text, $res, PREG_OFFSET_CAPTURE))
		{
			$features["short_lease"] = 1;
			if($matches !== false) $ms[] = $res[1];
		}	
		
		if(!preg_match("@".self::$r1."(tenant|income|commercial)".self::$r2."@is", $text, $res)
			and (preg_match("@".self::$r1.self::$word_r1."{0,3}(?:lease|remain(?:ing|s)) \w{0,10} (\d{1,3}) (?:year|yr)s?".self::$word_r2."{0,3}".self::$r2."@is", $text, $res, PREG_OFFSET_CAPTURE)
				or preg_match("@".self::$r1.self::$word_r1."{0,3}(\d{1,3}) (year|yr)s? \w{0,10} (?:lease|remain(?:ing|s))".self::$word_r2."{0,3}".self::$r2."@is", $text, $res, PREG_OFFSET_CAPTURE)
				)
			)
		{
			if($matches !== false) $ms[] = $res[1];
			$text2 = preg_quote($res[0][0]);
			preg_match("@$text2@is", "NHBC|tenant|income|commercial") or $leasehold_years = $res[1][0];
		}
		if(isset($leasehold_years))
		{//print($leasehold_years."\n\n\n\n");
			if($leasehold_years < 70) $features["short_lease"] = 1;
			//else unset($features["short_lease"]);
		}
		
		if($matches !== false) $matches[] = array('text'=>$text, 'matches'=>$ms);
		return $features;
	}
			
	static private function pattern_plus_pattern_match($text, $pattern1, $pattern2, &$ms=false, $radius_in_words=-1)
	{		
		if($radius_in_words < 0) $regex = "@(.*".self::$r1.")($pattern1)(".self::$r2.".*)@is";
		else $regex = "@(".self::$r1.self::$word_r1."{0,$radius_in_words})($pattern1)(".self::$word_r2."{0,$radius_in_words}".self::$r2.")@is";		
		
		for($offset = 0; preg_match($regex, $text, $res, PREG_OFFSET_CAPTURE, $offset); $offset = $res[2][1] + 1)
		{
			if($ms !== false) $ms[] = $res[2];
			$text2 = $res[1][0];
			$offset2 = $offset + $res[1][1];
			if(preg_match("@".self::$r1."($pattern2)".self::$r2."@is", $text2, $res2, PREG_OFFSET_CAPTURE))
			{
				if($ms !== false) $ms[] = $offset2 + $res2[1][1];	
				return true;
			}
			$text2 = $res[3][0];
			$offset2 = $offset + $res[3][1];
			if(preg_match("@".self::$r1."($pattern2)".self::$r2."@is", $text2, $res2, PREG_OFFSET_CAPTURE))
			{
				if($ms !== false) $ms[] = $offset2 + $res2[1][1];	
				return true;
			}
		}
		return false;		
	}
	
	static private $word_r1 = "(?:[^\s]+[^\w]+)";
	static private $word_r2 = "(?:[^\w]+[^\s]+)";
	
	static private function pattern_and_not_pattern_match($text, $pattern1, $pattern2, &$ms=false, $radius_in_words=-1)
	{		
		if($radius_in_words < 0) $regex = "@.*".self::$r1."($pattern1)".self::$r2.".*@is";
		else $regex = "@".self::$r1.self::$word_r1."{0,$radius_in_words}($pattern1)".self::$word_r2."{0,$radius_in_words}".self::$r2."@is";
		
		for($offset = 0; preg_match($regex, $text, $res, PREG_OFFSET_CAPTURE, $offset); $offset = $res[1][1] + 1)
		{
			if($ms !== false) $ms[] = $res[1];
			$text2 = $res[0][0];
			$offset2 = $offset + $res[0][1];
			if(!preg_match("@".self::$r1."($pattern2)".self::$r2."@is", $text2, $res2, PREG_OFFSET_CAPTURE)) return true;
			if($ms !== false) $ms[] = $offset2 + $res2[1][1];
		}
		
		return false;
	}
	
	private static function pattern_not_within_pattern_match($text, $pattern1, $pattern2, &$ms=false)
	{	
		preg_match_all("@".self::$r1."($pattern1)".self::$r2."@is", $text, $res1, PREG_OFFSET_CAPTURE);
		preg_match_all("@".self::$r1."($pattern2)".self::$r2."@is", $text, $res2, PREG_OFFSET_CAPTURE);
		
		$rs2 = $res2[1];
		$r2 = reset($rs2);
		foreach($res1[1] as $r1) 
		{
			if($ms !== false) $ms[] = $r1;
			for(; $r2; $r2 = next($rs2))
			{
				if($r1[1] < $r2[1]) return true;
				if($ms !== false) $ms[] = $r2;
				if($r1[1] + strlen($r1[0]) <= $r2[1] + strlen($r2[0])) break;
				continue;
			}
			if(!$r2) return true;
		}
		return false;
	}
}

/*
//$h = "upgrading modernisation,  rt rt rt rt rt rt undergone|underwent|completed|finished|undertaken";

$h = "refurbishment qq qq qq qq  qq qq need of";

//$d = "Well maintained receivers, students speech  speech receivers1, intercom -students no, speech receivers1, intercom receivers1 Description";

$d = 
<<<STR

Bairstow Eves Countrywide 8 Market Place, Derby, DE1 3QE 01332 4
STR;

$p = array('headline'=>$h, 'description'=>$d);
$vp = new ValueParser($p);
$ms = array();
//$v = $vp->ParseFeatures($ms);
$v = $vp->ParseStatus($ms);

print $v."<br>";
print_r($ms);

*/

?>