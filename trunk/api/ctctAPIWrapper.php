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

  public function __construct($token)
  {
    $this->apiKey = 'g4jqyr3swnt6m4nnxqqme7bv';
    $this->apiPath = $this->getCurrentUri();

    $this->accessToken = $token;
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

  static public function setIfEmpty (&$object, $hash, $key) {
    if (empty($object->$key) && isset($hash[$key])) {
      $object->$key = $hash[$key];
    }
  }

  public function updateContactDetails (&$Contact, $contactData)
  {
    if (!$Contact || !$contactData || !count($contactData)) {
      return false;
    }

    self::setIfEmpty($Contact, $contactData, 'first_name');
    self::setIfEmpty($Contact, $contactData, 'middle_name');
    self::setIfEmpty($Contact, $contactData, 'last_name');
    self::setIfEmpty($Contact, $contactData, 'prefix_name');
    self::setIfEmpty($Contact, $contactData, 'job_title');
    self::setIfEmpty($Contact, $contactData, 'company_name');
    self::setIfEmpty($Contact, $contactData, 'home_phone');
    self::setIfEmpty($Contact, $contactData, 'work_phone');
    self::setIfEmpty($Contact, $contactData, 'cell_phone');
    self::setIfEmpty($Contact, $contactData, 'fax');

    $addressLine1 = isset($contactData['address_line_1']) ? $contactData['address_line_1'] : "";
    $addressLine2 = isset($contactData['address_line_2']) ? $contactData['address_line_2'] : "";
    $addressLine3 = isset($contactData['address_line_3']) ? $contactData['address_line_3'] : "";

    $city = isset($contactData['city']) ? $contactData['city'] : "";
    $stateCode = isset($contactData['state_code']) ? $contactData['state_code'] : "";
    $countryCode = isset($contactData['country_code']) ? $contactData['country_code'] : "";
    $postalCode = isset($contactData['postal_code']) ? $contactData['postal_code'] : "";
    $subPostalCode = isset($contactData['sub_postal_code']) ? $contactData['sub_postal_code'] : "";


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

    if ($addressLine1 || $city || $countryCode || $postalCode) {
      $Address = new Address();
      
      $newAddress = $Address->create(array(
        'line1' => $addressLine1,
        'line2' => $addressLine2,
        'line3' => $addressLine3,
        'city' => $city,
        'address_type' => "UNKNOWN",
        'state_code' => $stateCode,
        'country_code' => $countryCode,
        'postal_code' => $postalCode,
        'sub_postal_code' => $subPostalCode,
      ));

      $Contact->addAddress($newAddress);
    }

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