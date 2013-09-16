<?php

namespace Models;

/**
 * Handles creation of new users in Ldap
 */
class LdapPerson extends LdapObject {

  private $calculations;

  /**
   * Creates a new LdapEntry
   * @param array $attributes  The attributes to set
   */
  public function __construct($attributes = array())
  {
    parent::__construct($attributes);
    $this->calculations = array(
      'givenname' => function() { $this->setName(); },

      'sn' => function() { $this->setName; },

      'uid' => function() {
        $this->__set('homedirectory', '/home/' . $this->attributes['uid']);
				if(!isset($this->dn))
					$this->dn = 'uid=' . $this->attributes['uid'] . ',ou=people,o=nieuwedelft,dc=bolkhuis,dc=nl';
      },

      'uidnumber' => function() {
        $this->__set('sambasid', 'S-1-5-21-1816619821-1419577557-1603852640-'.(1000+2*$this->attributes['uidnumber']));
      },

      'gidnumber' => function() {
        $this->__set('sambaprimarygroupsid', 'S-1-5-21-1816619821-1419577557-1603852640-'.(1001+2*$this->attributes['gidnumber']));
      },
    );

  }
  
  /**
   * Finds an unused uidnumber in ldap
   * @returns int         an unused uidnumber
   */
  protected static function findUidnumber()
  {
    $ldap = \Helper\LdapHelper::connect();

    // Find all existing entries with a uidNumber
    $search = $ldap->search('(objectClass=posixAccount)', array('uidNumber'));

    // Slap array until it's formatted
    $numbers = array_map(function($e){
      return (int)$e['uidnumber'][0];
    }, $search);

    $max = 1000;
    foreach($numbers as $number)
      if($number > $max && $number < 65000)
        $max = $number;

    return $max + 1;
  }

  /**
   * Calculates the name of this person using first and last name
   * @return string     the name of the person
   */
  protected function setName()
  {
    $parts = array();
    
    if(isset($this->attributes['givenname']))
      $parts[] = $this->attributes['givenname'];
    if(isset($this->attributes['sn']))
      $parts[] = $this->attributes['sn'];

    $name = implode(" ", $parts);

		setlocale(LC_ALL, 'en_US.UTF8');
		$gecos = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name);

    $this->__set('gecos', $gecos);
    $this->__set('cn', $gecos);
  }

  /**
   * Creates an LdapPerson from an uid
   * @param  string $uid  the uid to look up
   * @return LdapPerson   the specified entry under the dn
   */
  public static function fromUid($uid)
  {
    $ldap = \Helper\LdapHelper::connect();

    $dn = $ldap->getDn($uid);
    if(!$dn)
      return false;

    $data = $ldap->get($dn, 'iNetOrgPerson');
    $data = $ldap->flatten($data);

    $result = new self($data);
    $result->exists = true;
    $result->dn = $dn;

    return $result;
  }

  /**
   * Returns a default user (template)
   * @returns LdapPerson        a default LdapPerson
   */
  public static function getDefault()
  {
    $default = array(
      'objectclass' => array(
        'top',
        'person',
        'organizationalPerson',
        'iNetOrgPerson',
        'gosaAccount',
        'posixAccount',
        'shadowAccount',
        'sambaSamAccount',
        'sambaIdmapEntry',
#        'pptpServerAccount',
        'gosaMailAccount',
#        'gosaIntranetAccount',
      ),
      'gosamaildeliverymode' => '[L]',
      'gosamailserver' => 'mail',
      'gosaspammailbox' => 'INBOX',
      'gosaspamsortlevel' => '0',
      'gotolastsystemlogin' => '01.01.1970 00:00:00',
      'loginshell' => '/bin/bash',
      'sambaacctflags' => '[U           ]',
      'sambadomainname' => 'nieuwedelft',
      'sambahomedrive' => 'Z:',
      'sambahomepath' => '\\\samba\commissies',
      'sambalogofftime' => '2147483647',
      'sambalogontime' => '0',
      'sambapwdlastset' => '0',
      'sambamungeddial' => 'IAAgACAAIAAgACAAIAAgACAAIAAgACAAIAAgACAAIAAgACAAIAAgACAAIAAgACAAIAAgACAAIAAgACAAIAAgACAAIAAgACAAIAAgACAAIAAgACAAIAAgACAAIAAgACAAUAAQABoACAABAEMAdAB4AEMAZgBnAFAAcgBlAHMAZQBuAHQANTUxZTBiYjAYAAgAAQBDAHQAeABDAGYAZwBGAGwAYQBnAHMAMQAwMDAwMDEwMBYAAAABAEMAdAB4AEMAYQBsAGwAYgBhAGMAawASAAgAAQBDAHQAeABTAGgAYQBkAG8AdwAwMTAwMDAwMCIAAAABAEMAdAB4AEsAZQB5AGIAbwBhAHIAZABMAGEAeQBvAHUAdAAqAAIAAQBDAHQAeABNAGkAbgBFAG4AYwByAHkAcAB0AGkAbwBuAEwAZQB2AGUAbAAwMCAAAgABAEMAdAB4AFcAbwByAGsARABpAHIAZQBjAHQAbwByAHkAMDAgAAIAAQBDAHQAeABOAFcATABvAGcAbwBuAFMAZQByAHYAZQByADAwGAACAAEAQwB0AHgAVwBGAEgAbwBtAGUARABpAHIAMDAiAAIAAQBDAHQAeABXAEYASABvAG0AZQBEAGkAcgBEAHIAaQB2AGUAMDAgAAIAAQBDAHQAeABXAEYAUAByAG8AZgBpAGwAZQBQAGEAdABoADAwIgACAAEAQwB0AHgASQBuAGkAdABpAGEAbABQAHIAbwBnAHIAYQBtADAwIgACAAEAQwB0AHgAQwBhAGwAbABiAGEAYwBrAE4AdQBtAGIAZQByADAwKAAIAAEAQwB0AHgATQBhAHgAQwBvAG4AbgBlAGMAdABpAG8AbgBUAGkAbQBlADAwMDAwMDAwLgAIAAEAQwB0AHgATQBhAHgARABpAHMAYwBvAG4AbgBlAGMAdABpAG8AbgBUAGkAbQBlADAwMDAwMDAwHAAIAAEAQwB0AHgATQBhAHgASQBkAGwAZQBUAGkAbQBlADAwMDAwMDAw',
    );
    
    $result = new self($default);
    return $result;
  }

  /**
   * Gets a property of a LdapPerson
   * @param  string $name the property to read
   * @return mixed        the value of the property
   */
  public function __get($name)
  {
    return parent::__get($name);
  }

  /**
   * Saves the current LdapPerson to ldap, creates a new entry if needed
   * This notifies the user if their password is reset
   */
  public function save()
  {
    if(!$this->exists)
      $this->__set('uidnumber', self::findUidnumber());

    //Send mail if password changes
    if(isset($this->dirty['userpassword']))
    {
      $mail = new \Mailer\NewPerson($this->attributes['mail'], $this->attributes['uid'], $this->attributes['cn'], $this->attributes['userpassword']);
      $mail->send();
    }
    
		return parent::save();
  }

  /**
   * Sets a property of a LdapPerson
   * @param string $name  the property to set
   * @param mixed $value  the value to set
   */
  public function __set($name, $value)
  {
    parent::__set($name, $value);

    // Perform calculations if neccesary
    if(array_key_exists($name, $this->calculations))
      $this->calculations[$name]();
  }
}
