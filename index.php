<?php
/**
 * @package ActiveCampaign Tags
 * @version 1.0.0
 */
/*
Plugin Name: ActiveCampaign Tags
Description: This plugin is used to synchronize tags in WordPress and ActiveCampaign
Author: </Alex-K>
Version: 1.0.0
Author URI: http://t.me/alex_mal1k
*/

include_once 'Class/ActiveCampaignTags.php';

/**
 * Function for `save_post` action-hook.
 * 
 * @param int     $post_id Post ID.
 *
 * @return void
 */
function save_post_action($post_id){
    if ( ! wp_is_post_autosave( $post_id ) && ! wp_is_post_revision( $post_id ) )
        wp_schedule_single_event(time(), 'schedule_handler', array($post_id));
}
add_action('save_post_product', 'save_post_action', 10, 3 );

// Define a handler function.
function schedule_event_tags_handler($post_id) {
    $activeCampaignTags = new ActiveCampaignTags();
    $subscribers = getSubscriberEmailsOfProductID($post_id);
    $tagsInActiveCampaign = array();

    // get the list of tags associated with the post
    $tags = get_terms(
        array(
    	'taxonomy' => 'product_tag',
	    'object_ids' => $post_id,
    	'fields' => 'names',
        )
    );

    foreach ($subscribers as $subscriber)
        $activeCampaignTags->addUserToArrayByEmail($subscriber);

    foreach ($tags as $tag)
        $tagsInActiveCampaign[] = $activeCampaignTags->getTagIdByName($tag);

    $activeCampaignTags->addTagsToUsers(array_unique($tagsInActiveCampaign), $activeCampaignTags->getUsersArray());
}
add_action('schedule_handler', 'schedule_event_tags_handler', 10, 2);

// get users who have a subscription to the product
function getSubscriberEmailsOfProductID($product_id)
{
    $args = array(
        'status' => 'active',
        'product_id' => $product_id,
    );

    $subscribers = wcs_get_subscriptions($args);

    foreach ($subscribers as $subscriber)
        $subscriberEmails[] = $subscriber->data['billing']['email'];

    // return array_filter(array_unique($subscriberEmails));
    return ["valeriy.env@gmail.com", "testemail@gmail.com"];
}

add_action( 'woocommerce_subscription_status_active', 'on_new_subscription_active', 10, 1 );
function on_new_subscription_active($subscription) {
    // Get subscription data
    $customer_id = $subscription->get_customer_id();
    $product_id = $subscription->get_product_id();

    // Get the user object by ID
    $user = get_user_by('ID', $customer_id);
    $email = $user->user_email;
    $first_name = $user->first_name;
    $last_name = $user->last_name;

    // Get product tags by ID
    $tags = get_terms(
        array(
    	'taxonomy' => 'product_tag',
	    'object_ids' => $product_id,
    	'fields' => 'names',
        )
    );

    // Create a new contact in ActiveCampaign or get its ID
    $api_key = ActiveCampaignTags::API_TOKEN; // Specify your ActiveCampaign API key
    $account_id = ActiveCampaignTags::ACCOUNT_ID; // Specify your ActiveCampaign Account ID
    $api_url = 'https://'.$account_id.'.api-us1.com/api/3/'; // Specify URL for API requests

    // Get contact ID by email
    $response = wp_remote_get( $api_url . 'contacts', array(
        'headers' => array( 'Api-Token' => $api_key ),
        'body' => array(
            'email' => $email,
        ),
    ) );

    // Processing the response
    if ( !is_wp_error( $response ) && $response['response']['code'] == 200 ) {
        $result = json_decode( $response['body'] );
        if ( isset( $result->contacts ) && !empty( $result->contacts ) ) {
            $contact_id = $result->contacts[0]->id;
        } else {
            // Contact not found, create a new one
            $contact_data = array(
                'email' => $email,
                'firstName' => $first_name,
                'lastName' => $last_name,
            );

            $response = wp_remote_post( $api_url . 'contacts', array(
                'headers' => array( 'Api-Token' => $api_key ),
                'body' => json_encode( $contact_data ),
            ) );

            // Processing the response
            if ( !is_wp_error( $response ) && $response['response']['code'] == 200 ) {
                $result = json_decode( $response['body'] );
                $contact_id = $result->contact->id;
            }
        }
    }

    // Add product tags to contact profile
    if ( !empty($tags) && !empty($contact_id) )
        (new ActiveCampaignTags)->addTagsToUsers($tags, [$contact_id]);
}