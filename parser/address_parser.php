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

include_once("$ABSPATH/common/logger.php");
include_once("$ABSPATH/constants.php");
include_once("$ABSPATH/common/db.php");
include_once("$ABSPATH/common/html_utilities.php");
include_once("$ABSPATH/common/misc.php");

AddressParser::Init();

//Db::Query("SET SESSION query_cache_type = ON");

//SELECT * FROM `addresses` WHERE `double_dependent_locality` LIKE '%Battersea%' OR county LIKE '%Battersea%' OR traditional_county LIKE '%Battersea%' OR town LIKE '%Battersea%' OR village LIKE '%Battersea%' OR street LIKE '%Battersea%' OR thoroughfare LIKE '%Battersea%' 

class AddressParser
{
	static public $WriteDetails = false;
	
	static public function Init()
	{
		if(Db::SmartQuery("SHOW TABLES LIKE 'addresses'")) self::$AddressConnection = Db::DEFAULT_CONNECTION_NAME;
		else
		{
			self::$AddressConnection = "ADDRESS_CONNECTION";
			Db::AddConnectionString("localhost", "crawler", "Qwerty1234", "real_estate", self::$AddressConnection);
			//Db::AddConnectionString("localhost", "root", "123", "cliver", self::$AddressConnection);
		}		
	}
	static public $AddressConnection;	
	
	final function __construct(&$rd, &$pd)
	{		
		$this->rd = &$rd;
		$this->pd = &$pd;		
		if(!isset($this->pd['address'])) $this->pd['address'] = Html::PrepareField($this->rd['address']);
		if(!isset($this->pd['headline'])) $this->pd['headline'] = Html::PrepareField($this->rd['headline']);
		if(!isset($this->pd['description'])) $this->pd['description'] = Html::PrepareField($this->rd['description']);
	}
	private $rd;
	private $pd;
	
	public function ParseAddress()
	{							
		if(self::$WriteDetails) $start_time = time();
		
		$address = $this->find_complete_address();		
		
		if(self::$WriteDetails) Logger::Write2("Time spent by AddressParser: ".(time() - $start_time));
		
		if(!$address or $address->Rank() < 1)
		{
			if(self::$WriteDetails) Logger::Write2("No address was found or it was dropped due to low rank.");
			return false;
		}
		
		$this->pd['postcode'] = $address->postcode;
		$this->pd['county'] = $address->county;
		$this->pd['town'] = $address->town;
		$this->pd['street'] = $address->street;
		$this->pd['village'] = $address->village;
		$this->pd['thoroughfare'] = $address->thoroughfare;
		
		return true;
	}
	
	private function find_complete_address()
	{
		if(self::$WriteDetails) Logger::Write2("Parsing address: ".$this->pd['address']);
		$address = $this->find_the_best_rank_address($this->pd['address']);
		self::$look_for_exact_street_match = true;
		if(!$address or $address->Rank() < 1)
		{
			if(self::$WriteDetails) Logger::Write2("Parsing headline: ".$this->pd['headline']);
			$address = self::find_the_best_rank_address($this->pd['headline']);
			if($address and $address->Rank() > 10) return $address;
			if(self::$WriteDetails) Logger::Write2("Parsing description: ".$this->pd['description']);
			$address = self::find_the_best_rank_address($this->pd['description']);
			if($address and $address->Rank() > 10) return $address;
		}
		if($address and !$address->postcode and $address->town and !$address->street)
		{
			$found_words = array();
			if(self::$WriteDetails) Logger::Write2("Parsing headline for street: ".$this->pd['headline']);
			if(self::find_street($this->pd['headline'], $found_words, $address)) return $address;
			if(self::$WriteDetails) Logger::Write2("Parsing description for street: ".$this->pd['description']);
			if(self::find_street($this->pd['description'], $found_words, $address)) return $address;
		}
		return $address;		
	}
	
	private function find_the_best_rank_address($text)
	{
		if(!isset($this->rd['postcode'])) $this->rd['postcode'] = null;
		if(!isset($this->rd['county'])) $this->rd['county'] = null;
		if(!isset($this->rd['town'])) $this->rd['town'] = null;
		if(!isset($this->rd['street'])) $this->rd['street'] = null;
		
		$best_address = new Address(1, 1, 1, 1);
		
		//some fields can be preset
		$address = new Address($this->rd['postcode'], $this->rd['county'], $this->rd['town'], $this->rd['street']);
		self::find_address($text, $address, array(
				"find_postcode",
				"find_county",
				"find_traditional_county",
				"find_town",
				"find_village",
				"find_street",
				"find_thoroughfare"
			)
		);
		if($best_address->Rank() < $address->Rank())
		{
			$best_address = $address;
			if($best_address->Rank() > 10) return $best_address;
		}
		
		$address = new Address($this->rd['postcode'], $this->rd['county'], $this->rd['town'], $this->rd['street']);
		self::find_address($text, $address, array(
				"find_postcode",
				"find_traditional_county",
				"find_county",
				"find_town",
				"find_village",
				"find_street",
				"find_thoroughfare"
			)
		);
		if($best_address->Rank() < $address->Rank())
		{
			$best_address = $address;
			if($best_address->Rank() > 10) return $best_address;
		}
		
		$address = new Address($this->rd['postcode'], $this->rd['county'], $this->rd['town'], $this->rd['street']);
		self::find_address($text, $address, array(
				"find_postcode",
				"find_town",
				"find_county",
				"find_traditional_county",
				"find_village",
				"find_street",
				"find_thoroughfare"
			)
		);
		if($best_address->Rank() < $address->Rank())
		{
			$best_address = $address;
			if($best_address->Rank() > 10) return $best_address;
		}
		
		/*$address = new Address($this->pd['postcode'], $this->pd['county'], $this->pd['town'], $this->pd['street']);
		self::find_address($text, $address, array(
				"find_postcode",
				"find_county",
				"find_traditional_county",
				"find_street",
				"find_town",
				"find_village",
				"find_thoroughfare"
			)
		);
		if($best_address->Rank() < $address->Rank())
		{
			$best_address = $address;
			if($best_address->Rank() > 10) return $best_address;
		}*/
		/*
		$address = new Address($this->pd['postcode'], $this->pd['county'], $this->pd['town'], $this->pd['street']);
		self::find_address($text, $address, array(
				"find_postcode",
				"find_county",
				"find_traditional_county",
				"find_village",
				"find_thoroughfare",
				"find_town",
				"find_street"
			)
		);
		if($best_address->Rank() < $address->Rank())
		{
			$best_address = $address;
			if($best_address->Rank() > 10) return $best_address;
		}*/
		
		return $best_address;
	}
					
	static private function find_address($text, Address &$address, $ordered_search_functions)
	{		
		$found_words = array();
		
		foreach($ordered_search_functions as $osf) self::$osf($text, $found_words, $address);
						
		if(self::$WriteDetails) 
		{
			Logger::Write2("find_address: ".Misc::GetArrayAsString($ordered_search_functions));
			Logger::Write2("Before competion: ".$address->AsString());
		}		
		$address->CompleteAndSetRanks();
		if(self::$WriteDetails)
		{
			Logger::Write2("After competion: ".$address->AsString());
			Logger::Write2("Rank: ".$address->Rank());
		}
	}
	
	static private function find_postcode($text, array &$found_words, Address &$address)
	{
		if($address->postcode) return;
		
		if(strlen($text) > 6)
		{
			if($postcode = self::find_full_postcode(
				"@(?<=^|[^\w])([BEGLMNSW]\d{1,2}?)\s*(\d[A-Z]{2})(?=$|[^\w])@si", 
				$text, $found_words, $address)
			) return $postcode;
		
			if($postcode = self::find_full_postcode(
				"@(?<=^|[^\w])([A-Z]{2}\d{1,2}?)\s*(\d[A-Z]{2})(?=$|[^\w])@si", 
				$text, $found_words, $address)
			) return $postcode;	
		
			if($postcode = self::find_full_postcode(
				"@(?<=^|[^\w])([A-Z]{1,2}\d[A-Z])\s*(\d[A-Z]{2})(?=$|[^\w])@si", 
				$text, $found_words, $address)
			) return $postcode;			
		}
		
		if(strlen($text) > 2)
		{
			if($postcode = self::find_partial_postcode(
				"@(?<=^|[^\w])([BEGLMNSW]\d{1,2})(?=$|[^\w])@s", 
				$text, $found_words, $address)
			) return $postcode;
		
			if($postcode = self::find_partial_postcode(
				"@(?<=^|[^\w])([A-Z]{2}\d{1,2})(?=$|[^\w])@s", 
				$text, $found_words, $address)
			) return $postcode;
		
			if($postcode = self::find_partial_postcode(
				"@(?<=^|[^\w])([A-Z]{1,2}\d[A-Z])(?=$|[^\w])@s", 
				$text, $found_words, $address)
			) return $postcode;			
		}
	}
	
	static private function find_full_postcode($regex, $text, array &$found_words, Address &$address)
	{		
		for($op = 0; preg_match($regex, $text, $res, PREG_OFFSET_CAPTURE, $op); $op = $res[2][1] + strlen($res[2][0]))
		{
			$postcode = strtolower($res[1][0]." ".$res[2][0]);
			if(!Db::GetSingleValue("SELECT postcode FROM addresses WHERE postcode='$postcode'", self::$AddressConnection)) continue;
			$found_words[] = array('start'=>$res[1][1], 'length'=>strlen($res[1][0]), 'word'=>$postcode);
			$address->postcode = $postcode;
			return $postcode;
		}
	}
	
	static private function find_partial_postcode($regex, $text, array &$found_words, Address &$address)
	{			
		for($op = 0; preg_match($regex, $text, $res, PREG_OFFSET_CAPTURE, $op); $op = $res[1][1] + strlen($res[1][0]))
		{
			$postcode = strtolower($res[1][0]);
			if(!Db::GetSingleValue("SELECT postcode FROM addresses WHERE postcode LIKE '$postcode %'", self::$AddressConnection)) continue;
			$found_words[] = array('start'=>$res[1][1], 'length'=>strlen($res[1][0]), 'word'=>$postcode);
			$address->postcode = $postcode;
			return $postcode;
		}
	}
	
	static private function find_county($text, array &$found_words, Address &$address)
	{
		if($address->county or $address->traditional_county) return;	
		if(strlen($text) < 5) return;
		
		$counties = Db::GetFirstColumnArray("SELECT DISTINCT county FROM addresses WHERE $address->WhereSql", self::$AddressConnection);
		foreach($counties as $v)
		{
			if(!self::find_word_around_words($text, $found_words, $v)) continue;
			$address->county = $v;
			return $v;
		}
	}
		
	static private function find_traditional_county($text, array &$found_words, Address &$address)
	{
		if($address->county or $address->traditional_county) return;		
		if(strlen($text) < 5) return;
		
		$counties = Db::GetFirstColumnArray("SELECT DISTINCT traditional_county FROM addresses WHERE $address->WhereSql", self::$AddressConnection);
		foreach($counties as $v)
		{
			if(!self::find_word_around_words($text, $found_words, $v)) continue;
			$address->traditional_county = $v;
			return $v;
		}
	}
	
	static private function find_town($text, array &$found_words, Address &$address)
	{
		if($address->town) return;		
		if(strlen($text) < 4) return;
		
		$towns = Db::GetFirstColumnArray("SELECT DISTINCT town FROM addresses WHERE town<>'' AND $address->WhereSql", self::$AddressConnection);
		foreach($towns as $v)
		{
			if(!self::find_word_around_words($text, $found_words, $v)) continue;
			$address->town = $v;
			return $v;
		}
	}
	
	static private $look_for_exact_street_match = false;
	static private function find_street($text, array &$found_words, Address &$address)
	{
		if($address->street) return;		
		if(!$address->postcode and !$address->town and !$address->village and !$address->thoroughfare) return;
		if(strlen($text) < 8) return;
		
		$streets = Db::GetFirstColumnArray("SELECT DISTINCT street FROM addresses WHERE street<>'' AND $address->WhereSql", self::$AddressConnection);				
		foreach($streets as $v)
		{
			if(!self::find_word_around_words($text, $found_words, $v)) continue;
			$address->street = $v;
			return $v;
		}
		if(self::$look_for_exact_street_match) return;
		$streets_ = array();
		foreach($streets as $v)
		{
			//rise|bridge|park\s+road|church\s+road|square|park - were added for street LIKE 'battersea%'
			//hill|place|park\s+east|industrial\s+park|court|terrace|yard|way|walk|fields?|gardens? - were added for street LIKE 'burnett%'
			$v_ = preg_replace("@(?<=[^\w])(road|street|mews|lane|close|drive|avenue|rise|bridge|park|church|square|hill|place|east|industrial|court|terrace|yard|way|walk|fields?|gardens?)\s*$@is", "", $v);
			if(array_key_exists($v_, $streets_)) continue;
			$streets_[$v_] = 1;
			if(self::find_word_around_words($text, $found_words, $v_))
			{
				$address->street = $v;
				return $v;				
			}
			//done for 'queens terrace' street that can be written as 'queen's terrace'
			$v_ = preg_replace("@([a-z])s(?=$|[^\w])@is", "$1's", $v);
			if($v == $v_) continue;
			if(self::find_word_around_words($text, $found_words, $v_))
			{
				$address->street = $v;
				return $v;				
			}
		}
	}
	
	static private function find_village($text, array &$found_words, Address &$address)
	{
		if($address->village) return;		
		if(strlen($text) < 5) return;
		
		$villages = Db::GetFirstColumnArray("SELECT DISTINCT village FROM addresses WHERE village<>'' AND $address->WhereSql", self::$AddressConnection);
		foreach($villages as $v)
		{
			if(!self::find_word_around_words($text, $found_words, $v)) continue;
			$address->village = $v;
			return $v;
		}
	}
	
	static private function find_thoroughfare($text, array &$found_words, Address &$address)
	{
		if($address->thoroughfare) return;		
		if(strlen($text) < 5) return;
		
		$thoroughfares = Db::GetFirstColumnArray("SELECT DISTINCT thoroughfare FROM addresses WHERE thoroughfare<>'' AND $address->WhereSql", self::$AddressConnection);		
		foreach($thoroughfares as $v)
		{
			if(!self::find_word_around_words($text, $found_words, $v)) continue;
			$address->thoroughfare = $v;
			return $v;
		}
		foreach($thoroughfares as $v)
		{
			$v_ = preg_replace("@^the\s+|(?<=[^\w])(court|centre)\s*$@is", "", $v);
			if($v == $v_) continue;
			if(!self::find_word_around_words($text, $found_words, $v_)) continue;
			$address->thoroughfare = $v;
			return $v;
		}		
	}
		
	//****************************************************************************************
	//core functions
	//****************************************************************************************
	static private function find_word_around_words($text, array &$found_words, $word2, $max_char_span=22)
	{
		$word2 = trim($word2);
		if(!$word2) return false;
		
		$w2l = strlen($word2);
		$search_radius = $max_char_span + $w2l;
		
		$w2_pattern = preg_replace("@\.+@", "\\.?", $word2);
		$w2_pattern = preg_replace("@[\s\-]+@is", "[\\-\\s]", $w2_pattern);//treat '-' as space
		$w2_pattern = preg_replace("@\[\\\\-\\\s\]the\[\\\\-\\\s\]@is", "(?:[\\-\\s]the)?[\\-\\s]", $w2_pattern);//ignore 'the' within several words
		$w2_pattern = "@(?<=^|[^\w])".$w2_pattern."(?=$|[^\w])@is";
	    	
		$tl = strlen($text);
		
		$chunks = array();
		$fws_count = count($found_words);
		if($fws_count > 0)
		{
			$start = $found_words[0]['start'] - $search_radius;
			if($start < 0) $start = 0;	
			
			foreach($found_words as $fw)
			{	
				if($start + $search_radius < $fw['start'] - $search_radius) 
				{
					$chunks[] = array('start'=>$start, 'length'=>$search_radius);
					$chunks[] = array('start'=>$fw['start'] - $search_radius, 'length'=>$search_radius);
				}	
				elseif($start + $w2l <= $fw['start']) $chunks[] = array('start'=>$start, 'length'=>($fw['start'] - $start));
				
				$start = $fw['start'] + $fw['length'];
			}
			
			$length = $search_radius;
			if($start + $length >= $tl) $length = $tl - $start;					
			$chunks[] = array('start'=>$start, 'length'=>$length);
		}	
		else
		{	
			$chunks[] = array('start'=>0, 'length'=>$tl);
		}
				
		foreach($chunks as $c)
		{				
	//if($c['start']<0 or $c['length']<0 or $c['start']+$c['length']>=count($text))	print($c['start']."-".$c['length']."-".count($text)."-".$tl."\n\n");
			$t = substr($text, $c['start'], $c['length']);				
			if(preg_match($w2_pattern, $t, $res, PREG_OFFSET_CAPTURE))
			{
				$fw = array();
				$fw['start'] = $c['start'] + $res[0][1];
				$fw['length'] = strlen($res[0][0]);
	            $fw['word'] = $res[0][0];
	            for($i = 0; $i < $fws_count; $i++) 
	            {
	                if($fw['start'] < $found_words[$i]['start'])
	                {
	                    array_splice($found_words, $i, 0, array($fw));
	                    return true;
	                }
	            }
	            $found_words[] = $fw;
				return true;
			}
		}
		
		return false;
	}		
}

class Address implements arrayaccess 
{	
	public function __construct($postcode, $county, $town, $street)
	{
		if($postcode) $this->postcode = $postcode;
	    if($county) $this->county = $county;
	    if($town) $this->town = $town;
		if($street) $this->street = $street;
	}
	
	private $container = array();
	
	public function offsetSet($key, $value) 
	{
		if(!$value) throw new Exception("$key has no value.");
		switch($key)
		{
			case "postcode":
				if(preg_match("@.+\s.+@is", $value)) $this->where_sqls['postcode'] = "postcode='".addslashes($value)."'";
				else $this->where_sqls['postcode'] = "postcode LIKE '".addslashes($value)." %'";				
				break;
			case "county":			
				$this->where_sqls['county'] = "county='".addslashes($value)."'";
				break;
			case "traditional_county":			
				$this->where_sqls['traditional_county'] = "traditional_county='".addslashes($value)."'";
				break;
			case "town":
				$this->where_sqls['town'] = "town='".addslashes($value)."'";
				break;
			case "village":
				$this->where_sqls['village'] = "village='".addslashes($value)."'";
				break;
			case "street":
				$this->where_sqls['street'] = "street='".addslashes($value)."'";
				break;
			case "thoroughfare":
				$this->where_sqls['thoroughfare'] = "thoroughfare='".addslashes($value)."'";
				break;
			default:
				throw new Exception("Address field is not allowed: $key");
		}    
		if(isset($this->container[$key])) throw new Exception("$key cannot be re-set.");
		$this->container[$key] = $value;	
		$this->WhereSql = join(" AND ", $this->where_sqls);
		if(!$this->WhereSql) $this->WhereSql = "1";
	}
	private $where_sqls;
	public $WhereSql = 1;
	
	public function offsetGet($key) 
	{
		if(!isset($this->container[$key])) return null;
		return $this->container[$key];
	}

	public function offsetExists($key) 
	{
		return isset($this->container[$key]);
	}
	
	public function offsetUnset($key) 
	{
		throw new Exception("$key cannot be unset.");
	}
	
	public function __set($key, $value)
	{
		return $this->offsetSet($key, $value);
	}
	
	public function __get($key)
	{
		return $this->offsetGet($key);
	}
	
	//try to fill the absent fields by the db data
	public function CompleteAndSetRanks()
	{
		if($this->rank_after_complete) return;
		$this->rank_before_complete = $this->get_rank_before_complete();
		
		$address = $this;
		if(!$address->postcode and !$address->county and !$address->town and !$address->street and !$address->village and !$address->thoroughfare) return;
		
		$rs = Db::GetArray("SELECT * FROM addresses WHERE $address->WhereSql", AddressParser::$AddressConnection);
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
		if(!$address->postcode and $postcode) $address->postcode = $postcode;
		if(!$address->county and $county) $address->county = $county;
		if(!$address->traditional_county and $traditional_county) $address->traditional_county = $traditional_county;
		if(!$address->town and $town) $address->town = $town;
		if(!$address->village and $village) $address->village = $village;
		if(!$address->street and $street) $address->street = $street;
		if(!$address->thoroughfare and $thoroughfare) $address->thoroughfare = $thoroughfare;
						
		$this->rank_after_complete = $this->get_rank_after_complete();		
	}
		
	private function get_rank_before_complete()
	{
		if(preg_match("@.+\s+.+@is", $this->postcode))
		{//complete postcode
			if($this->county or $this->traditional_county)
			{
				if($this->town)
				{
					if($this->street or $this->village or $this->thoroughfare) return 10;
				}
				return 9;
			}
			return 8;
		}	
		if($this->postcode)
		{
			if($this->county or $this->traditional_county)
			{
				if($this->town)
				{
					if($this->street or $this->village or $this->thoroughfare) return 9;
					else return 8;
				}
				else return 7;
			}
			else return 6;
		}	
		if($this->county or $this->traditional_county)
		{
			if($this->town)
			{
				if($this->street or $this->village or $this->thoroughfare) return 5;
				else return 4;
			}
			else return 1;
		}
		if($this->town)
		{
			if($this->street or $this->village or $this->thoroughfare) return 2;
			else return 1;
		}
		if($this->village or $this->thoroughfare)
		{
			if($this->street) return 2;
			else return 1;
		}
		return 0;
	}
	
	private function get_rank_after_complete()
	{
		if(preg_match("@.+\s+.+@is", $this->postcode))
		{//complete postcode
			if($this->county or $this->traditional_county)
			{
				if($this->town)
				{
					if($this->street or $this->village or $this->thoroughfare) return 10;
					return 9;
				}					
				if(AddressParser::$WriteDetails) Logger::Write2("Postcode has no town so it is invalid: $this->postcode");
				return 0;//invalid postcode
			}
			//if(AddressParser::$WriteDetails) Logger::Write2("Postcode is invalid: $this->postcode");
			return 1;
		}
		if($this->postcode)
		{
			if($this->county or $this->traditional_county)
			{
				if($this->town)
				{
					if($this->street or $this->village or $this->thoroughfare) return 9;
					else return 8;
				}					
				//if(AddressParser::$WriteDetails) Logger::Write2("Postcode is invalid: $this->postcode");
				return 2;
			}
			//if(AddressParser::$WriteDetails) Logger::Write2("Postcode has no county: $this->postcode");
			return 1;
		}	
		if($this->county or $this->traditional_county)
		{
			if($this->town)
			{
				if($this->street or $this->village or $this->thoroughfare) return 5;
				else return 4;
			}
			else return 1;
		}
		if($this->town and strlen($this->town) > 8)
		{//some towns have several counties so no county determined
			if($this->street or $this->village or $this->thoroughfare) return 2;
			else return 1;
		}
		/*if(($this->village and strlen($this->village) > 8) or ($this->thoroughfare and strlen($this->thoroughfare) > 8))
		{//some villages have several counties so no county determined
			if($this->street) return 2;
			else return 1;
		}*/
		return 0;
	}
	
	public function Rank()
	{
		return $this->rank_before_complete * $this->rank_after_complete;
	}
	private $rank_before_complete;
	private $rank_after_complete;
	
	public function AsString()
	{
		return Misc::GetArrayAsString($this->container);
	}
}



/*
$h = "Flat to rent in CINNABAR WHARF WEST WAPPING HIGH STREET WAPPING - 3 bedrooms Weekly rental of Â£5,000 flat 3 bedrooms";

//$d = "Well maintained receivers, students speech  speech receivers1, intercom -students no, speech receivers1, intercom receivers1 Description";

$d = 
<<<STR


STR;

$p = array('headline'=>$h, 'description'=>$d);
AddressParser::$WriteDetails = true;
$vp = new AddressParser($p);
$v = $vp->ParseAddress();

print $v."<br>";
print_r($p);
*/
?>