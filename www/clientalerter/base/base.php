<?

//********************************************************************************************
//Author: Sergey Stoyan, CliverSoft.com
//        http://cliversoft.com
//        stoyan@cliversoft.com
//        sergey.stoyan@gmail.com
//        27 February 2007
//Copyright: (C) 2007, Sergey Stoyan
//********************************************************************************************

class Base
{
	protected $class;

	public function __construct($class='')
	{
		if($class) $this->class = $class;
	}

	//output html content
	public function Display()
	{
	}

	static function query($sql)
	{
		$result = mysql_query($sql) or self::error("Query failed: $sql\n".mysql_error());
		return $result;
	}

	/*private $errors = array();

	//save error but not output yet
	public function SetError($error)
	{
		$this->error[] = $error;
	}

	//
	public function IsErrors()
	{
		return count($this->error) > 0;
	}

	//output all saved errors
	public function PrintErrors()
	{
		if(is_array($this->error)) $this->error = join("<br>\n", $this->error);
		print "<div class='error'>".$this->error."</div>";
		$this->error = array();
	}*/

	//just output an error or an error array
	/*function error($errors)
	{
		return self::_error($errors);
	}*/

	//just output an error or an error array
	static function error($errors)
	{
		if(is_array($errors)) $errors = join("<br>\n", $errors);
		print "<div class='error'>".$errors."</div>\n";
	}

	//just output a message or a message array
	static function notify($message)
	{
		if(is_array($message)) $message = join("<br>\n", $message);
		print "<div class='message'>".$message."</div>\n";
	}
}


?>