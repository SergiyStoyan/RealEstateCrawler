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

$ABSPATH = dirname(__FILE__)."/..";

include_once("$ABSPATH/common/logger.php");
include_once("$ABSPATH/constants.php");
include_once("$ABSPATH/common/db.php");
include_once("$ABSPATH/common/html_utilities.php");
include_once("$ABSPATH/common/misc.php");

set_time_limit(-1);

Db::Query("SET SESSION query_cache_type = ON");

//SELECT * FROM `addresses` WHERE `double_dependent_locality` LIKE '%Nercwys%' OR county LIKE '%Nercwys%' OR traditional_county LIKE '%Nercwys%' OR town LIKE '%Nercwys%' OR village LIKE '%Nercwys%' OR street LIKE '%Nercwys%' OR thoroughfare LIKE '%Nercwys%' 

class AddressParser
{
	static public $WriteDetails = false;
	
	private static $SEARCH_ORDER = array(
			"find"=>"postcodes", 
			"yes"=>"#SEARCH_ORDER_FIND_COUNTIES", 
			"no"=>"#SEARCH_ORDER_FIND_COUNTIES"
		);	
	
	private static $SEARCH_ORDER_FIND_COUNTIES = array(
		"find"=>"counties", 
		"yes"=>"#SEARCH_ORDER_FIND_TOWNS_FOR_COUNTY",
		"no"=>array(
			"find"=>"traditional_counties",
			"yes"=>"#SEARCH_ORDER_FIND_TOWNS_FOR_COUNTY",
			"no"=>"#SEARCH_ORDER_FIND_TOWNS_FOR_NO_COUNTY"
		)
	);
	
	private static $SEARCH_ORDER_FIND_TOWNS_FOR_COUNTY = array(
		"find"=>"towns", 
		"yes"=>"#SEARCH_ORDER_FIND_STREETS_FOR_TOWNS",
		"no"=>array(
			"find"=>"villages",
			"yes"=>array(
				"find"=>"streets", "look_for_exact_entity_match"=>false,					
				"yes"=>false,					
				"no"=>array(
					"find"=>"thoroughfares", "look_for_exact_entity_match"=>true, 
					"yes"=>false,
					"no"=>false
				)
			),
			"no"=>array(
				"find"=>"thoroughfares", "look_for_exact_entity_match"=>true,
				"yes"=>array(
					"find"=>"streets", "look_for_exact_entity_match"=>true,				
					"yes"=>false,
					"no"=>false
				),
				"no"=>array(
					"find"=>"streets", "look_for_exact_entity_match"=>true,	"look_within_max_char_span"=>50,		
					"yes"=>false,
					"no"=>array(
						"cutoff"=>"county",
						"#merge"=>"#SEARCH_ORDER_FIND_TOWNS_FOR_NO_COUNTY"
					)
				)
			)
		)
	);	
	
	private static $SEARCH_ORDER_FIND_TOWNS_FOR_NO_COUNTY = array(
		"find"=>"towns",
		"yes"=>"#SEARCH_ORDER_FIND_STREETS_FOR_TOWNS",
		"no"=>array(
			"find"=>"villages",
			"yes"=>array(
				"find"=>"streets", "look_for_exact_entity_match"=>false,			
				"yes"=>false,		
				"no"=>array(
					"find"=>"thoroughfares", "look_for_exact_entity_match"=>true,
					"yes"=>false,
					"no"=>false
				)
			),
			"no"=>array(
				"find"=>"thoroughfares", "look_for_exact_entity_match"=>true,
				"yes"=>array(
					"find"=>"streets", "look_for_exact_entity_match"=>true,				
					"yes"=>false,
					"no"=>false
				),
				"no"=>array(//false  !!!
					"find"=>"streets", "look_for_exact_entity_match"=>true,	"look_within_max_char_span"=>50,				
					"yes"=>false,
					"no"=>false
				)
			)
		)
	);	
		
	private static $SEARCH_ORDER_FIND_STREETS_FOR_TOWNS = array(
		"find"=>"streets", "look_for_exact_entity_match"=>false,				
		"yes"=>array(
			"find"=>"villages",
			"yes"=>false,
			"no"=>array(
				"find"=>"thoroughfares", "look_for_exact_entity_match"=>true,
				"yes"=>false,
				"no"=>false
			)
		),					
		"no"=>array(
			"cutoff"=>"town",
			"find"=>"villages",
			"yes"=>array(
				"find"=>"streets", "look_for_exact_entity_match"=>false,		
				"yes"=>array(
					"find"=>"towns",
					"yes"=>false,
					"no"=>false
				),					
				"no"=>array(
					"find"=>"thoroughfares", "look_for_exact_entity_match"=>true,
					"yes"=>false,
					"no"=>array(
						"find"=>"towns",
						"yes"=>false,
						"no"=>false
					)
				)
			),
			"no"=>array(
				"find"=>"thoroughfares", "look_for_exact_entity_match"=>true,
				"yes"=>array(
					"find"=>"streets", "look_for_exact_entity_match"=>true,			
					"yes"=>array(
						"find"=>"towns",
						"yes"=>false,
						"no"=>false
					),
					"no"=>false
				),
				"no"=>array(//false  !!!
					"find"=>"streets", "look_for_exact_entity_match"=>true,	"look_within_max_char_span"=>50,		
					"yes"=>false,
					"no"=>false
				)
			)
		)
	);		
	
	public static function Init()
	{
		static $initiated = false;		
		if($initiated) return;//Logger::Quit("Init was called twice.");
		$initiated = true;
		
		self::compile_search_step(self::$SEARCH_ORDER);
	}
	
	static private function compile_search_step(&$search_step)
	{
		if(!$search_step) return;
		if(isset($search_step['yes'])) 
		{
			if(is_string($search_step['yes'])) $search_step['yes'] = self::get_search_order_by_name($search_step['yes']);
			self::compile_search_step($search_step['yes']);
		}
		if(isset($search_step['no'])) 
		{
			if(is_string($search_step['no'])) $search_step['no'] = self::get_search_order_by_name($search_step['no']);
			self::compile_search_step($search_step['no']);
		}
		if(isset($search_step['#merge'])) 
		{
			$_search_order = self::get_search_order_by_name($search_step['#merge']);
			if(isset($_search_order['find'])) $search_step['find'] = $_search_order['find'];
			if(isset($_search_order['look_for_exact_entity_match'])) $search_step['look_for_exact_entity_match'] = $_search_order['look_for_exact_entity_match'];
			if(isset($_search_order['look_within_max_char_span'])) $search_step['look_within_max_char_span'] = $_search_order['look_within_max_char_span'];
			if(isset($_search_order['yes'])) $search_step['yes'] = $_search_order['yes'];
			if(isset($_search_order['no'])) $search_step['no'] = $_search_order['no'];
			unset($search_step['#merge']);
			self::compile_search_step($search_step);
		}		
	}	
	static private function get_search_order_by_name($search_order_name)
	{
		$search_order = substr($search_order_name, 1);
		$vars = get_class_vars("AddressParser");
		$search_order = $vars[$search_order];
		//$search_order = self::$$search_order;
		if(!is_array($search_order)) Logger::Quit("search_order $search_order is wrong.");
		return $search_order;		
	}
	
	final function __construct(&$p)
	{
		self::Init();	
	
		self::$p = &$p;
		if(!isset(self::$p['address_'])) self::$p['address_'] = Html::PrepareField(self::$p['address']);
		if(!isset(self::$p['headline_'])) self::$p['headline_'] = Html::PrepareField(self::$p['headline']);
		if(!isset(self::$p['description_'])) self::$p['description_'] = Html::PrepareField(self::$p['description']);
	}
	private static $p;

	public static function ProductId()
	{
		return self::$p['id'];
	}
	
	public function ParseAddress()
	{
		if(self::$WriteDetails) $start_time = microtime(false);	
		
		self::$p['postcode'] = "";
		self::$p['county'] = "";
		self::$p['town'] = "";
		self::$p['street'] = "";
		self::$p['village'] = "";
		self::$p['thoroughfare'] = "";	
		
		$address = self::find_complete_address();		
		
		if(self::$WriteDetails)
		{	
			$start_times = explode(" ", $start_time);
			$end_times = explode(" ", microtime(false));
			$time_diff = $end_times[1] - $start_times[1] + $end_times[0] - $start_times[0];
			Logger::Write2("Time spent by AddressParser: $time_diff");
			if($address) Logger::Write2("The chosen address:".$address->AsString());
		}
		
		if(!$address or $address->Rank < 5)
		{
			if(self::$WriteDetails) Logger::Write2("No address was found or it was dropped due to low rank.");
			return false;
		}
		
		self::$p['postcode'] = $address->postcode;
		self::$p['county'] = $address->county;
		self::$p['town'] = $address->town;
		self::$p['street'] = $address->street;
		self::$p['village'] = $address->village;
		self::$p['thoroughfare'] = $address->thoroughfare;
		
		return true;
	}
	
	private static function find_complete_address()
	{
		self::$look_for_exact_entity_match2 = false;
		$best_address = null;
		
		if(self::$p['address'])
		{
			if(self::$WriteDetails) Logger::Write2("Parsing address: ".self::$p['address']);
			$address = self::find_the_best_rank_address(self::$p['address_']);
			if($address and $address->IsProven()) return $address;
			$best_address = $address;
		}
		
		if(self::$WriteDetails) Logger::Write2("Parsing headline: ".self::$p['headline_']);
		$address = self::find_the_best_rank_address(self::$p['headline_']);			
		if($address and $address->IsProven()) return $address;
		if(!$best_address or $address and $best_address->Rank < $address->Rank) $best_address = $address;
		
		self::$look_for_exact_entity_match2 = true;
		
		if(self::$WriteDetails) Logger::Write2("Parsing description: ".self::$p['description_']);
		$address = self::find_the_best_rank_address(self::$p['description_']);			
		if($address and $address->IsProven()) return $address;	
		if(!$best_address or $address and $best_address->Rank < $address->Rank) $best_address = $address;
		
		if($best_address and !$best_address->postcode and ($best_address->town or $best_address->village) and !$best_address->street)
		{
			//Logger::Warning("Not implemented! ID=".self::$p['id']);
			$parent_address_entity = $best_address->EndAddressEntity;
			$parent_address_entity->SwitchToAnotherText2SearchForName("street");
			
			Logger::Write2("Parsing headline for street in ID=".self::$p['id']);
			$child_address_entities = self::find_streets(self::$p['headline_'], $parent_address_entity, true);	
			if(empty($child_address_entities))
			{
				Logger::Write2("Parsing description for street in ID=".self::$p['id']);
				$child_address_entities = self::find_streets(self::$p['description_'], $parent_address_entity, true);				
			}
				
			if(!empty($child_address_entities))
			{
				$addresses = array();
				foreach($child_address_entities as $cae)
				{
					Logger::Write2("Found street:".$cae->FoundWord);
					$addresses[] = new Address($cae);
				}
				$best_address = self::choose_the_best_address($addresses);
			}
		}
		return $best_address;		
	}
	static private $look_for_exact_entity_match2 = false;
		
	static private function find_the_best_rank_address($text)
	{				
		if($proven_address = self::perform_step($addresses, self::$SEARCH_ORDER, null, $text))
		{
			if(self::$WriteDetails) Logger::Write2("Search was stopped by a proven address");
			return $proven_address;
		}
		return self::choose_the_best_address($addresses);
	}
	
	static private function choose_the_best_address(array $addresses)
	{						
		$best_address = false;
		foreach($addresses as $address)	
		{
			if(!$best_address or $best_address->Rank < $address->Rank) $best_address = $address;
			elseif($best_address->Rank == $address->Rank)
			{
				$score = 0;
				foreach(AddressEntity::$Names as $name) $score += self::compare_certainty_of_entity_values($name, $address, $best_address);
				if($score > 0) $best_address = $address;
			}
		}
		return $best_address;
	}
		
	static private function compare_certainty_of_entity_values($name, $address, $address2)
	{
		$ae = $address->EndAddressEntity->FindEntityInChain($name, false);
		$ae2 = $address2->EndAddressEntity->FindEntityInChain($name, false);
		if(!$ae)
		{
			if(!$ae2) return 0;
			return -1;
		}
		if(!$ae2) return 1;
		$found_word = $ae->FoundWord;
		$found_word2 = $ae2->FoundWord;
		switch($address->Name2States[$name])
		{
			case 'exact':			
				switch($address2->Name2States[$name])
				{
					case 'exact':
						preg_match_all("@\s+@s", $found_word, $res);
						preg_match_all("@\s+@s", $found_word2, $res2);
						if(count($res2[0]) < count($res[0])) return 1;
						if(count($res2[0]) > count($res[0])) return -1;
						preg_match_all("@(^|\s+)[A-Z]@s", $found_word, $res);
						preg_match_all("@(^|\s+)[A-Z]@s", $found_word2, $res2);
						if(count($res2[0]) < count($res[0])) return 1;
						if(count($res2[0]) > count($res[0])) return -1;
						if(strlen($found_word2) < strlen($found_word)) return 1;
						if(strlen($found_word2) > strlen($found_word)) return -1;
						return 0;
					case 'not_exact':
					case 'replenished':
					case 'empty':
						return 1;
					default: Logger::Quit("No such state.");
				}
			case 'not_exact':			
				switch($address2->Name2States[$name])
				{
					case 'exact': 
						return -1;
					case 'not_exact':
						preg_match_all("@\s+@s", $found_word, $res);
						preg_match_all("@\s+@s", $found_word2, $res2);
						if(count($res2[0]) < count($res[0])) return 1;
						if(count($res2[0]) > count($res[0])) return -1;
						preg_match_all("@(^|\s+)[A-Z]@s", $found_word, $res);
						preg_match_all("@(^|\s+)[A-Z]@s", $found_word2, $res2);
						if(count($res2[0]) < count($res[0])) return 1;
						if(count($res2[0]) > count($res[0])) return -1;
						if(strlen($found_word2) < strlen($found_word)) return 1;
						if(strlen($found_word2) > strlen($found_word)) return -1;
						return 0;
					case 'replenished':
					case 'empty':
						return 1;
					default: Logger::Quit("No such state.");
				}
			case 'replenished':
			case 'empty':
				return 0;
			default: Logger::Quit("No such state.");
		}
	}
	
	static private function perform_step(&$addresses, $step, $parent_address_entity, $text)
	{
		static $zero_address_entity;
		if(!$parent_address_entity)
		{//initiate search 
			$zero_address_entity = new AddressEntity(null, null, null, null, null, null);
			$parent_address_entity = $zero_address_entity;
			$addresses = array();
		}
		
		if(!$step)
		{//the chain end was reached
			if(self::$WriteDetails) Logger::Write2("<<< end of chain >>>");		
			$address = new Address($parent_address_entity);
			$addresses[] = $address;
			if($address->IsProven()) return $address;
			return false;
		}		
		
		if(isset($step['cutoff'])) 
		{
			if($proven_address = self::perform_step($addresses, false, $parent_address_entity, $text)) return $proven_address;
			
			if(self::$WriteDetails) Logger::Write2(">>> CUTOFF: ".$step['cutoff']." ->");
			
			$cutoff_address_entity = $parent_address_entity->FindEntityInChain($step['cutoff'], false);
			if($cutoff_address_entity) $parent_address_entity = $cutoff_address_entity->Parent;
			else $parent_address_entity = $zero_address_entity;
			if(self::$WriteDetails) Logger::Write2("-> current entity: ".$parent_address_entity->Name);
		}
		
		if(self::$WriteDetails) Logger::Write2(">>> FIND: ".$step['find'].((isset($step['look_for_exact_entity_match']) and $step['look_for_exact_entity_match']) ? " (exactly)" : "").(isset($step['look_within_max_char_span']) ? "(".$step['look_within_max_char_span'].")" : "").", WITH: ".$parent_address_entity->WhereSql." ->");
		
		static $step2entity_names = array("postcodes"=>"postcode", "counties"=>"county", "traditional_counties"=>"traditional_county", "towns"=>"town", "villages"=>"village", "streets"=>"street", "thoroughfares"=>"thoroughfare");
		$entity_name = $step2entity_names[$step['find']];		
		
		if($parent_address_entity->HasChild($entity_name))
		{//it is possible when the same cutoff was done for several entities
			if(self::$WriteDetails) Logger::Write2("-> this search order was already gone");
			return;
		}
		
		$search_function = "find_".$step['find'];
		if(isset($step['look_for_exact_entity_match'])) self::$look_for_exact_entity_match = $step['look_for_exact_entity_match'];
		else self::$look_for_exact_entity_match = false;
		if(isset($step['look_within_max_char_span'])) self::$look_within_max_char_span = $step['look_within_max_char_span'];
		else self::$look_within_max_char_span = 22;
		$child_address_entities = self::$search_function($text, $parent_address_entity);
		if(empty($child_address_entities))
		{
			if(self::$WriteDetails) Logger::Write2("-> ---");
			$child_address_entity = new AddressEntity($parent_address_entity, $entity_name, null, null, null, null);
			return self::perform_step($addresses, $step['no'], $child_address_entity, $text);
		}
		if(self::$WriteDetails)
		{
			$aes = "";
			foreach($child_address_entities as $cae) $aes .= $cae->Value."; ";
			Logger::Write2("-> $aes");
		}
		foreach($child_address_entities as $cae)
		{
			if($proven_address = self::perform_step($addresses, $step['yes'], $cae, $text)) return $proven_address;
		}
	}
	
	//this search function always must be performed first 
	static private function find_postcodes($text, $root_address_entity)
	{	
		static $entity_name = "postcode";
		$child_address_entities = array();
		
		//search full postcodes
		$regexes = array("([BEGLMNSW]\d{1,2}?)\s*(\d[A-Z]{2})", "([A-Z]{2}\d{1,2}?)\s*(\d[A-Z]{2})", "([A-Z]{1,2}\d[A-Z])\s*(\d[A-Z]{2})");		
		for($op = 0; preg_match("@(?<=^|[^\w])(?'p'".join("|", $regexes).")(?=$|[^\w])@si", $text, $res, PREG_OFFSET_CAPTURE, $op); $op = $res['p'][1] + strlen($res['p'][0]))
		{
			$postcode = strtolower(preg_replace("@\s{2,}@is", " ", $res['p'][0]));
			if(Db::GetSingleValue("SELECT postcode FROM addresses WHERE postcode='$postcode'")) $child_address_entities[] = new AddressEntity($root_address_entity, $entity_name, $postcode, $res['p'][1], strlen($res['p'][0]), $res['p'][0]);
		}
		
		if(!empty($child_address_entities))	return $child_address_entities;	
		
		//search partial postcodes
		$regexes = array("([BEGLMNSW]\d{1,2}?)", "([A-Z]{2}\d{1,2}?)", "([A-Z]{1,2}\d[A-Z])");
		for($op = 0; preg_match("@(?<=^|[^\w])(?'p'".join("|", $regexes).")(?=$|[^\w])@si", $text, $res, PREG_OFFSET_CAPTURE, $op); $op = $res['p'][1] + strlen($res['p'][0]))
		{
			$postcode = strtolower($res['p'][0]);
			if(Db::GetSingleValue("SELECT postcode FROM addresses WHERE postcode LIKE '$postcode %'")) $child_address_entities[] = new AddressEntity($root_address_entity, $entity_name, $postcode, $res['p'][1], strlen($res['p'][0]), $res['p'][0]);
		}
		
		return $child_address_entities;
	}
	
	static private function find_counties($text, $parent_address_entity)
	{		
		static $entity_name = "county";
		$child_address_entities = array();
			
		$counties = Db::GetFirstColumnArray("SELECT DISTINCT county FROM addresses WHERE $parent_address_entity->WhereSql");		
		self::create_children_for_ae_by_finding_words_around_words("county", $text, $parent_address_entity, $counties, $child_address_entities);
		
		return $child_address_entities;
	}
		
	static private function find_traditional_counties($text, $parent_address_entity)
	{
		static $entity_name = "traditional_county";
		$child_address_entities = array();
		
		$counties = Db::GetFirstColumnArray("SELECT DISTINCT traditional_county FROM addresses WHERE $parent_address_entity->WhereSql");
		self::create_children_for_ae_by_finding_words_around_words($entity_name, $text, $parent_address_entity, $counties, $child_address_entities);
		
		return $child_address_entities;
	}
	
	static private function find_towns($text, $parent_address_entity)
	{
		static $entity_name = "town";
		$child_address_entities = array();
			
		$towns = Db::GetFirstColumnArray("SELECT DISTINCT town FROM addresses WHERE town<>'' AND $parent_address_entity->WhereSql");
		self::create_children_for_ae_by_finding_words_around_words($entity_name, $text, $parent_address_entity, $towns, $child_address_entities);
		
		return $child_address_entities;
	}
	
	static private function find_streets($text, $parent_address_entity)
	{		
		static $entity_name = "street";
		$child_address_entities = array();
			
		//if(!$parent_address_entity->FindEntityInChain('postcode') and !$parent_address_entity->FindEntityInChain('town') and !$parent_address_entity->FindEntityInChain('village') and !$parent_address_entity->FindEntityInChain('thoroughfare')) return $child_address_entities;
		
		$streets = Db::GetFirstColumnArray("SELECT DISTINCT street FROM addresses WHERE street<>'' AND $parent_address_entity->WhereSql");				
		self::create_children_for_ae_by_finding_words_around_words($entity_name, $text, $parent_address_entity, $streets, $child_address_entities);
								
		if(!empty($child_address_entities)) return $child_address_entities;
		if(self::$look_for_exact_entity_match or self::$look_for_exact_entity_match2) return $child_address_entities;
			
		$streets2 = array();
		foreach($streets as $s)
		{	
			if($s1 = self::apply_street_regex1($s)) $streets2[$s1][$s] = 0;
			if($s2 = self::apply_street_regex2($s)) $streets2[$s2][$s] = 0;
			if($s12 = self::apply_street_regex2($s1)) $streets2[$s12][$s] = 0;
		}
		self::create_children_for_ae_by_finding_words_around_words($entity_name, $text, $parent_address_entity, $streets2, $child_address_entities, true);
		
		return $child_address_entities;
	}
	
	static private function apply_street_regex1($street)
	{//done for 'queens terrace' street that can be written as 'queen's terrace'
		$street2 = preg_replace("@([a-z])s(?=$|[^\w])@is", "$1's", $street);
		if($street2 != $street) return trim($street2);
	}
	
	static private function apply_street_regex2($street)
	{//rise|bridge|park\s+road|church\s+road|square|park - were added for street LIKE 'battersea%'
//hill|place|park\s+east|industrial\s+park|court|terrace|yard|way|walk|fields?|gardens? - were added for street LIKE 'burnett%'
		$street2 = preg_replace("@(?<=[^\w])(road|street|mews|lane|close|drive|avenue|rise|bridge|park|church|square|hill|place|east|industrial|court|terrace|yard|way|walk|fields?|gardens?)\s*$@is", "", $street);
		if($street2 != $street) return trim($street2);
	}
	
	static private function find_villages($text, $parent_address_entity)
	{
		static $entity_name = "village";
		$child_address_entities = array();
		
		$villages = Db::GetFirstColumnArray("SELECT DISTINCT village FROM addresses WHERE village<>'' AND $parent_address_entity->WhereSql");			
		self::create_children_for_ae_by_finding_words_around_words($entity_name, $text, $parent_address_entity, $villages, $child_address_entities);
		
		return $child_address_entities;
	}
	
	static private function find_thoroughfares($text, $parent_address_entity)
	{
		static $entity_name = "thoroughfare";
		$child_address_entities = array();
		
		$thoroughfares = Db::GetFirstColumnArray("SELECT DISTINCT thoroughfare FROM addresses WHERE thoroughfare<>'' AND $parent_address_entity->WhereSql");
		self::create_children_for_ae_by_finding_words_around_words($entity_name, $text, $parent_address_entity, $thoroughfares, $child_address_entities);
			
		if(!empty($child_address_entities)) return $child_address_entities;
		if(self::$look_for_exact_entity_match or self::$look_for_exact_entity_match2) return $child_address_entities;
		
		$thoroughfares2 = array();
		foreach($thoroughfares as $t)
		{
			if($t1 = self::apply_thoroughfare_regex1($t)) $thoroughfares2[$t1][$t] = 0;
		}
		self::create_children_for_ae_by_finding_words_around_words($entity_name, $text, $parent_address_entity, $thoroughfares2, $child_address_entities, true);
		
		return $child_address_entities;
	}
	
	static private function apply_thoroughfare_regex1($thoroughfare)
	{
		$$thoroughfare2 = preg_replace("@^the\s+|(?<=[^\w])(court|centre)\s*$@is", "", $thoroughfare);
		if($thoroughfare2 != $thoroughfare) return trim($thoroughfare2);
	}
		
	//****************************************************************************************
	//core functions
	//****************************************************************************************
	static private $look_for_exact_entity_match = true;
	static private $look_within_max_char_span = 22;
	
	static private function create_children_for_ae_by_finding_words_around_words($entity_name, $text, $parent_address_entity, array $word2s, array &$child_address_entities, $word2s_has_values=false)
	{
		$text_length = strlen($text);
		
		if($word2s_has_values) 
		{
			$word22values = $word2s;
			$word2s = array_keys($word2s);
		}		
			 
		foreach($word2s as $word2)
		{	
			if($word2s_has_values) $entity_values = array_keys($word22values[$word2]);
			else $entity_values = array($word2);
			self::find_word_around_words($entity_name, $entity_values, $text, $text_length, $parent_address_entity, $word2, $child_address_entities);
		}
	}
	
	static private function find_word_around_words($entity_name, $entity_values, $text, $text_length, $parent_address_entity, $word2, array &$child_address_entities)
	{		
		$word2 = trim($word2);
		if(!$word2) return;
				
		$word2_length = strlen($word2);
		$search_radius = self::$look_within_max_char_span + $word2_length;
		
		$word2_pattern = preg_replace("@\.+@", "\\.?", $word2);
		$word2_pattern = preg_replace("@[\s\-]+@is", "[\\-\\s]", $word2_pattern);//treat '-' as space
		$word2_pattern = preg_replace("@\[\\\\-\\\s\]the\[\\\\-\\\s\]@is", "(?:[\\-\\s]the)?[\\-\\s]", $word2_pattern);//ignore 'the' within several words
		$word2_pattern = "@(?<=^|[^\w])".$word2_pattern."(?=$|[^\w])@is";
	    					
		//get chunks
		$chunks = array();
		if(!empty($parent_address_entity->EntitiesInFoundWordOrder))
		{
			$start = $parent_address_entity->EntitiesInFoundWordOrder[0]->FoundWordStart - $search_radius;
			if($start < 0) $start = 0;
			foreach($parent_address_entity->EntitiesInFoundWordOrder as $aeifwo)
			{	
				if($start + $search_radius < $aeifwo->FoundWordStart - $search_radius) 
				{
					$chunks[] = array('start'=>$start, 'length'=>$search_radius);
					$chunks[] = array('start'=>$aeifwo->FoundWordStart - $search_radius, 'length'=>$search_radius);
				}	
				elseif($start + $word2_length <= $aeifwo->FoundWordStart) $chunks[] = array('start'=>$start, 'length'=>($aeifwo->FoundWordStart - $start));
				
				$start = $aeifwo->FoundWordStart + $aeifwo->FoundWordLength;
			}
			
			$length = $search_radius;
			if($start + $length >= $text_length) $length = $text_length - $start;
			if($length >= $word2_length) $chunks[] = array('start'=>$start, 'length'=>$length);
		}	
		else
		{	
			if($text_length >= $word2_length) $chunks[] = array('start'=>0, 'length'=>$text_length);
		}				
		
		//search in chunks
		foreach($chunks as $c)
		{				
			$t = substr($text, $c['start'], $c['length']);
			if(preg_match($word2_pattern, $t, $res, PREG_OFFSET_CAPTURE)) 
			{
				foreach($entity_values as $ev) $child_address_entities[] = new AddressEntity($parent_address_entity, $entity_name, $ev, $c['start'] + $res[0][1], strlen($res[0][0]), $res[0][0]);//it can be so when looking with $look_for_exact_entity_match=false
			}
		}
	}			
}

class AddressEntity
{	
	//public static $ZERO_ADDRESS_ENTITY;
	
	public function __construct($parent_address_entity, $name, $value, $found_word_start, $found_word_length, $found_word)
	{
		if(!$parent_address_entity)
		{//creating ZERO_ADDRESS_ENTITY
			//self::$ZERO_ADDRESS_ENTITY = $this;
			$this->Name = "_ZERO_ADDRESS_ENTITY";
			$this->WhereSql = "1";
			$where_sql_and_entity_name_s = array();
			return;
		}
		//if(!in_array($name, AddressEntity::$Names)) Logger::Quit("Name entity '$name' is not allowed in AddressEntity.");
		//if(!$value) Logger::Quit("No value in AddressEntity.");
		//if(!$found_word) Logger::Quit("No word in AddressEntity.");
		if($found_word_start < 0) Logger::Quit("Start is wrong value in AddressEntity.");
		if($found_word_length < 0) Logger::Quit("Length is wrong value in AddressEntity.");
		if($parent_address_entity->FindEntityInChain($name, false)) Logger::Quit("The chain already contains $name");
		$this->Parent = $parent_address_entity;
		$this->Name = $name;
		$this->Value = $value;
		$this->FoundWordStart = $found_word_start;
		$this->FoundWordLength = $found_word_length;
		$this->FoundWord = $found_word;
		$this->create_where_sql();
		$this->create_found_words_order();
		
		//testing search order for loops/efficiency
		foreach($parent_address_entity->children as $cae) if($cae->FoundWordStart == $this->FoundWordStart and $cae->FoundWordLength == $this->FoundWordLength and $cae->Name == $this->Name and $cae->Value == $this->Value) Logger::Quit("This search order was already gone for ID=".AddressParser::ProductId().": $cae->Name => $cae->FoundWord => $cae->Value");//.Misc::GetArrayAsString($this->EntitiesInFoundWordOrder));
		$parent_address_entity->children[] = &$this;
	}
	private $children = array();
		
	public static $Names = array("postcode", "county", "traditional_county", "town", "village", "thoroughfare", "street");
	
	public $EntitiesInFoundWordOrder = array();	
	public $Name;	
	public $Value;	
	public $FoundWordStart;	
	public $FoundWordLength;	
	public $FoundWord;
	public $Parent;	
	
	//illegal extrem way used for the last chance
	public function SwitchToAnotherText2SearchForName($name)
	{	
		$ae = $this->FindEntityInChain($name, false);
		if($ae) $ae->Name = $name."_";//to pass check
		
		$this->EntitiesInFoundWordOrder = array();//to not split the text onto chunks while searching as they were created on another text
	}
	
	public function HasChild($name)
	{
		foreach($this->children as $cae) if($cae->Name == $name) return true;
		return false;
	}
	
	private function create_found_words_order() 
	{
		foreach($this->Parent->EntitiesInFoundWordOrder as $ae) $this->EntitiesInFoundWordOrder[] = $ae;
		if(!$this->FoundWordLength) return;
		$aecifwo_length = count($this->EntitiesInFoundWordOrder);
		for($i = 0; $i < $aecifwo_length; $i++)
		{
			$ae = $this->EntitiesInFoundWordOrder[$i];
			if($this->FoundWordStart < $ae->FoundWordStart)
			{
				array_splice($this->EntitiesInFoundWordOrder, $i, 0, array(&$this));
				return;
			}
		}
		$this->EntitiesInFoundWordOrder[] = &$this;
	}
	
	private function create_where_sql() 
	{
		if(empty($this->Value))
		{
			if($this->Parent->Parent) $this->WhereSql = $this->Parent->WhereSql;
			else $this->WhereSql = "1";
			return;
		}
		switch($this->Name)
		{
			case "postcode":
				if(preg_match("@.+\s.+@is", $this->Value)) $where_sql = "postcode='".addslashes($this->Value)."'";
				else $where_sql = "postcode LIKE '".addslashes($this->Value)." %'";				
				break;
			case "county":			
				$where_sql = "county='".addslashes($this->Value)."'";
				break;
			case "traditional_county":			
				$where_sql = "traditional_county='".addslashes($this->Value)."'";
				break;
			case "town":
				$where_sql = "town='".addslashes($this->Value)."'";
				break;
			case "village":
				$where_sql = "village='".addslashes($this->Value)."'";
				break;
			case "street":
				$where_sql = "street='".addslashes($this->Value)."'";
				break;
			case "thoroughfare":
				$where_sql = "thoroughfare='".addslashes($this->Value)."'";
				break;
			default:
				Logger::Quit("Address entity is not allowed: $this->Name");
		}    
		if($this->Parent->Parent and !empty($this->Parent->WhereSql) and $this->Parent->WhereSql != "1") $this->WhereSql = $this->Parent->WhereSql." AND $where_sql";	
		else $this->WhereSql = $where_sql;
	}
	public $WhereSql;
		
	public function FindEntityInChain($name, $ignore_empty=true) 
	{
		for($address_entity = $this; $address_entity->Parent; $address_entity = $address_entity->Parent) if($address_entity->Name == $name) return ($ignore_empty and !$address_entity->FoundWordLength) ? false : $address_entity;
		return null;		
	}
}

class Address 
{	
	public function __construct(AddressEntity $end_address_entity)
	{
		$this->EndAddressEntity = $end_address_entity;
		
		foreach(AddressEntity::$Names as $name) $this->set_entity_value($name);
		
		$this->set_name2states_before_completion();
		$this->complete();
		$this->set_rank_after_completion();
	}
	public $EndAddressEntity;
		
	private function set_entity_value($name)
	{		
		if($ae = $this->EndAddressEntity->FindEntityInChain($name)) $this->$name = $ae->Value;
		else $this->$name = null;
	}
	
	//try to fill the absent fields by the db data
	private function complete()
	{		
		if($this->EndAddressEntity->WhereSql == 1) return;
		
		$rs = Db::GetArray("SELECT * FROM addresses WHERE ".$this->EndAddressEntity->WhereSql);
		$postcode = $postcode1 = $county = $traditional_county = $town = $village = $street = $thoroughfare = null;
		foreach($rs as $r)
		{
			if($postcode)
			{
				if($postcode != $r['postcode'])	$postcode = false;
			}
			elseif($postcode !== false) $postcode = $r['postcode'];
			
			if($postcode1)
			{
				if($postcode1 != preg_replace("@\s.+@is", "", $r['postcode'])) $postcode1 = false;
			}
			elseif($postcode1 !== false) $postcode1 = preg_replace("@\s.+@is", "", $r['postcode']);
			
			if($county)
			{
				if($county != $r['county']) $county = false;
			}
			elseif($county !== false) $county = $r['county'];
			
			if($traditional_county)
			{
				if($traditional_county != $r['traditional_county']) $traditional_county = false;
			}
			elseif($traditional_county !== false) $traditional_county = $r['traditional_county'];
			
			if($town)
			{
				if($town != $r['town']) $town = false;
			}
			elseif($town !== false) $town = $r['town'];
			
			if($village)
			{
				if($village != $r['village']) $village = false;
			}
			elseif($village !== false) $village = $r['village'];
			
			if($street)
			{
				if($street != $r['street']) $street = false;
			}
			elseif($street !== false) $street = $r['street'];
			
			if($thoroughfare)
			{
				if($thoroughfare != $r['thoroughfare']) $thoroughfare = false;
			}
			elseif($thoroughfare !== false) $thoroughfare = $r['thoroughfare'];
		}
		$postcode = $postcode ? $postcode : $postcode1;
		if(!$this->postcode and $postcode) $this->postcode = $postcode;
		if(!$this->county and $county) $this->county = $county;
		if(!$this->traditional_county and $traditional_county) $this->traditional_county = $traditional_county;
		if(!$this->town and $town) $this->town = $town;
		if(!$this->village and $village) $this->village = $village;
		if(!$this->street and $street) $this->street = $street;
		if(!$this->thoroughfare and $thoroughfare) $this->thoroughfare = $thoroughfare;
	}
	
	static private $NAME2STATE2FACTOR = array("postcode"=>array("exact"=>15, "not_exact"=>10, "replenished"=>1, "empty"=>1), "county"=>array("exact"=>8, "not_exact"=>7, "replenished"=>1, "empty"=>1), "traditional_county"=>array("exact"=>8, "not_exact"=>7, "replenished"=>1, "empty"=>1), "town"=>array("exact"=>8, "not_exact"=>5, "replenished"=>1, "empty"=>1), "village"=>array("exact"=>8, "not_exact"=>4, "replenished"=>1, "empty"=>1), "thoroughfare"=>array("exact"=>5, "not_exact"=>2, "replenished"=>1, "empty"=>1), "street"=>array("exact"=>7, "not_exact"=>2, "replenished"=>1, "empty"=>1));
		
	public $Name2States = array();
	
	private function set_name2states_before_completion()
	{		
		foreach(AddressEntity::$Names as $name)
		{
			$ae = $this->EndAddressEntity->FindEntityInChain($name);
			if($ae)
			{
				if(strtolower($ae->FoundWord) == $ae->Value) $this->Name2States[$name] = "exact";
				else $this->Name2States[$name] = "not_exact";
			}
			else $this->Name2States[$name] = "empty";
		}
		
		if(preg_match("@.+\s+.+@is", $this->postcode))
		{//complete postcode
			$this->Name2States['postcode'] = "exact";
		}
		elseif($this->postcode)
		{//partial
			$this->Name2States['postcode'] = "not_exact";
		}
		
		if(AddressParser::$WriteDetails)Logger::Write2("Before completion:".$this->AsString());
		
		return $this->Name2States;
	}
	
	private function set_rank_after_completion()
	{														
		foreach(AddressEntity::$Names as $name) if($this->$name and $this->Name2States[$name] == "empty") $this->Name2States[$name] = "replenished";
		
		$this->Rank = 1;
		foreach(AddressEntity::$Names as $name) $this->Rank *= self::$NAME2STATE2FACTOR[$name][$this->Name2States[$name]];
		
		//address may not have town only if a prstial postcode 
		//if($this->Name2States[postcode] != "not_exact" and !$this->town) $this->Rank = 0;
				
		if(AddressParser::$WriteDetails) Logger::Write2("After completion:".$this->AsString());
		if(AddressParser::$WriteDetails) Logger::Write2("Rank: ".$this->Rank);
	}		
	
	public function AsString()
	{
		$s = "";
		foreach(AddressEntity::$Names as $name) $s .= "\r\n$name [".$this->Name2States[$name]."] = ".$this->$name;
		return $s;
	}
	
	public $Rank;
			
	public function IsProven()
	{
		return ($this->Rank > 100);		
	}
}

/*
$a = $h = $d = "";

$a=' Desirable One Bed Apartment, Royal Artillery Quays, Erebus Drive, Woolwich Arsenal';

//$h = " wentworth close, yateley, hampshire GU46	//6lF, clayton avenue, wembley, middlesex ha0 4ju, ";

//$h = " wentworth close, yateley,   /      clayton avenue, wembley, middlesex ";

$h = "Leicester Road, Ravenstone";
//street:leicester road	
//village:ravenstone

//$h = "Chatham Place, Reading, 2 Bedroom Apartment";
//town:reading

//$a = "Willingdon Road, Eastbourne";
//$h = '<h1 class="ad-name" itemprop="name">1 bedroom flat for sale - Under Offer</h1>  <span itemprop="price"> Â£99,950</span> Willingdon Road, Eastbourne';	
//$d = "gation into the working order of these items. All measurements are approximate and photographs provided for guidance only.";

//$d = "Well maintained receivers, students speech  speech receivers1, intercom -students no, speech receivers1, intercom receivers1 Description";

$p = array('address'=>$a, 'headline'=>$h, 'description'=>$d);
AddressParser::$WriteDetails = true;
$vp = new AddressParser($p);
$v = $vp->ParseAddress();

Logger::Write2($v);
Logger::Write2($p);
*/
?>