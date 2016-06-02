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
include_once("$ABSPATH/common/db.php");
include_once("$ABSPATH/base/crawler6/table_routines.php");

class CommonPhraseDetector
{
	static public function Init()
	{				
		self::$patterns = array();
		foreach(Db::GetArray("SELECT pattern, field FROM parser_patterns") as $r) self::$patterns[Html::PrepareField($r['pattern'])] = $r['field'];
	}
	private static $patterns;
		
	const WORD_RADIUS = 3;
	
	public static function Detect(&$p, $crawler_id)
	{	
		if(!isset(self::$patterns)) self::Init();
		
		//if(!isset($p['headline_'])) $p['headline_'] = Html::PrepareField($p['headline']);
		if(!isset($p['description_'])) $p['description_'] = Html::PrepareField($p['description']);
				
		$global_product_id = TableRoutines::GetGlobalProductId($crawler_id, $p['id']);
		foreach(self::$patterns as $pattern=>$field)
		{			
			for($offset = 0; preg_match("@((?:[a-z\,]+\s+){0,".self::WORD_RADIUS."})($pattern)((?:\s+[a-z\,]+){0,".self::WORD_RADIUS."})@is", $p['description_'], $res, PREG_OFFSET_CAPTURE, $offset); $offset = $res[2][1] + 1)
			{
				$found_match_left = strtolower(trim($res[1][0]));
				$pattern = strtolower(trim($res[2][0]));
				$found_match_right = strtolower(trim($res[3][0]));
				//Logger::Write2("Found: $pattern '$found_match_left $pattern $found_match_right'");
				if($found_match_left) $found_match_left_words = preg_split("@\s+@", $found_match_left);
				else $found_match_left_words = array();
				//Logger::Write2($found_match_left_words);
				if($found_match_right) $found_match_right_words = preg_split("@\s+@", $found_match_right);
				else $found_match_right_words = array();
				//Logger::Write2($found_match_right_words);
				$found_match_left_words_count = count($found_match_left_words);
				$found_match_right_words_count = count($found_match_right_words);
				$frames_count = $found_match_left_words_count + $found_match_right_words_count - self::WORD_RADIUS + 1;
				//Logger::Write2("frames_count:$frames_count");
			
				for($i = 0; $i < $frames_count; $i++)
				{
					$phrase = "";
					for($j = $i; $j < $found_match_left_words_count; $j++) $phrase .= $found_match_left_words[$j]." ";
					$phrase .= $pattern;
					$right_last = self::WORD_RADIUS - $found_match_left_words_count + $i;
					for($j = 0; $j < $right_last; $j++) $phrase .= " ".$found_match_right_words[$j];
										
					$this->count_match($phrase, $global_product_id, $pattern, $field);
				}
			}
		}
	}	
		
	private function count_match(&$phrase, &$global_product_id, &$pattern, &$field)
	{			
		//Logger::Write2("Counting: $phrase");
		$product_ids = Db::GetSingleValue("SELECT product_ids FROM parser_found_matches WHERE phrase='$phrase' AND field='$field'");
		if(!$product_ids)
		{			
			Db::Query("INSERT parser_found_matches SET product_ids='$global_product_id', phrase='$phrase', field='$field', last_found_date=NOW()");
			return;
		}
		
		if(strstr($product_ids, $global_product_id) !== false)
		{
			Logger::Warning_("product_id '$global_product_id' was already counted for phrase='$phrase'!");
			return;
		}
		$product_ids = "$product_ids\n$global_product_id";
		Db::Query("UPDATE parser_found_matches SET product_ids='$product_ids', last_found_date=NOW() WHERE phrase='$phrase' AND field='$field'");
		$pis = preg_split("@\s+@is", $product_ids);
		if(count($pis) < 5) return;
		$negative_patterns = Db::GetSingleValue("SELECT negative_patterns FROM parser_patterns WHERE pattern='$pattern'");
		Logger::Write("Adding '$phrase' to negative_patterns.");
		if(strstr($negative_patterns, $phrase) !== false)
		{
			//Logger::Write2("negative_pattern '$phrase' was already added.");
			return;
		}
		if($negative_patterns) $negative_patterns2 = "$negative_patterns|$phrase";
		else $negative_patterns2 = $phrase;
		Db::Query("UPDATE parser_patterns SET negative_patterns='$negative_patterns2', _state='new' WHERE pattern='$pattern'");
		/*foreach($pis as $gpi)
		{
			TableRoutines::GetPartsOfGlobalProductId($gpi, $crawler_id, $product_id);
			$products_table = TableRoutines::GetProductsTableForCrawler($crawler_id);
			Db::Query("UPDATE $products_table SET _state='new' WHERE $id='$product_id'");
		}*/
	}
		
	public static function CleanUpOldNegativePatterns($older_than_days=100)
	{	
		$old_pattern_time = time() - 60 * 60 * 24 * $older_than_days;
		foreach(Db::GetArray("SELECT pattern, field, negative_patterns FROM parser_patterns") as $rs)
		{
			$negative_patterns = $rs['negative_patterns'];
			$negative_patterns2 = $negative_patterns;
			foreach(preg_split("@\|@is", $negative_patterns) as $np)
			{
				$last_found_date = Db::GetSingleValue("SELECT UNIX_TIMESTAMP(last_found_date) FROM parser_found_matches WHERE phrase='$np' AND field='".$rs['field']."'");
				if($last_found_date and $old_pattern_time < $last_found_date) continue; 
				
				$negative_patterns2 = preg_replace("@\|*$pattern\|*@is", "|", $negative_patterns2);
				if($negative_patterns2 == "|") $negative_patterns2 = "";
				Db::Query("UPDATE parser_patterns SET negative_patterns='$negative_patterns2', _state='new' WHERE pattern='$pattern'");
				Db::Query("DELETE FROM parser_found_matches WHERE phrase='$np' AND field='".$rs['field']."'");
			}
		}
	}
}
/*
CREATE TABLE IF NOT EXISTS `parser_patterns` (
  `pattern` varchar(64) NOT NULL,
  `field` varchar(32) NOT NULL,
  `value` varchar(32) NOT NULL,
  `negative_patterns` text NOT NULL,
  PRIMARY KEY (`pattern`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
  
CREATE TABLE IF NOT EXISTS `parser_found_matches` (
  `phrase` varchar(512) NOT NULL,
  `field` varchar(32) NOT NULL,
  `last_found_date` datetime NOT NULL,
  `product_ids` text NOT NULL,
  PRIMARY KEY (`phrase`, `field`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
  	
INSERT INTO parser_patterns (pattern, field, value) VALUES
('receipt of an offer', 'status', 'repossession'), 
('submit any higher offers', 'status', 'repossession'), 
('public notice', 'status', 'repossession'), 
('receivers', 'status', 'repossession'), 
('mortgagee', 'status', 'repossession'), 
('notice of offer', 'status', 'repossession'), 
('repossessed', 'status', 'repossession'), 
('buy to let', 'status', 'investment'), 
('btl', 'status', 'investment'), 
('investor', 'status', 'investment'), 
('p.c.m.', 'status', 'investment'), 
('pcm', 'status', 'investment'),  
('tenanted', 'status', 'investment'),  
('portfolio', 'status', 'investment'),  
('registered rent', 'status', 'investment'),  
('fully let', 'status', 'investment'),  
('long lease', 'status', 'investment'),  
('yield', 'status', 'investment'),  
('multi let', 'status', 'investment'),  
('hmo', 'status', 'investment'),  
('multiple occupation', 'status', 'investment'),  
('income', 'status', 'investment'),  
('rental', 'status', 'investment'),  
('investment', 'status', 'investment'),   
('tenant', 'status', 'investment'),   
('tenants', 'status', 'investment'),   
('requiring refurbishment', 'status', 'light_renovation'),    
('requires refurbishment', 'status', 'light_renovation'),    
('some refurbishment', 'status', 'light_renovation'),    
('needs renovation', 'status', 'light_renovation'),    
('requires renovation', 'status', 'light_renovation'),    
('needs work', 'status', 'light_renovation'),    
('re\-furbishment', 'status', 'light_renovation'),    
('disrepair', 'status', 'light_renovation'),    
('unfinished', 'status', 'light_renovation'),    
('un\-finished', 'status', 'light_renovation'),    
('unconditional offers', 'status', 'light_renovation'),    
('incomplete', 'status', 'light_renovation'),    
('diy project', 'status', 'light_renovation'),        
('diy enthusiasts?', 'status', 'light_renovation'),        
('keen diy', 'status', 'light_renovation'),        
('diy experts?', 'status', 'light_renovation'),        
('for diy', 'status', 'light_renovation'),        
('with diy', 'status', 'light_renovation'),        
('need of repair', 'status', 'light_renovation'),        
('water damage', 'status', 'light_renovation'),    
('fixer upper', 'status', 'light_renovation'),     
('neglected', 'status', 'light_renovation'),       
('remedial work', 'status', 'light_renovation'),       
('unmodernised', 'status', 'light_renovation'),       
('some repair', 'status', 'light_renovation'),       
('cosmetic', 'status', 'light_renovation'),       
('modernisation', 'status', 'light_renovation'),       
('updating', 'status', 'light_renovation'),     
('improvement', 'status', 'light_renovation'),   
('modernising', 'status', 'light_renovation'),   
('total refurbishment', 'status', 'light_renovation'),   
('renovation', 'status', 'light_renovation'),   
('redecoration', 'status', 'light_renovation'),   
('tlc', 'status', 'light_renovation')

INSERT INTO parser_patterns (pattern, field, value) VALUES
('subsidence', 'status', 'major_renovation'), 
('additional dwelling', 'status', 'major_renovation'), 
('building plot', 'status', 'major_renovation'), 
('dilapidated', 'status', 'major_renovation'), 
('unmortgageable', 'status', 'major_renovation'), 
('development potential', 'status', 'major_renovation'), 
('development opportunity', 'status', 'major_renovation'), 
('derelict', 'status', 'major_renovation'), 
('potential redevelopment', 'status', 'major_renovation'), 
('opportunity redevelopment', 'status', 'major_renovation'), 
('development site', 'status', 'major_renovation'), 
('shell condition', 'status', 'major_renovation'),
('cash offers', 'status', 'major_renovation'), 
('cash purchase', 'status', 'major_renovation'), 
('cash buyers', 'status', 'major_renovation'), 
('fire damaged', 'status', 'major_renovation'), 
('self build', 'status', 'major_renovation'), 
('structural repair', 'status', 'major_renovation'), 
('development plot', 'status', 'major_renovation'), 
('conversion opportunity', 'status', 'major_renovation'), 
('part developed', 'status', 'major_renovation'), 
('part[\-\s]completed', 'status', 'major_renovation'), 
('construction dwellings', 'status', 'major_renovation'), 
('change of use', 'status', 'major_renovation'), 
('erection', 'status', 'major_renovation'), 
('partially converted', 'status', 'major_renovation'),
('partially built', 'status', 'major_renovation'), 
('partially complete', 'status', 'major_renovation'), 
('partially developed', 'status', 'major_renovation'), 
('scope for extension', 'status', 'major_renovation'), 
('potential for extension', 'status', 'major_renovation'), 
('potential to extend', 'status', 'major_renovation'), 
('room to extend', 'status', 'major_renovation'), 
('uninhabitable', 'status', 'major_renovation'), 
('poor condition', 'status', 'major_renovation'), 
('building site', 'status', 'major_renovation'), 
('poor repair', 'status', 'major_renovation'), 
('development land', 'status', 'major_renovation'), 
('development possibilities', 'status', 'major_renovation'), 
('possible development', 'status', 'major_renovation'), 
('poor state', 'status', 'major_renovation'), 
('cash purchaser', 'status', 'major_renovation'), 
('cash buyer', 'status', 'major_renovation'), 
('upgrading', 'status', 'major_renovation'), 
('own risk', 'status', 'major_renovation'), 
('quick sale', 'status', 'motivated_seller'), 
('fast sale', 'status', 'motivated_seller'), 
('below market value', 'status', 'motivated_seller'), 
('BMV', 'status', 'motivated_seller'), 
('free\-?hold', 'tenure', 'freehold'), 
('lease\-?hold', 'tenure', 'leasehold'), 
('share of freehold', 'tenure', 'shared_freehold'), 
('shared freehold', 'tenure', 'shared_freehold'), 
('basement', 'features', 'basement'), 
('cellar', 'features', 'basement'), 
('no deposit', 'features', 'gifted_deposit'), 
('deposit paid', 'features', 'gifted_deposit'), 
('no money down', 'features', 'gifted_deposit'), 
('no chain', 'features', 'no_chain'), 
('chain free', 'features', 'no_chain'), 
('no\-chain', 'features', 'no_chain'), 
('chain\-free', 'features', 'no_chain'), 
('no onward chain', 'features', 'no_chain'), 
('no stamp duty', 'features', 'no_stamp_duty'), 
('stamp duty paid', 'features', 'no_stamp_duty'), 
('stamp duty exempt', 'features', 'no_stamp_duty'), 
('stamp duty threshold', 'features', 'no_stamp_duty'), 
('attic', 'features', 'attic'), 
('loft', 'features', 'attic'), 
('vacant possession', 'features', 'vacant_possession'), 
('outbuildings', 'features', 'outbuildings'), 
('end of terrace', 'features', 'end_of_terrace'), 
('under offer', 'features', 'under_offer'), 
('garage', 'features', 'garage'), 
('corner plot', 'features', 'corner_plot'),  
('corner position', 'features', 'corner_plot'), 
('stpp', 'features', 'planning_permission'), 
('stp', 'features', 'planning_permission'), 
('s\.t\.p', 'features', 'planning_permission'), 
('s\.t\.p\.p\.', 'features', 'planning_permission'), 
('subject to necessary', 'features', 'planning_permission'), 
('subject to the necessary', 'features', 'planning_permission'), 
('subject to relevant', 'features', 'planning_permission'), 
('subject to the relevant', 'features', 'planning_permission'), 
('planning consents?', 'features', 'planning_permission'), 
('outline planning', 'features', 'planning_permission'), 
('detailed planning', 'features', 'planning_permission'), 
('to pp', 'features', 'planning_permission'), 
('pp for', 'features', 'planning_permission'), 
('p\.p\. for', 'features', 'planning_permission'), 
('P\/P', 'features', 'planning_permission'), 
('dpp', 'features', 'planning_permission'), 
('opp development', 'features', 'planning_permission'), 
('planning for', 'features', 'planning_permission'), 
('copies of (?:the )?plans', 'features', 'planning_permission'), 
('subject to consents?', 'features', 'planning_permission'), 
('planning enquiries', 'features', 'planning_permission'), 
('subject to planning', 'features', 'planning_permission'), 
('lapsed planning', 'features', 'planning_permission'), 
('expired planning', 'features', 'planning_permission'), 
('planning passed', 'features', 'planning_permission'), 
('permission for', 'features', 'planning_permission'), 
('planning permission', 'features', 'planning_permission'), 
('short lease', 'features', 'short_lease'), 
('sold stc', 'is_sold', ''), 
('sale agreed', 'is_sold', ''), 
('STC', 'is_sold', ''), 
('sstc', 'is_sold', ''), 
('sold', 'is_sold', '')
*/

?>