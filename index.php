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