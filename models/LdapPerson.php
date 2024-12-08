<?php

namespace Models;

use Helper\LdapHelper;

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
              if (!isset($this->dn)) {
                  $this->dn = 'uid=' . $this->uid . ',ou=people,o=nieuwedelft,' . LdapHelper::Connect()->basedn; /** NOTE: THIS IS MEANT TO BE A PLACEHOLDER GROUP */
              }
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
        $search = $ldap->search('(objectClass=posixAccount)', array('uidNumber'));

        // Slap array until it's formatted
        $numbers = array_map(function ($e) {
            return (int)$e['uidnumber'][0];
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
            $parts[] = $this->givenname;
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

        $dn = $ldap->getDn($uid);
        if (!$dn) {
            return false;
        }

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
            'gosaAccount',
            'posixAccount',
            'shadowAccount',
            'gosaMailAccount',
            'fdBolkData',
            'fdBolkDataAVG'
        ),
          'gosamaildeliverymode' => '[L]',
          'gosamailserver' => 'mail',
          'gosaspammailbox' => 'INBOX',
          'gosaspamsortlevel' => '0',
      );

        $result = new self($default);
        return $result;
    }

    /**
     * Gets a property of a LdapPerson
     * @param  string $name the property to read
     * @return mixed        the value of the property
     */
    public function __get(string $name) : mixed
    {
        return parent::__get($name);
    }

    /**
     * Saves the current LdapPerson to ldap, creates a new entry if needed
     * This notifies the user if their password is reset
     */
    public function save() : bool
    {
        if (!$this->exists) {
            $this->__set('uidnumber', self::findUidnumber());
        }

        //Send mail if password changes
        if (isset($this->dirty['userpassword'])) {
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
    public function __set(string $name, mixed $value)
    {
        parent::__set($name, $value);

        // Perform calculations if neccesary
        if (array_key_exists($name, $this->calculations)) {
            $this->calculations[$name]();
        }
    }
}
