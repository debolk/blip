<?php
namespace Helper;

use LDAP\Connection;

class LdapHelper
{
    /**
     * Stores the singleton instance of this class
     * @var LdapHelper|null
     */
    private static LdapHelper|null $instance = null;

    /**
     * Return a singleton connection to the LDAP-server
     */
    public static function Connect() : LdapHelper
    {
        if (self::$instance == null) {
            syslog(LOG_ERR, "Ldap connection not initialised");
        }

        return self::$instance;
    }

	public static function Initialise($ldap_host, $ldap_base, $ldap_username, $ldap_password) {
		self::$instance = new LdapHelper($ldap_host, $ldap_base, $ldap_username, $ldap_password);
	}

    /**
     * Connection to the LDAP-server
     * @var Connection
     */
    protected Connection $ldap;

    /**
     * Base DN of all LDAP-entries
     * @var string
     */
    public $basedn;

    /**
     * Connect to the LDAP-server and set basic configuration
     */
    public function __construct($ldap_host, $ldap_base, $ldap_username, $ldap_password)
    {
        $this->ldap = ldap_connect($ldap_host);

		ldap_set_option($this->ldap, LDAP_OPT_PROTOCOL_VERSION, 3); //sets ldap protocol to v3; server won't accept otherwise.
		$this->bind($ldap_username, $ldap_password);
        $this->basedn = $ldap_base;
    }

	public function getBaseDn() {
		return $this->basedn;
	}

    /**
     * Escape arguments for safe searches in LDAP
     * @param  string $argument argument to escape
     * @return string           escaped string
     */
    public function escapeArgument(string $argument) : string
    {
        $sanitized=array('\\' => '\5c',
            '*' => '\2a',
            '(' => '\28',
            ')' => '\29',
            "\x00" => '\00');
        return str_replace(array_keys($sanitized), array_values($sanitized), $argument);
    }

    /**
     * Returns the last thrown error
     * @return string						the last error returned from ldap
     */
    public function lastError() : string
    {
        return ldap_error($this->ldap);
    }

    /**
     * Returns the full DN for a LDAP-entry
     * @param  string $uid the UID to find
     * @return string      full DN
     */
    public function getDn(string $uid) : string
    {
        $uid = $this->escapeArgument($uid);
        $users = ldap_search($this->ldap, $this->basedn, '(uid=' . $uid . ')', array('dn'));

        if (!$users || ldap_count_entries($this->ldap, $users) == 0) {
            return false;
        }

        $users = ldap_get_entries($this->ldap, $users);

        return $users[0]['dn'];
    }

    /**
     * Binds to the LDAP-server
     * @param  string $dn   the full DN of the user to bind to
     * @param  string $pass password
     * @return boolean      whether the bind succeeded
     */
    public function bind(string $dn, string $pass) : bool
    {
        if (!$dn || empty($pass)) {
            return false;
        }
        return ldap_bind($this->ldap, $dn, $pass);
    }

    /**
     * Searches an LDAP-entry
     * @param string $filter the filter to use
     * @param array|null $attributes attributes to include in the result set
     * @param string|null $basedn the DN to search
     * @return array              a filtered array of results (without counts)
     */
    public function search(string $filter, array $attributes = null, string $basedn = null) : array
    {
        if ($basedn == null) {
            $basedn = $this->basedn;
        }

        if ($attributes == null) {
            $query = ldap_search($this->ldap, $basedn, $filter);
        } else {
            $query = ldap_search($this->ldap, $basedn, $filter, $attributes);
        }

        if (!$query) {
            return array();
        }

        $results = ldap_get_entries($this->ldap, $query);
        return $this->stripCounts($results);
    }

    /**
     * Gets an entry from LDAP
     * @param string $dn DN of the entry
     * @param string|null $objectClass optional objectClass filter, default *
     * @param array|null $attributes array of attributes to return
     * @return array|bool filtered array of desired attributes, or false if the entry was not found
     */
    public function get(string $dn, string $objectClass = null, array $attributes = null) : array|bool
    {
        $objectClass = $this->escapeArgument($objectClass);
        if ($objectClass == null) {
            $objectClass = '*';
        }
        $query = '(objectClass=' . $objectClass . ')';

        if ($attributes == null) {
            $read = ldap_read($this->ldap, $dn, '(objectClass=' . $objectClass . ')');
        } else {
            $read = ldap_read($this->ldap, $dn, '(objectClass=' . $objectClass . ')', $attributes);
        }

        if (!$query || ldap_count_entries($this->ldap, $read) < 1) {
            return false;
        }

        $results = ldap_get_entries($this->ldap, $read);
        $result = $results[0];

        return $this->stripCounts($result);
    }

    /**
     * Adds an object to ldap
     * @param string $dn          DN of the entry
     * @param array  $data        the data the object should contain
     * @return bool               TRUE on success or FALSE on failure
     */
    public function add(string $dn, array $data) : bool
    {
        // Remove unset parameters
        foreach ($data as $key => $value) {
            if (is_array($value) && count($value) == 0) {
                unset($data[$key]);
            }
        }

        return @ldap_add($this->ldap, $dn, $data);
    }

    /**
     * Modifies an object in ldap
     * @param string $dn          DN of the entry
     * @param array  $data        the changed data the object should contain
     * @return bool               TRUE on success or FALSE on failure
     */
    public function modify(string $dn, array $data) : bool
    {
        return @ldap_modify($this->ldap, $dn, $data);
    }

    /**
     * Recursively strip all unneeded 'count' parameters from a LDAP-result
     * @param  array $array array to filter
     * @return array        filtered array
     */
    protected function stripCounts(array $array) : array
    {
        $result = array();
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $result[$key] = $this->stripCounts($value);
            } elseif ($key !== 'count') {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * Flatten a LDAP-object
     * @param  object $ldap_object object to flatten
     * @return object              flattened object
     */
    public function flatten(object $ldap_object) : object
    {
        $result = array();
        foreach ($ldap_object as $key => $value) {
            if (!is_int($key)) {
                if (is_array($value) && count($value) == 1) {
                    $result[$key] = $value[0];
                } else {
                    $result[$key] = $value;
                }
            }
        }

        return $result;
    }
}
