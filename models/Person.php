<?php

namespace Models;

class Person implements \JSONSerializable
{
  public $uid;
  public $first_name;
  public $last_name;
  public $email;

  public function name()
  {
    return implode(' ', array_filter(array($this->first_name, $this->last_name)));
  }

  public function jsonSerialize()
  {
    return array(
      'uid' => $this->uid,
      'href' => getenv('BASE_URL').'persons/'.$this->uid,
      'name' => $this->name(),
      'email' => $this->email,
    );
  }
}