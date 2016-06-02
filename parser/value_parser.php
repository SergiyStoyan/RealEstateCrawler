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
		//if(!isset($pd['agent'])) $pd['agent'] = Html::PrepareField($rd['agent']);	
	
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
	
	private static $span_left = "(?<=^|[^\w])";
	private static $span_right = "(?=$|[^\w])";
	
	static public function ParseBedroomNumber(&$rd, &$pd, &$matches=false)
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
				
		if(preg_match("@".self::$span_left."(\d+)$bedroom1".self::$span_right."@is", $text, $res, PREG_OFFSET_CAPTURE)) $bedroom_number = $res[1][0];
		elseif(preg_match("@".self::$span_left."(one)$bedroom1".self::$span_right."@is", $text, $res, PREG_OFFSET_CAPTURE)) $bedroom_number = 1;
		elseif(preg_match("@".self::$span_left."(two)$bedroom1".self::$span_right."@is", $text, $res, PREG_OFFSET_CAPTURE)) $bedroom_number = 2;
		elseif(preg_match("@".self::$span_left."(three)$bedroom1".self::$span_right."@is", $text, $res, PREG_OFFSET_CAPTURE)) $bedroom_number = 3;
		elseif(preg_match("@".self::$span_left."(four)$bedroom1".self::$span_right."@is", $text, $res, PREG_OFFSET_CAPTURE)) $bedroom_number = 4;
		elseif(preg_match("@".self::$span_left."(five)$bedroom1".self::$span_right."@is", $text, $res, PREG_OFFSET_CAPTURE)) $bedroom_number = 5;
		elseif(preg_match("@".self::$span_left."$bedroom2(\d+)".self::$span_right."@s", $text, $res, PREG_OFFSET_CAPTURE)) $bedroom_number = $res[1][0];
		elseif(preg_match("@".self::$span_left."(studio)".self::$span_right."@is", $text, $res, PREG_OFFSET_CAPTURE)) $bedroom_number = 1;
		
		if($matches !== false) $matches[] = array('text'=>$text, 'matches'=>array($res[1]));
		return $bedroom_number;
	}
	
	static public function ParseType(&$rd, &$pd, &$matches=false)
	{			
		$r = self::ParseType_($pd['headline'], $matches) or $r = self::ParseType_($pd['description'], $matches);
		$pd['type'] = $r;
	}
	
	static private function ParseType_($text, &$matches=false)
	{			
		$type = null;
		
		if(preg_match("@".self::$span_left."(bungalow)".self::$span_right."@is", $text, $res, PREG_OFFSET_CAPTURE)) $type = "bungalow";
		elseif(preg_match("@".self::$span_left."(land|plot)".self::$span_right."@is", $text, $res, PREG_OFFSET_CAPTURE)) $type = "land";
		elseif(preg_match("@".self::$span_left."(house|cottege|home|farmhouse)".self::$span_right."@is", $text, $res, PREG_OFFSET_CAPTURE)) $type = "house";
		elseif(preg_match("@".self::$span_left."(flat|apartment|maisonette|studio)".self::$span_right."@is", $text, $res, PREG_OFFSET_CAPTURE)) $type = "flat";
		elseif(preg_match("@".self::$span_left."(warehouse|retail premises|retail unit|retail shop|industrial unit|fri lease)".self::$span_right."@is", $text, $res, PREG_OFFSET_CAPTURE)) $type = "commercial";
			
		if($matches !== false) $matches[] = array('text'=>$text, 'matches'=>array($res[1]));
		return $type;	
	}
				
	static public function ParsePrice(&$rd, &$pd, &$matches=false)
	{			
		$r = self::ParsePrice_($rd['headline'], $matches) or $r = self::ParsePrice_($rd['description'], $matches) or $r = self::ParsePrice_2($rd['headline'], $matches);
		
		$price = preg_replace("@[^\d]+@s", "", $r);
		if(!$price) $price = 0;
		$pd['price'] = $price;
	}
	
	static private function ParsePrice_($text, &$matches=false)
	{
		$price0 = "@((?:£|".chr(163)."|&pound;|&#163;|GBP)(?:\s+|<.*?>)*\d[\s\d,]*[\d])".self::$span_right."@is";
		if(!preg_match($price0, $text, $res, PREG_OFFSET_CAPTURE)) return;
		$price = $res[1][0];
		$price = Html::PrepareField($price);
		
		if($matches !== false) $matches[] = array('text'=>$text, 'matches'=>array($res[1]));
		return $price;
	}
	
	static private function ParsePrice_2($text, &$matches=false)
	{
		$price0 = "@(?:\s+|<.*?>)(\d+[\s\d,](?:\d{3}[\s\d,])*000)".self::$span_right."@is";
		if(!preg_match($price0, $text, $res, PREG_OFFSET_CAPTURE)) return;
		$price = $res[1][0];
		$price = Html::PrepareField($price);
		
		if($matches !== false) $matches[] = array('text'=>$text, 'matches'=>array($res[1]));
		return $price;
	}
		
	static public function ParseStatus(&$rd, &$pd, &$matches=false)
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
		
		if(preg_match("@".self::$span_left."(receipt of an offer|submit any higher offers|public notice|(?<!roof top|satellite|speech|intercom) receivers|(?<!not all )mortgagees?|notice of offer)".self::$span_right."@is", $text, $res, PREG_OFFSET_CAPTURE))
		{
			if($matches !== false) $ms[] = $res[1];
			$statuses["repossession"] = 1;
		}
		elseif(self::pattern_not_within_pattern_match($text, "repossessed", "may be repossessed if you", $ms))
		{
			$statuses["repossession"] = 1;
		}
				
		if(preg_match("@".self::$span_left."(multi let|hmo|multiple occupation)".self::$span_right."@is", $text, $res, PREG_OFFSET_CAPTURE))
		{
			if($matches !== false) $ms[] = $res[1];
			$statuses["investment_hmo"] = 1;
		}
				
		if(preg_match("@".self::$span_left."(portfolio)".self::$span_right."@is", $text, $res, PREG_OFFSET_CAPTURE))
		{
			if($matches !== false) $ms[] = $res[1];
			$statuses["investment_portfolio"] = 1;
		}
				
		if(preg_match("@".self::$span_left."((?<!proof of )income|tenanted|(?<!intending purchaser or )tenant|fully let)".self::$span_right."@is", $text, $res, PREG_OFFSET_CAPTURE))
		{
			if($matches !== false) $ms[] = $res[1];
			$statuses["investment_prelet"] = 1;
		}
		elseif(self::pattern_not_within_pattern_match($text, "tenants", "prospective purchasers\s*\/\s*tenants|prospective buyers or tenants", $ms)
		or self::pattern_not_within_pattern_match($text, "tenant", "intending purchasers or tenants|purchasers\s*\/\s*tenants", $ms)
		)
		{//print("+++++++++");
			$statuses["investment_prelet"] = 1;
		}
				
		if(preg_match("@".self::$span_left."(p\.c\.m\.|pcm|registered rent|investors?|(?<!sales and )rental|long lease|yield|buy to let|btl)".self::$span_right."@is", $text, $res, PREG_OFFSET_CAPTURE))
		{
			if($matches !== false) $ms[] = $res[1];
			$statuses["investment_general"] = 1;
		}
		elseif(self::pattern_not_within_pattern_match($text, "investment", "please contact our investment team|\/investments", $ms))
		{
			$statuses["investment_general"] = 1;
		}
		
		if(preg_match("@".self::$span_left."(requiring refurbishment|requires refurbishment|some refurbishment|needs renovation|requires renovation|needs work|re\-furbishment|disrepair|unfinished|un\-finished|incomplete|diy project|diy enthusiasts?|keen diy|diy experts?|for diy|with diy|need of repair|water damage|neglected|remedial work|unmodernised|some repair|cosmetic(?! finish| mirror)|fixer upper|unconditional offers)".self::$span_right."@is", $text, $res, PREG_OFFSET_CAPTURE))
		{
			if($matches !== false) $ms[] = $res[1];
			$statuses["light_renovation"] = 1;
		}
		elseif(self::pattern_and_not_pattern_match($text, "modernisation|updating|improvement|modernising|total refurbishment|renovation|redecoration|tlc", "undergone|underwent|completed|finished|undertaken|subject of|carried", $ms, 5)
			or self::pattern_plus_pattern_match($text, "refurbishment|renovation", "need of", $ms, 20)
			or self::pattern_and_not_pattern_match($text, "upgrading", "undergone|underwent|completed|finished|undertaken", $ms, 5)
		)
		{
			$statuses["light_renovation"] = 1;
		}
		
		if(preg_match("@".self::$span_left."(subsidence|unmortgageable|structural repair|dilapidated|development potential|development opportunity|derelict|potential redevelopment|opportunity redevelopment|shell condition|cash offers|fire damaged|self build|structural repair|conversion opportunity|part completed|part\-completed|part developed|construction dwellings|change of use|erection|partially built|partially complete|partially developed|developer(?!s)|partially converted|uninhabitable|poor condition|building site|poor repair|development land|development possibilities|possible development|poor state|cash purchaser(?! then we will))".self::$span_right."@is", $text, $res, PREG_OFFSET_CAPTURE))
		{
			if($matches !== false) $ms[] = $res[1];
			$statuses["development"] = 1;
		}
		elseif(self::pattern_and_not_pattern_match($text, "own risk", "school catchment", $ms))
		{
			$statuses["development"] = 1;
		}
		
		if(preg_match("@".self::$span_left."(additional dwelling|building plot|development site|development plot)".self::$span_right."@is", $text, $res, PREG_OFFSET_CAPTURE))
		{
			if($matches !== false) $ms[] = $res[1];
			$statuses["plot"] = 1;
		}
		
		if(preg_match("@".self::$span_left."(cash buyers|cash purchase|cash offers|unmortgageable)".self::$span_right."@is", $text, $res, PREG_OFFSET_CAPTURE))
		{
			if($matches !== false) $ms[] = $res[1];
			$statuses["unmortgageable"] = 1;
		}
		elseif(self::pattern_not_within_pattern_match($text, "cash buyer", "sell to a cash buyer|your cash buyer with|cash buyer available", $ms))
		{
			$statuses["unmortgageable"] = 1;
		}
				
		if(preg_match("@".self::$span_left."(short lease)".self::$span_right."@is", $text, $res, PREG_OFFSET_CAPTURE))
		{
			$statuses["short_lease"] = 1;
			if($matches !== false) $ms[] = $res[1];
		}
		elseif(preg_match_all("@".self::$span_left.self::$word_left."{0,4}lease".self::$word_right."{0,4}".self::$span_right."@is", $text, $res, PREG_OFFSET_CAPTURE)) self::check_leasehold_years($res[0], $text, $leasehold_years, $statuses, $ms);	
		if(!isset($leasehold_years) and preg_match("@".self::$span_left."lease".self::$span_right."@is", $text) and preg_match_all("@".self::$span_left.self::$word_left."{0,4}(yrs?|years?|remaining)".self::$word_right."{0,4}".self::$span_right."@is", $text, $res, PREG_OFFSET_CAPTURE)) self::check_leasehold_years($res[0], $text, $leasehold_years, $statuses, $ms);
		
		if(preg_match("@".self::$span_left."(stpp|stp|s\.t\.p|s\.t\.p\.p\.|subject to planning|subject to necessary|subject to the necessary|subject to relevant|subject to the relevant|to pp|subject to consents?|planning enquiries|subject to planning)".self::$span_right."@is", $text, $res, PREG_OFFSET_CAPTURE))
		{
			$statuses["planning_permission_potential"] = 1;			
			if($matches !== false) $ms[] = $res[1];
		}
		elseif(self::pattern_and_not_pattern_match($text, "planning permission", "conservatory", $ms)
			or self::pattern_not_within_pattern_match($text, "planning permission", "any reference is made to planning permission|unable to confirm that the appropriate planning permission|cannot guarantee that building regulations or planning permission has been approved|not checked the legal title, nor any planning permission", $ms)
			)
		{
			$statuses["planning_permission_potential"] = 1;
		}			
		
		if(preg_match("@".self::$span_left."(planning consent|outline planning|detailed planning|pp for|p\.p\. for|P\/P|dpp|opp development|planning for|copies of (?:the )?plans|lapsed planning|expired planning|(?<!references to condition and necessary )permission for|planning passed)".self::$span_right."@is", $text, $res, PREG_OFFSET_CAPTURE))
		{
			$statuses["planning_permission_granted"] = 1;			
			if($matches !== false) $ms[] = $res[1];
		}
		
		if(preg_match("@".self::$span_left."(quick sale|fast sale|below market value|BMV)".self::$span_right."@is", $text, $res, PREG_OFFSET_CAPTURE))
		{
			if($matches !== false) $ms[] = $res[1];
			$statuses["motivated_seller"] = 1;
		}
		
		if($matches !== false) $matches[] = array('text'=>$text, 'matches'=>$ms);
		return $statuses; 	
	}
	
	static function check_leasehold_years($year_fragments, $text, &$leasehold_years, &$statuses, &$ms=false)
	{
		foreach($year_fragments as $m)
		{
			//print($m[0]."+++++<br>\n\n\n\n");
			if(preg_match("@".self::$span_left."NHBC".self::$span_right."@is", $m[0])) continue;
			/*preg_match_all("@(?>!(?:£|".chr(163)."|&pound;|&#163;|GBP)\s*|\.|\,)(\d{1,3})(?![\.|\,]?\d)@is", $m[0], $res);*/
			$t = preg_replace("@(£|".chr(163)."|&pound;|&#163;|GBP)\s*\d+?([\.|\,]\d+)*|\d+?([\.|\,]\d+)+@is", "", $m[0]);
			//$t = preg_replace("@\d+?([\.|\,]\d+)+@is", "", $t);
			preg_match_all("@".self::$span_left."(\d+)".self::$span_right."@is", $t, $res);
			foreach($res[1] as $leasehold_years)
			{
			print($leasehold_years."-----<br>");
				if($leasehold_years > 0 and $leasehold_years < 75 and ($leasehold_years >= 30 or !preg_match("@".self::$span_left."(tenant|income|commercial)".self::$span_right."@is", $text))) 
				{
					$statuses["short_lease"] = 1;
					if($ms !== false) $ms[] = $m;
					break;
				}
			}
		}
	}
			
	static public function ParseTenure(&$rd, &$pd, &$matches=false)
	{			
		$r = self::ParseTenure_($pd['headline'], $matches) or $r = self::ParseTenure_($pd['description'], $matches);
		$pd['tenure'] = $r;
	}
	
	static private function ParseTenure_($text, &$matches=false)
	{	
		$tenure = null;				
		if(preg_match("@".self::$span_left."(free\-?hold)".self::$span_right."@is", $text, $res, PREG_OFFSET_CAPTURE)) $tenure = "freehold";
		elseif(preg_match("@".self::$span_left."(lease\-?hold)".self::$span_right."@is", $text, $res, PREG_OFFSET_CAPTURE)) $tenure = "leasehold";
		elseif(preg_match("@".self::$span_left."(share of freehold|shared freehold)".self::$span_right."@is", $text, $res, PREG_OFFSET_CAPTURE)) $tenure = "shared_freehold";
		
		if($matches !== false) $matches[] = array('text'=>$text, 'matches'=>array($res[1]));
		return $tenure;
	}
				
	static public function ParseIsSold(&$rd, &$pd, &$matches=false)
	{			
		$r = self::ParseIsSold_($pd['headline'], $matches) or $r = self::ParseIsSold_($pd['description'], $matches);
		$pd['is_sold'] = $r;
	}
	
	static private function ParseIsSold_($text, &$matches=false)
	{			
		if($matches !== false) $ms = array(); else $ms = false;
		
		$is_sold = 0;	
		
		if(preg_match("@".self::$span_left."(sold stc|sale agreed|STC|sstc)".self::$span_right."@is", $text, $res, PREG_OFFSET_CAPTURE))
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
				
	static public function ParseFeatures(&$rd, &$pd, &$matches=false)
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
		
		if(preg_match("@".self::$span_left."(basement(?! flat)|cellar)".self::$span_right."@is", $text, $res, PREG_OFFSET_CAPTURE))
		{
			$features["basement"] = 1;
			if($matches !== false) $ms[] = $res[1];
		}		
		
		if(preg_match("@".self::$span_left."(no deposit|deposit paid|no money down)".self::$span_right."@is", $text, $res, PREG_OFFSET_CAPTURE)) 
		{
			$features["gifted_deposit"] = 1;
			if($matches !== false) $ms[] = $res[1];
		}		
		
		if(preg_match("@".self::$span_left."(no chain|chain free|no\-chain|chain\-free|no onward chain)".self::$span_right."@is", $text, $res, PREG_OFFSET_CAPTURE)) 
		{
			$features["no_chain"] = 1;
			if($matches !== false) $ms[] = $res[1];
		}		
		
		if(preg_match("@".self::$span_left."(no stamp duty|stamp duty paid|stamp duty exempt|stamp duty threshold)".self::$span_right."@is", $text, $res, PREG_OFFSET_CAPTURE)) 
		{
			$features["no_stamp_duty"] = 1; 
			if($matches !== false) $ms[] = $res[1];
		}		
		
		if(preg_match("@".self::$span_left."(attic|loft)".self::$span_right."@is", $text, $res, PREG_OFFSET_CAPTURE)) 
		{
			$features["attic"] = 1;
			if($matches !== false) $ms[] = $res[1];
		}
		
		if(preg_match("@".self::$span_left."(vacant possession(?!".self::$word_left."{0,3} (?:upon|on) completion))".self::$span_right."@is", $text, $res, PREG_OFFSET_CAPTURE))
		{
			$features["vacant_possession"] = 1;
			if($matches !== false) $ms[] = $res[1];
		}		
		
		if(preg_match("@".self::$span_left."((?<!garden and )outbuildings)".self::$span_right."@is", $text, $res, PREG_OFFSET_CAPTURE)) 
		{
			$features["outbuildings"] = 1;
			if($matches !== false) $ms[] = $res[1];
		}
		
		if(self::pattern_and_not_pattern_match($text, "end of terrace", "flat", $ms)) 
		{	
			$features["end_of_terrace"] = 1;
		}		
				
		if(preg_match("@".self::$span_left."(under offer)".self::$span_right."@is", $text, $res, PREG_OFFSET_CAPTURE))
		{
			$features["under_offer"] = 1;
			if($matches !== false) $ms[] = $res[1];
		}		
		
		if(preg_match("@".self::$span_left."(garage)".self::$span_right."@is", $text, $res, PREG_OFFSET_CAPTURE))
		{
			$features["garage"] = 1;
			if($matches !== false) $ms[] = $res[1];
		}		
		
		if(preg_match("@".self::$span_left."(corner plot|corner position)".self::$span_right."@is", $text, $res, PREG_OFFSET_CAPTURE))
		{
			$features["corner_plot"] = 1;
			if($matches !== false) $ms[] = $res[1];
		}
		
		if($matches !== false) $matches[] = array('text'=>$text, 'matches'=>$ms);
		return $features;
	}
			
	static private function pattern_plus_pattern_match($text, $pattern1, $pattern2, &$ms=false, $radius_in_words=-1)
	{		
		if($radius_in_words < 0) $regex = "@(.*".self::$span_left.")($pattern1)(".self::$span_right.".*)@is";
		else $regex = "@(".self::$span_left.self::$word_left."{0,$radius_in_words})($pattern1)(".self::$word_right."{0,$radius_in_words}".self::$span_right.")@is";		
		
		for($offset = 0; preg_match($regex, $text, $res, PREG_OFFSET_CAPTURE, $offset); $offset = $res[2][1] + 1)
		{
			if($ms !== false) $ms[] = $res[2];
			$text2 = $res[1][0];
			$offset2 = $offset + $res[1][1];
			if(preg_match("@".self::$span_left."($pattern2)".self::$span_right."@is", $text2, $res2, PREG_OFFSET_CAPTURE))
			{
				if($ms !== false) $ms[] = $offset2 + $res2[1][1];	
				return true;
			}
			$text2 = $res[3][0];
			$offset2 = $offset + $res[3][1];
			if(preg_match("@".self::$span_left."($pattern2)".self::$span_right."@is", $text2, $res2, PREG_OFFSET_CAPTURE))
			{
				if($ms !== false) $ms[] = $offset2 + $res2[1][1];	
				return true;
			}
		}
		return false;		
	}
	
	static private $word_left = "(?:[^\s]+[^\w]+)";
	static private $word_right = "(?:[^\w]+[^\s]+)";
	
	static private function pattern_and_not_pattern_match($text, $pattern1, $pattern2, &$ms=false, $radius_in_words=-1)
	{		
		if($radius_in_words < 0) $regex = "@.*".self::$span_left."($pattern1)".self::$span_right.".*@is";
		else $regex = "@".self::$span_left.self::$word_left."{0,$radius_in_words}($pattern1)".self::$word_right."{0,$radius_in_words}".self::$span_right."@is";
		
		for($offset = 0; preg_match($regex, $text, $res, PREG_OFFSET_CAPTURE, $offset); $offset = $res[1][1] + 1)
		{
			if($ms !== false) $ms[] = $res[1];
			$text2 = $res[0][0];
			$offset2 = $offset + $res[0][1];
			if(!preg_match("@".self::$span_left."($pattern2)".self::$span_right."@is", $text2, $res2, PREG_OFFSET_CAPTURE)) return true;
			if($ms !== false) $ms[] = $offset2 + $res2[1][1];
		}
		
		return false;
	}
	
	private static function pattern_not_within_pattern_match($text, $pattern1, $pattern2, &$ms=false)
	{	
		preg_match_all("@".self::$span_left."($pattern1)".self::$span_right."@is", $text, $res1, PREG_OFFSET_CAPTURE);
		preg_match_all("@".self::$span_left."($pattern2)".self::$span_right."@is", $text, $res2, PREG_OFFSET_CAPTURE);
		
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

A spacious and well presented one bedroom first floor apartment located within the popular area of West Knighton. The property is offered for sale with No Upward Chain and comprises: an open plan lounge/kitchen, bedroom, bathroom and parki A spacious and well presented one bedroom first floor apartment located within the popular area of West Knighton. The property is offered for sale with No Upward Chain and comprises: an open plan lounge/kitchen, bedroom, bathroom and parking space. The property is ideally situated for day to day amenities in Wigston Town Centre or along Queens Road shopping parade in neighbouring Clarendon Park where there is a direct bus route, together with regular bus routes running to and from Leicester City Centre. Viewing is highly recommended to appreciate accommodation. The Ground Rent is 9.00 per year, Service Charge is 38.54 per month with 94 years remaining on the lease.Gas central heating, double glazing, entrance hall, open plan lounge/kitchen, bedroom, bathroom, parking space.Communal Entrance With stairs rising to first floor and a security door entry system. Entrance Hall With cupboard with loft access, additional storage cupboard, radiator.Open Plan Lounge 15'6'' x 10'5'' / Kitchen 9'5'' x 6'01'' With double glazed square bay window to the front elevation, kitchen area comprises: stainless steel sink and drainer, a range of wall and base units with work surfaces over, plumbing for washing machine, central heating boiler, radiator.Bedroom 12'5'' x 8'7'' With double glazed window to the front elevation, radiator.Bathroom 9'8'' x 5'7'' With panelled bath, pedestal wash-hand basin, low-level WC, extractor, radiator.OutsideThe property benefits from use the car park and a brick bin store as you enter the property. VALUATIONS: If you are considering selling we would be happy to advise you on the value of your own property together with marketing advice with no obligation. Kitchen type: N/A Bathrooms: 0 Bedrooms: 1 Reception Rooms: 0 Parking: N/A Outside space: N/A Tenure: N/A Heating: N/A

STR;

$rd = array('headline'=>$h, 'description'=>$d);
$pd = array();
if(!isset($pd['headline'])) $pd['headline'] = Html::PrepareField($rd['headline']);
if(!isset($pd['description'])) $pd['description'] = Html::PrepareField($rd['description']);
$ms = array();
ValueParser::ParseStatus($rd, $pd, $ms);

print $pd['status']."<br>";
print_r($ms);
*/

?>