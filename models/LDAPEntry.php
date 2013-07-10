<?php

namespace Models;

class LDAPEntry
{
  private $attributes = array();

  /**
   * Converts a LDAP-resultset to a LDAPEntry-instance
   * @class  array $entry
   * @return Models\LDAPEntry
   */
  public static function from_result($entry)
  {
    $keys = ['uid', 'givenname', 'sn', 'mail', 'telephonenumber', 'mobile', 'homephone', 'homepostaladdress'];
    foreach ($keys as $key) {
      if (isset($entry[$key][0])) {
        $parameters[$key] = $entry[$key][0];
      }
    }

    // Format parameters
    return new LDAPEntry($parameters);
  }

  /**
   * Constructs a new LDAP-entry
   */
  public function __construct($attributes)
  {
    $this->attributes = $attributes;
  }

  /**
   * Converts this LDAP-entry to a Person-model
   * @return Models\Person
   */
  public function to_Person()
  {
    // Create correct properties
    $input = [];

    if (isset($this->attributes['uid'])) {
      $input['uid'] = $this->attributes['uid'];
    }
    if (isset($this->attributes['givenname'])) {
      $input['firstname'] = $this->attributes['givenname'];
    }
    if (isset($this->attributes['sn'])) {
      $input['lastname'] = $this->attributes['sn'];
    }
    if (isset($this->attributes['mail'])) {
      $input['email'] = $this->attributes['mail'];
    }
    if (isset($this->attributes['telephonenumber'])) {
      $input['phone'] = $this->attributes['telephonenumber'];
    }
    if (isset($this->attributes['mobile'])) {
      $input['mobile'] = $this->attributes['mobile'];
    }
    if (isset($this->attributes['homephone'])) {
      $input['phone_parents'] = $this->attributes['homephone'];
    }
    if (isset($this->attributes['homepostaladdress'])) {
      $input['address'] = $this->attributes['homepostaladdress'];
    }
    if (isset($this->attributes['initials'])) {
      $input['initials'] = $this->attributes['initials'];
    }

    // Create and return model
    return new Person($input);
  }

  /**
   * Cast to array
   * @return array
   */
  public function to_array()
  {
    return $this->attributes;
  }
}

// /**
  //  * Create a new member with the given data
  //  * @param array $data containing the keys name, email and status. Status can be any string of: lid, kandidaat-lid, oud-lid or ex-lid.
  //  * @return the results of LDAP::find() of the new member
  //  */
  // public function create($data)
  // {
  //   // Find a free UID
  //   $uid = (string)$this->find_free_uid($data);

  //   // Calculate UID-number
  //   $uid_number = (string)$this->get_new_uid_number();


  //   $name = implode(' ', array_filter([$data->firstname, $data->lastname]));

  //   // Build complete input array
  //   $input = [
  //     'uid' => $uid,
  //     'cn' => $name,
  //     'gecos' => $name,
  //     'givenname' => $data->firstname,
  //     'sn' => $data->lastname,
  //     'mail' => $data->email,
  //     'objectClass' => ['top', 'person', 'organizationalPerson', 'iNetOrgPerson','gosaAccount','posixAccount','shadowAccount','sambaSamAccount','sambaIdmapEntry','pptpServerAccount','gosaMailAccount','gosaIntranetAccount'],
  //     'gosamaildeliverymode' => '[L]',
  //     'gosamailserver' => 'mail',
  //     'gosaspammailbox' => 'INBOX',
  //     'gosaspamsortlevel' => '0',
  //     'gotolastsystemlogin' => '01.01.1970 00:00:00',
  //     'loginshell' => '/bin/bash',
  //     'sambaacctflags' => '[U           ]',
  //     'sambadomainname' => 'nieuwedelft',
  //     'sambahomedrive' => 'Z:',
  //     'sambahomepath' => '\\\samba\commissies',
  //     'sambalogofftime' => '2147483647',
  //     'sambalogontime' => '0',
  //     'sambapwdlastset' => '0',
  //     'sambamungeddial' => 'IAAgACAAIAAgACAAIAAgACAAIAAgACAAIAAgACAAIAAgACAAIAAgACAAIAAgACAAIAAgACAAIAAgACAAIAAgACAAIAAgACAAIAAgACAAIAAgACAAIAAgACAAIAAgACAAUAAQABoACAABAEMAdAB4AEMAZgBnAFAAcgBlAHMAZQBuAHQANTUxZTBiYjAYAAgAAQBDAHQAeABDAGYAZwBGAGwAYQBnAHMAMQAwMDAwMDEwMBYAAAABAEMAdAB4AEMAYQBsAGwAYgBhAGMAawASAAgAAQBDAHQAeABTAGgAYQBkAG8AdwAwMTAwMDAwMCIAAAABAEMAdAB4AEsAZQB5AGIAbwBhAHIAZABMAGEAeQBvAHUAdAAqAAIAAQBDAHQAeABNAGkAbgBFAG4AYwByAHkAcAB0AGkAbwBuAEwAZQB2AGUAbAAwMCAAAgABAEMAdAB4AFcAbwByAGsARABpAHIAZQBjAHQAbwByAHkAMDAgAAIAAQBDAHQAeABOAFcATABvAGcAbwBuAFMAZQByAHYAZQByADAwGAACAAEAQwB0AHgAVwBGAEgAbwBtAGUARABpAHIAMDAiAAIAAQBDAHQAeABXAEYASABvAG0AZQBEAGkAcgBEAHIAaQB2AGUAMDAgAAIAAQBDAHQAeABXAEYAUAByAG8AZgBpAGwAZQBQAGEAdABoADAwIgACAAEAQwB0AHgASQBuAGkAdABpAGEAbABQAHIAbwBnAHIAYQBtADAwIgACAAEAQwB0AHgAQwBhAGwAbABiAGEAYwBrAE4AdQBtAGIAZQByADAwKAAIAAEAQwB0AHgATQBhAHgAQwBvAG4AbgBlAGMAdABpAG8AbgBUAGkAbQBlADAwMDAwMDAwLgAIAAEAQwB0AHgATQBhAHgARABpAHMAYwBvAG4AbgBlAGMAdABpAG8AbgBUAGkAbQBlADAwMDAwMDAwHAAIAAEAQwB0AHgATQBhAHgASQBkAGwAZQBUAGkAbQBlADAwMDAwMDAw',
  //     'userpassword' => uniqid(),
  //     'homedirectory' => "/home/$uid",
  //     'uidnumber' => $uid_number,
  //     'sambasid' => 'S-1-5-21-1816619821-1419577557-1603852640-'.(1000+2*$uid_number),
  //     'gidNumber' => '1084',
  //     'sambaprimarygroupsid' => 'S-1-5-21-1816619821-1419577557-1603852640-3051',
  //     'gender' => strtoupper($data->gender),
  //   ];

  //   // Add optional attributes
  //   if (! empty($data->phone)) {
  //     $input['telephonenumber'] = $data->phone;
  //   }
  //   if (! empty($data->mobile)) {
  //     $input['mobile'] = $data->mobile;
  //   }
  //   if (! empty($data->phone_parents)) {
  //     $input['homephone'] = $data->phone_parents;
  //   }
  //   if (! empty($data->address)) {
  //     $input['homepostaladdress'] = $data->address;
  //   }
  //   if (! empty($data->dateofbirth)) {
  //     $input['dateOfBirth'] = date('Y-m-d', strtotime($data->dateofbirth));
  //   }

  //   // Create LDAP-entry
  //   $success = ldap_add($this->server, "uid=$uid,ou=people,o=nieuwedelft,dc=bolkhuis,dc=nl", $input);

  //   // Return the new user
  //   return $this->find($uid);
  // }