<?php
class ActiveCampaignTags {
    const ACCOUNT_ID = 'wdp60034';
    const API_TOKEN = '';
    
    protected $users = array();
    protected $account;
    protected $api_key;

    public function __construct() {
        // Plugin initialization
        $this->account = ActiveCampaignTags::ACCOUNT_ID;
        $this->api_key = ActiveCampaignTags::API_TOKEN;
    }

    // create tag on active campaign if not exist
    public function createTag($tag_name)
    {
        $api_url = 'https://'.$this->account.'.api-us1.com/api/3/tags';
        $args = array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'Api-Token' => $this->api_key
            ),
            'body' => json_encode( array(
                'tag' => [
                    'tag' => $tag_name,
                    'tagType' => 'contact',
                    'description' => 'The tag was created automatically using the ActiveCampaignTags WordPress plugin.'
                ]
            ) )
        );

        // Send a POST request to a remote server
        $response = wp_remote_post( $api_url, $args );

        // Check if the request succeeded
        if ( !is_wp_error( $response ) ) {
            // Get a response from the server
            $body = wp_remote_retrieve_body( $response );
            $data = json_decode( $body );

            return $data->tag->id;
        }
        die('Request error');
    }

    // find tag
    public function getTagIdByName($tag_name) {
        $api_url = 'https://'.$this->account.'.api-us1.com/api/3/tags';
        $params = array(
            'search' => $tag_name,
        ); // GET request parameters

        // Add parameters to the URL
        $url = add_query_arg($params, $api_url);

        // Request options
        $args = array(
            'timeout' => 30, // Request timeout in seconds
            'headers' => array(
                'Content-Type' => 'application/json',
                'Api-Token' => $this->api_key
            )
        );

        // Send request
        $response = wp_remote_get($url, $args);

        if (!is_wp_error($response)) {
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);
            $tag_id = null;
    
            // Look for a tag by name and get its ID
            foreach ($data['tags'] as $tag) {
                if ($tag['tag'] === $tag_name) {
                    $tag_id = $tag['id'];
                    break;
                }
            }

            if ( empty($tag_id) )
                $tag_id = $this->createTag($tag_name);
                
            return $tag_id;
        }
        die('Request error');
    }

    // add user to array
    public function addUserToArray($id) {
        array_push($this->users, $id);
    }

    // get an array of user ids
    public function getUsersArray()
    {
        return array_filter(array_unique($this->users));
    }

    // finding user id by email and adding it to user array
    public function addUserToArrayByEmail($email) {
        $api_url = 'https://'.$this->account.'.api-us1.com/api/3/contacts'; // URL API
        $params = array(
            'email' => $email
        ); // GET request parameters

        // Add parameters to the URL
        $url = add_query_arg($params, $api_url);

        // Request options
        $args = array(
            'timeout' => 30, // Request timeout in seconds
            'headers' => array(
                'Content-Type' => 'application/json',
                'Api-Token' => $this->api_key
            )
        );

        // Send request
        $response = wp_remote_get($url, $args);

        // Processing the response
        if (is_wp_error($response)) {
            $api_response = false;
        } else {
            $body = wp_remote_retrieve_body($response);
            $this->addUserToArray(json_decode($body, true)['contacts'][0]['id']);
            $api_response = true;
        }

        // Return result
        return $api_response;
    }

    // find contact
    // public function getContactIdByEmail($user_email) {
    //     $api_url = 'https://'.$this->account.'.api-us1.com/api/3/contacts';
    //     $params = array(
    //         'email' => $user_email,
    //     ); // GET request parameters

    //     // Add parameters to the URL
    //     $url = add_query_arg($params, $api_url);

    //     // Request options
    //     $args = array(
    //         'timeout' => 30, // Request timeout in seconds
    //         'headers' => array(
    //             'Content-Type' => 'application/json',
    //             'Api-Token' => $this->api_key
    //         )
    //     );

    //     // Send request
    //     $response = wp_remote_get($url, $args);

    //     if (!is_wp_error($response)) {
    //         $body = wp_remote_retrieve_body($response);
    //         $data = json_decode($body, true);
                            
    //         return $tag_id;
    //     }
    //     die('Request error');
    // }

    
}