<?php

namespace Models;

use Helper\LdapHelper;
use Mailer\NewPerson;

/**
 * Handles creation of new users in Ldap
 */
class LdapPerson extends LdapObject
{
    private $calculations;

    /**
     * Creates a new LdapEntry
     * @param array $attributes  The attributes to set
     */
    public function __construct(array $attributes = array())
    {
        parent::__construct($attributes);
        $this->calculations = array(
          'givenname' => function () {
              $this->setName();
          },

          'sn' => function () {
              $this->setName();
          },

          'uid' => function () {
	          $group = $attributes['membership'] ?? 'external';

              if (!isset($this->dn)) {
				  $ldap = LdapHelper::Connect();
                  $this->dn = 'uid=' . $this->uid . ',' . PersonModel::$personOUnits[$group] . ',' . $ldap->getBaseDn();
              }
			  if (!isset($this->gidnumber)) {
				  $this->gidnumber = PersonModel::$groupIds[$group];
			  }
			  $this->homedirectory = "/home/" . $this->uid;
          },
        );
    }

    /**
     * Finds an unused uidnumber in ldap
     * @returns int         an unused uidnumber
     */
    protected static function findUidnumber() : int
    {
        $ldap = \Helper\LdapHelper::connect();

        // Find all existing entries with a uidNumber
        $search = $ldap->search('(objectClass=posixAccount)', array('uidnumber'));

        // Slap array until it's formatted
        $numbers = array_map(function ($e) {
			if (!isset($e)) return null;
            return (int)$e;
        }, $search);

        $max = 1000;
        foreach ($numbers as $number) {
            if ($number > $max && $number < 65000) {
                $max = $number;
            }
        }

        return $max + 1;
    }

    /**
     * Calculates the name of this person using first and last name
     * @return string     the name of the person
     */
    protected function setName() : string
    {
        $parts = array();

        if (isset($this->attributes['givenName'])) {
            $parts[] = $this->givenName;
        }
        if (isset($this->attributes['sn'])) {
            $parts[] = $this->sn;
        }

        $name = implode(" ", $parts);

        setlocale(LC_ALL, 'en_US.UTF8');
        $gecos = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name);

        $this->__set('gecos', $gecos);
        $this->__set('cn', $gecos);
        return $gecos;
    }

    /**
     * Creates an LdapPerson from an uid
     * @param string $uid the uid to look up
     * @return LdapPerson|bool the specified entry under the dn, false id the dn is invalid
     */
    public static function fromUid(string $uid) : LdapPerson|bool
    {
        $ldap = LdapHelper::Connect();

        $dn = $ldap->getUserDn($uid);
        if (!$dn) {
            return false;
        }

        return self::fromDn($dn);
    }

	public static function fromDn(string $dn): LdapPerson|null {
		$ldap = LdapHelper::Connect();
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
    public static function getDefault() : LdapPerson
    {
        $default = array(
          'objectclass' => array(
            'top',
            'person',
            'organizationalPerson',
            'iNetOrgPerson',
            'posixAccount',
            'shadowAccount',
            'gosaMailAccount',
            'fdBolkData',
            'fdBolkDataAVG'
        ),
          'gosamailserver' => 'mail',
      );

        $result = new self($default);
        return $result;
    }

    /**
     * Saves the current LdapPerson to ldap, creates a new entry if needed
     */
    public function save() : bool
    {
        if (!$this->exists) {
            $this->__set('uidnumber', self::findUidnumber());
        }

		if (!isset($this->mail)) {
			$this->mail = 'invalid@nieuwedelft.nl.';
		}

        return parent::save();
    }

	public function send_login($pass) {
		$mail = new NewPerson($this->mail, $this->uid, $this->cn, $pass);
		if (!$mail->send()){
			syslog(LOG_ERR, "Unable to send login mail: " . $mail->getError());
		} else{
			syslog(LOG_DEBUG, "Successfully sent login.");
		}
	}

    /**
     * Sets a property of a LdapPerson
     * @param string $name  the property to set
     * @param mixed $value  the value to set
     */
    public function __set(string $name, mixed $value)
    {
        parent::__set($name, $value);
        // Perform calculations if neccesary
        if (array_key_exists($name, $this->calculations)) {
            $this->calculations[$name]();
        }
    }

	/**
	 * Remove the LdapPerson from the directory
	 * @return bool TRUE on success, FALSE if otherwise
	 */
	public function delete(): bool {
		$ldap = LdapHelper::Connect();
		if (isset($this->attributes['dn'])) return $ldap->delete($this->dn);
		return false;
	}

}
