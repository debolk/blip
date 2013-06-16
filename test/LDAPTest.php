<?php 

require_once('LDAP.php');

class LDAPTest extends PHPUnit_Framework_TestCase
{
  function setUp()
  {
    // Stub the LDAP-server
    $this->stub = $this->getMock('Zend\Ldap\Ldap');

    // Get a handle to our class under test
    $this->ldap = new LDAP($this->stub);
  }

  function test_it_can_be_instantiated()
  {
    $this->assertInstanceOf('LDAP', $this->ldap);
  }

  function test_it_finds_all_users()
  {
    $this->markTestIncomplete('This test has not been implemented yet.');
  }

  function test_it_finds_a_specific_user()
  {
    $this->markTestIncomplete('This test has not been implemented yet.');
  }

  function test_it_does_not_find_a_nonexisting_user()
  {
    $this->markTestIncomplete('This test has not been implemented yet.');
  }

  function test_it_creates_a_new_valid_user()
  {
    $this->markTestIncomplete('This test has not been implemented yet.');
  }

  /**
   * @expectedException LDAPInvalidUserException
   */
  function test_it_does_not_create_an_invalid_user()
  {
    $this->ldap->create(array());
  }

  function test_it_updates_a_user()
  {
    $this->markTestIncomplete('This test has not been implemented yet.');
  }

  function test_it_does_not_store_an_invalid_update()
  {
    $this->markTestIncomplete('This test has not been implemented yet.');
  }
}
