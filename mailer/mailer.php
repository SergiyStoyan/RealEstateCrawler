<?
//********************************************************************************************
//Author: Sergey Stoyan, CliverSoft.com
//        http://cliversoft.com
//        stoyan@cliversoft.com
//        sergey.stoyan@gmail.com
//        1 February 2012
//Copyright: (C) 2012, Sergey Stoyan
//********************************************************************************************

$ABSPATH = dirname(__FILE__)."/../";

include_once("$ABSPATH/common/logger.php");
include_once("$ABSPATH/constants.php");

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

class Mailer
{	
	final function __construct($senderEmail, $senderName, $cc, $bcc, $replyTo)
	{
		$this->mailer = new PHPMailer(true);
		
		//$this->mailer->SMTPDebug = SMTP::DEBUG_SERVER;   //Enable verbose debug output
		$this->mailer->isSMTP();
		$this->mailer->Host       = '';     
		$this->mailer->SMTPAuth   = true;                 //Enable SMTP authentication
		$this->mailer->Username   = '';   //SMTP username
		$this->mailer->Password   = '';          //SMTP password
		//$this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;         //Enable TLS encryption; `PHPMailer::ENCRYPTION_SMTPS` encouraged
		$this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
		$this->mailer->Port       = 465; //TCP port to connect to, use 465 for `PHPMailer::ENCRYPTION_SMTPS` above
		
		$this->mailer->setFrom($senderEmail, $senderName);
		$this->mailer->addReplyTo($replyTo);
		$this->mailer->addCC($cc);
		$this->mailer->addBCC($bcc);

		$mailer->isHTML(false); 		
	}
	
	private $mailer;
	
	public function Send($email, $subject, $message)
	{
		try 
		{
			$mailer->clearAllRecipients();
			$mailer->addAddress($email); 
			$this->mailer->Subject = "MESSAGE: ".$subject;
			$this->mailer->Body    = $message;

			if($this->mailer->send() != true)
				throw new Exception('mailer->send() != true'." while sending to $email");
		} 
		catch (Exception $e) 
		{
			Logger::Error($e);
			throw $e;
		}
	}
}

?>