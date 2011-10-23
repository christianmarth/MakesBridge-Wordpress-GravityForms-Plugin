<?php

class mksapi {

<<<<<<< HEAD
    var $url = 'https://api.bridgemailsystem.com/pms/services/'; //MakesBridge API URL
    
    var $apiKey; //MakesBridge API Access Token
    
    var $userId; //MakesBridge API Username
    
    var $authToken; //Authentication Token
=======
    //MakesBridge API URL
    var $url = 'https://api.bridgemailsystem.com/pms/services/';
    //MakesBridge API Access Token
    var $apiKey;
    //MakesBridge API Username
    var $userId;
    //Authentication Token
    var $authToken;
>>>>>>> first commit

    function mksapi($userId, $apiKey) {
        $this->apiKey = $apiKey;
        $this->userId = $userId;
    }

    /*
     * Login to MakesBridge
     *
     * @param string $apiKey MakesBridge API Access Token
     * @param string $userId MakesBridge UserId
     *
     */

    function login() {
        $content = "<?xml version='1.0' ?>
		 <login>
		 <userId>$this->userId</userId>
		 <api_tk>$this->apiKey</api_tk>
		 </login>";
        $headers = array(
            'Content-type' => 'text/xml'
        );
        $result = wp_remote_post($this->url . 'login/', array(
            'headers' => $headers,
            'sslverify' => false,
            'body' => $content,
                ));
        $authTk = wp_remote_retrieve_header($result, 'auth_tk');
        $this->authToken = $authTk;
        return($authTk);
    }

    //Returns true if settings are correct or false if incorrect
    function testSettings() {
        $response = $this->login();
        return(($response) ? true : false);
    }

    function retrieveLists() {
        $headers = array(
            'Content-type' => 'text/xml',
            'userId' => $this->userId,
            'auth_tk' => $this->authToken
        );
        $response = wp_remote_get($this->url . 'getlistinfo/', array(
            'headers' => $headers,
            'sslverify' => false
                ));
        $data = wp_remote_retrieve_body($response);
        $data = simplexml_load_string($data, 'SimpleXMLElement', LIBXML_NOCDATA);
        return($data);
    }

<<<<<<< HEAD
    //Create Subscriber Function
    function createSubscriber($data, $list) {
=======
    function createSubscriber($data) {
>>>>>>> first commit
        $headers = array(
            'Content-type' => 'text/xml',
            'userId' => $this->userId,
            'auth_tk' => $this->authToken
        );
<<<<<<< HEAD
        $xmlstr = "<?xml version='1.0' encoding='utf-8'?>
	    <addsubscriber></addsubscriber>";
        $xml = new SimpleXMLElement($xmlstr);
        $xml->addAttribute('listName', $list);
        $subscriber = $xml->addChild('subscriber');

        //StandardFields
        foreach ($data['standard'] as $key => $value) {
            $subscriber->$key = $data['standard'][$key];
        };

        //CustomFields
        $customField = $subscriber->addChild('customFields');

        if (is_array($data['custom'])) {
            foreach ($data['custom'] as $customKey => $customValue) {
                $customFields = $customField->addChild('customField');
                $customFields->addAttribute('name', $customKey);
                $customFields->addAttribute('value', $customValue);
            }
        }

=======
        $user = get_userdata($user_id);
        $xmlstr = "<?xml version='1.0' encoding='utf-8'?>
	    <addsubscriber></addsubscriber>";
        $xml = new SimpleXMLElement($xmlstr);
        $xml->addAttribute('listName', 'Clients');
        $subscriber = $xml->addChild('subscriber');
        $subscriber->email = $data['email'];
        $subscriber->firstName = $data['name'];
>>>>>>> first commit
        $response = wp_remote_post($this->url . 'addsubscriber/', array(
            'headers' => $headers,
            'sslverify' => false,
            'body' => $xml->asXML()
                ));
<<<<<<< HEAD

        $data = wp_remote_retrieve_body($response);
        $data = simplexml_load_string($data, 'SimpleXMLElement', LIBXML_NOCDATA);

        return $response;
    }

    //Custom Fields Function
    function retrieveCustomFields() {
        $headers = array(
            'Content-type' => 'text/xml',
            'userId' => $this->userId,
            'auth_tk' => $this->authToken
        );
        $response = wp_remote_get($this->url . 'getcustinfo/', array(
            'headers' => $headers,
            'sslverify' => false
                ));
        $data = wp_remote_retrieve_body($response);
        $data = simplexml_load_string($data, 'SimpleXMLElement', LIBXML_NOCDATA);
        return($data);
    }
    
    function retrieveWorkflowList(){
        $headers = array(
            'Content-type' => 'text/xml',
            'userId' => $this->userId,
            'auth_tk' => $this->authToken
        );
        $response = wp_remote_get($this->url . 'getworkflowlist/', array(
            'headers' => $headers,
            'sslverify' => false
                ));
        $data = wp_remote_retrieve_body($response);
        $data = simplexml_load_string($data, 'SimpleXMLElement', LIBXML_NOCDATA);
        return($data);        
    }

}
//End MakesBridge API
=======
    }
<<<<<<< HEAD
}
>>>>>>> first commit
=======
    
    function retrieveCustomFields(){
        $headers = array(
            'Content-type' => 'text/xml',
            'userId' => $this->userId,
            'auth_tk' => $this->authToken
        );
        $response = wp_remote_get($this->url . 'getcustinfo/', array(
            'headers' => $headers,
            'sslverify' => false
                ));
        $data = wp_remote_retrieve_body($response);
        $data = simplexml_load_string($data, 'SimpleXMLElement', LIBXML_NOCDATA);
        return($data);
    }
}
>>>>>>> commit
