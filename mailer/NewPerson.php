<?php

namespace Mailer;


use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

class NewPerson
{
    private $rec_address;
    private $uid;
    private $name;
    private $password;

	private $mail;

	private static $mail_from;
	private static $imap_user;
	private static $imap_password;
	private static $imap_host;
	private static $imap_port;
	private static $imap_security;

	public static function Initialise($mail_from, $imap_user, $imap_password, $imap_host, $imap_port, $imap_security) {
		self::$mail_from = $mail_from;
		self::$imap_user = $imap_user;
		self::$imap_password = $imap_password;
		self::$imap_host = $imap_host;
		self::$imap_port = $imap_port;
		self::$imap_security = $imap_security;
	}

    /**
     * Constructs a new email
     * @param string $email    valid e-mailaddress of the new user
     * @param string $uid      UID of the new user
     * @param string $name     full name of the new user
     * @param string $password plaintext password of the new user
     */
    public function __construct($email, $uid, $name, $password)
    {
        list($this->rec_address, $this->uid, $this->name, $this->password) = [$email, $uid, $name, $password];
		$this->mail = new PHPMailer();
	    $this->mail->isSMTP();
	    $this->mail->Host = self::$imap_host;
	    $this->mail->Port = self::$imap_port;
	    $this->mail->SMTPAuth = true;
	    $this->mail->Username = self::$imap_user;
	    $this->mail->Password = self::$imap_password;
	    $this->mail->SMTPSecure = self::$imap_security;
		$this->mail->CharSet = "UTF-8";
    }

    /**
     * Sends the e-mail
     * @return bool TRUE on success, FALSE if otherwise
     */
    public function send() : bool
    {

		// Construct mail parameters
        $name = $this->name;
        $password = $this->password;
        $uid = $this->uid;

		$this->mail->setFrom(self::$mail_from);
		$this->mail->addAddress($this->rec_address);
		$this->mail->Subject = '[DeBolk] Login details';
		$this->mail->isHTML();


        // Build message
        $message = <<<MAIL
    <p style="font-size: small">-- English below --</p>
    <p>Beste {$name},</p>
    <p>Je gegevens zijn toegevoegd aan de ledenadministratie van De Bolk. Je hebt hierdoor automatisch een Bolk-account gekregen. Hiermee heb je toegang tot verschillende systemen binnen De Bolk, zoals de <a href="http://wiki.debolk.nl">wiki</a>, je eigen <a href="http://webmail.bolkhuis.nl">e-mailaccount</a> en <a href="http://noms.debolk.nl">bolknoms</a>. Je account heeft nog wel een tijdelijk wachtwoord. Je moet dit wachtwoord veranderen om toegang te krijgen.</p>
    <ol>
      <li>Maak een VPN-verbinding met De Bolk. Instructies kun je vinden op <a href="http://wiki.debolk.nl/index.php/VPN">de wiki</a>. Je gebruikersnaam is <b>{$uid}</b> en je tijdelijke wachtwoord is <b>{$password}</b>.</li>
      <li><a href="http://fusion.i.bolkhuis.nl/password.php?uid={$uid}&directory=BOLKHUIS">Stel je wachtwoord opnieuw in</a>.
    </ol>
    <p>Hierdoor word je wachtwoord gereset en krijg je met één account toegang tot alle systemen. De volgende keer dat je verbinding maakt met de VPN, moet je wel je nieuwe wachtwoord invoeren, niet de tijdelijke.</p>
    <p>Als je nog vragen hebt, kun je contact opnemen met de secretaris via <a href="mailto:secretaris@nieuwedelft.nl">secretaris@nieuwedelft.nl</a> of in de sociëteit.</p>
	<br>
	<hr style="width:100%;size:2">
	<br>
	<p>Dear {$name},</p>
	<p>Your data has been added to the member administration of de Bolk. This means you've automatically received a Bolk-account. With this you have access to various systems within de Bolk, like the <a href="http://wiki.debolk.nl">wiki</a>, your own <a href="http://webmail.bolkhuis.nl">e-mailaccount</a> and <a href="http://noms.debolk.nl">bolknoms</a>. Your account does have a temporary password, you need to change it to gain access.</p>
    <ol>
      <li>Connect with de Bolk VPN. You can find instructions on <a href="http://wiki.debolk.nl/index.php/VPN">the wiki</a>. Your username <b>{$uid}</b> and your temporary password is <b>{$password}</b>.</li>
      <li><a href="http://fusion.i.bolkhuis.nl/password.php?uid={$uid}&directory=BOLKHUIS">Reset your password</a>.
    </ol>
    <p>This reset your password and gets you access to all our systems with a single account. You do need to use your new password the next time you connect with the VPN, not the temporary one.</p>
    <p>If you have any question, you can contact the secretary via <a href="mailto:secretaris@nieuwedelft.nl">secretaris@nieuwedelft.nl</a> or in the society.</p>
MAIL;
		$this->mail->Body = $message;



        try {
	        // Send the e-mail
	        return $this->mail->send();
        } catch (Exception $e) {
			syslog(LOG_ERR, $e->errorMessage());
			return false;
        }
    }

	public function getError() : string {
		return $this->mail->ErrorInfo;
	}
}
