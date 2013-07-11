<?php

namespace Mailer;

class NewPerson
{
  private $email;
  private $uid;
  private $name;
  private $password;

  /**
   * Constructs a new email
   * @param string $email    valid e-mailaddress of the new user
   * @param string $uid      UID of the new user
   * @param string $name     full name of the new user
   * @param string $password plaintext password of the new user
   */
  public function __construct($email, $uid, $name, $password)
  {
    list($this->email, $this->uid, $this->name, $this->password) = [$email, $uid, $name, $password];
  }

  /**
   * Sends the e-mail
   * @return void
   */
  public function send()
  {
    // Construct needed headers from configuration
    $headers = 'From: '.getenv('MAIL_FROM') . "\r\n" .
               'Reply-To: '.getenv('MAIL_REPLYTO') . "\r\n" .
               'X-Mailer: PHP/' . phpversion() . "\r\n" .
               "MIME-Version: 1.0\r\n" .
               "Content-type: text/html; charset=utf-8\r\n";

    // Build message
    $message = <<<MAIL
    <p>Beste {$this->name},</p>
    <p>Je gegevens zijn toegevoegd aan de ledenadministratie van De Bolk. Je hebt hierdoor automatisch een Bolk-account gekregen. Hiermee heb je toegang tot verschillende systemen binnen De Bolk. Je account heeft nog wel een tijdelijk wachtwoord. Je moet dit wachtwoord veranderen om toegang te krijgen.</p>
    <ol>
      <li>Maak een VPN-verbinding met De Bolk. Hoe dat moet kun je lezen <a href="http://wiki.debolk.nl/index.php/VPN">op de wiki</a>. Je gebruikersnaam is "{$this->uid}" en je (tijdelijke) wachtwoord "{$this->password}".</li>
      <li>Ga naar de <a href="http://gosa.i.bolkhuis.nl/password.php?uid={$this->uid}&directory=BOLKHUIS">Gosa-interface</a> en stel een nieuw wachtwoord in. Gebruik als huidig wachtwoord "{$this->password}".
      </ol>
    <p>Als je nog vragen hebt, kun je contact opnemen met de secretaris via <a href="mailto:{getenv('MAIL_REPLYTO')}">{getenv('MAIL_REPLYTO')}</a> of in de sociëteit.</p>
MAIL;

    // Send the e-mail
    mail($this->email, '[DeBolk] Verander je wachtwoord', $message, $headers);
  }
}