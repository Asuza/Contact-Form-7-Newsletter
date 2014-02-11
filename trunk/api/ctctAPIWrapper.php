<?php

use Ctct\Services\ListService;
use Ctct\Services\ContactService;

use Ctct\Components\Contacts\Address;
use Ctct\Components\Contacts\Contact;
use Ctct\Components\Contacts\ContactList;
use Ctct\Components\Contacts\CustomField;
use Ctct\Components\Contacts\EmailAddress;
use Ctct\Components\Contacts\Note;

class CTCT_API_Wrapper
{
  private $apiKey;
  private $apiPath;

  private $accessToken;

  public function __construct()
  {
    $this->apiKey = 'w32repp6td33pyq86kysa8nd';
    $this->apiPath = $this->getCurrentUri();

    $this->accessToken = 'e4960684-acc1-4cc4-9245-81c7039a393c';
  }

  public function getApiKey ()
  {
    return $this->apiKey;
  }

  public function getApiPath ()
  {
    return $this->apiPath;
  }

  public function getAccessToken ()
  {
    return $this->accessToken;
  }

  public function getCurrentUri ()
  {
    return 'http://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
  }

  public function getAccessTokenUri ()
  {
    return 'https://api.constantcontact.com/mashery/account/' . $this->getApiKey();
  }

  public function getLists ()
  {
    $Lists = new ListService($this->getApiKey());

    return $Lists->getLists($this->getAccessToken());
  }

  public function getContactByEmail ($email)
  {
    $Contacts = new ContactService($this->getApiKey());

    $response = $Contacts->getContacts($this->getAccessToken(), array(
      'email' => $email
    ));

    if (!empty($response->results)) {
      return $response->results[0];
    }

    return false;
  }

  public function createContact ($data)
  {
    $Contact = new Contact();

    return $Contact->create($data);
  }

  public function addContact ($contact, $options)
  {
    $Contacts = new ContactService($this->getApiKey());

    return $Contacts->addContact($this->getAccessToken() , $contact, $options);
  }

  public function updateContact ($contact, $options)
  {
    $Contacts = new ContactService($this->getApiKey());

    return $Contacts->updateContact($this->getAccessToken() , $contact, $options);
  }

  static public function setIfEmpty (&$var, $defaultValue) {
    if (empty($var)) {
      $var = $defaultValue;
    }
  }

  public function updateContactDetails (&$Contact, $contactData)
  {
    if (!$Contact || !$contactData || !count($contactData)) {
      return false;
    }

    self::setIfEmpty($Contact->first_name, $contactData['first_name']);
    self::setIfEmpty($Contact->middle_name, $contactData['middle_name']);
    self::setIfEmpty($Contact->last_name, $contactData['last_name']);
    self::setIfEmpty($Contact->prefix_name, $contactData['prefix_name']);
    self::setIfEmpty($Contact->job_title, $contactData['job_title']);
    self::setIfEmpty($Contact->company_name, $contactData['company_name']);
    self::setIfEmpty($Contact->home_phone, $contactData['home_phone']);
    self::setIfEmpty($Contact->work_phone, $contactData['work_phone']);
    self::setIfEmpty($Contact->cell_phone, $contactData['cell_phone']);
    self::setIfEmpty($Contact->fax, $contactData['fax']);

    $Address = new Address();
    $newAddress = $Address->create(array(
      'line1' => $contactData['address_line_1'],
      'line2' => $contactData['address_line_2'],
      'line3' => $contactData['address_line_3'],
      'city' => $contactData['city'],
      'address_type' => "UNKNOWN",
      'state_code' => $contactData['state_code'],
      'country_code' => $contactData['country_code'],
      'postal_code' => $contactData['postal_code'],
      'sub_postal_code' => $contactData['sub_postal_code'],
    ));

    $CustomField = new CustomField();
    $cfPrefix = 'CustomField';

    foreach ($contactData as $key => $value) {
      if (stripos($key, $cfPrefix) !== false) {
        $cf = $CustomField->create(array(
          'name' => $key,
          'value' => $value
        ));

        $Contact->addCustomField($cf);
      }
    }

    $Contact->addAddress($newAddress);

    return true;
  }

  public function listMergeVars() {
    return array(
      array('tag'=>'email_address', 'req' => true, 'name' => "Email Address", 'placeholder' => '[your-email]'),
      array('tag'=>'full_name', 'req' => false, 'name' => "Full Name"),
      array('tag'=>'first_name', 'req' => false, 'name' => "First Name"),
      array('tag'=>'middle_name', 'req' => false, 'name' => "Middle Name"),
      array('tag'=>'last_name', 'req' => false, 'name' => "Last Name"),
      array('tag'=>'job_title', 'req' => false, 'name' => "Job Title"),
      array('tag'=>'company_name', 'req' => false, 'name' => "Company Name"),
      array('tag'=>'home_phone', 'req' => false, 'name' => "Home Phone"),
      array('tag'=>'work_phone', 'req' => false, 'name' => "Work Phone"),
      array('tag'=>'cell_phone', 'req' => false, 'name' => "Cell Phone"),
      array('tag'=>'fax', 'req' => false, 'name' => "Fax"),
      array('tag'=>'address_line_1','req' => false, 'name' => "Address 1"),
      array('tag'=>'address_line_2','req' => false, 'name' => "Address 2"),
      array('tag'=>'address_line_3','req' => false, 'name' => "Address 3"),
      array('tag'=>'city', 'req' => false, 'name' => "City"),
      array('tag'=>'state_code',  'req' => false, 'name' => "State Code"),
      array('tag'=>'country_code', 'req' => false, 'name' => "Country Code"),
      array('tag'=>'postal_code',  'req' => false, 'name' => "Postal Code"),
      array('tag'=>'sub_postal_code', 'req' => false, 'name' => "Sub Postal Code"),
      array('tag'=>'notes', 'req' => false, 'name' => "Note"),
      array('tag'=>'CustomField1', 'req' => false, 'name' => "Custom Field 1"),
      array('tag'=>'CustomField2', 'req' => false, 'name' => "Custom Field 2"),
      array('tag'=>'CustomField3', 'req' => false, 'name' => "Custom Field 3"),
      array('tag'=>'CustomField4', 'req' => false, 'name' => "Custom Field 4"),
      array('tag'=>'CustomField5', 'req' => false, 'name' => "Custom Field 5"),
      array('tag'=>'CustomField6', 'req' => false, 'name' => "Custom Field 6"),
      array('tag'=>'CustomField7', 'req' => false, 'name' => "Custom Field 7"),
      array('tag'=>'CustomField8', 'req' => false, 'name' => "Custom Field 8"),
      array('tag'=>'CustomField9', 'req' => false, 'name' => "Custom Field 9"),
      array('tag'=>'CustomField10', 'req' => false, 'name' => "Custom Field 10"),
      array('tag'=>'CustomField11', 'req' => false, 'name' => "Custom Field 11"),
      array('tag'=>'CustomField12', 'req' => false, 'name' => "Custom Field 12"),
      array('tag'=>'CustomField13', 'req' => false, 'name' => "Custom Field 13"),
      array('tag'=>'CustomField14', 'req' => false, 'name' => "Custom Field 14"),
      array('tag'=>'CustomField15', 'req' => false, 'name' => "Custom Field 15"),
    );
  }
}