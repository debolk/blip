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
               'Reply-To: '.getenv('MAIL_FROM') . "\r\n" .
               'X-Mailer: PHP/' . phpversion() . "\r\n" .
               "MIME-Version: 1.0\r\n" .
               "Content-type: text/html; charset=utf-8\r\n";

    // Construct mail parameters
    $name = $this->name;
    $password = $this->password;
    $uid = $this->uid;
    $sec_email = getenv('MAIL_FROM');

    // Build message
    $message = <<<MAIL
    <p>Beste {$name},</p>
    <p>Je gegevens zijn toegevoegd aan de ledenadministratie van De Bolk. Je hebt hierdoor automatisch een Bolk-account gekregen. Hiermee heb je toegang tot verschillende systemen binnen De Bolk, zoals de <a href="http://wiki.debolk.nl">wiki met informatie</a>, je eigen <a href="http://webmail.bolkhuis.nl">e-mailaccount</a> en <a href="http://noms.debolk.nl">bolknoms</a>. Je account heeft nog wel een tijdelijk wachtwoord. Je moet dit wachtwoord veranderen om toegang te krijgen.</p>
    <ol>
      <li>Maak een VPN-verbinding met De Bolk. Instructies kun je vinden op <a href="http://wiki.debolk.nl/index.php/VPN">de wiki</a>. Je gebruikersnaam is "{$uid}" en je tijdelijke wachtwoord "{$password}".</li>
      <li><a href="http://gosa.i.bolkhuis.nl/password.php?uid={$uid}&directory=BOLKHUIS">Stel je wachtwoord opnieuw in</a>. Gebruik als huidig wachtwoord "{$password}".
    </ol>
    <p>Hierdoor worden al je wachtwoorden gereset en krijg je met één account toegang tot alle systemen. De volgende keer dat je verbinding maakt met de VPN, moet je wel je nieuwe wachtwoord invoeren, niet de tijdelijke.</p>
    <p>Als je nog vragen hebt, kun je contact opnemen met de secretaris via <a href="mailto:{$sec_email}">{$sec_email}</a> of in de sociëteit.</p>
MAIL;

    // Send the e-mail
    mail($this->email, '[DeBolk] Verander je wachtwoord', $message, $headers);
  }
}