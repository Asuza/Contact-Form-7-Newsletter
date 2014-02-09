<?php

use Ctct\Services\ListService;
use Ctct\Services\ContactService;

use Ctct\Components\Contacts\Contact;
use Ctct\Components\Contacts\ContactList;
use Ctct\Components\Contacts\EmailAddress;

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
}