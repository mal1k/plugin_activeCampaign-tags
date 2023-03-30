<?php
/**
 * @package ActiveCampaign Tags
 * @version 1.0.0
 */
/*
Plugin Name: ActiveCampaign Tags
Description: This plugin is used to synchronize tags in WordPress and ActiveCampaign
Author: Alex Kotlyarsky
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
    if ( ! wp_is_post_autosave( $post_id ) && ! wp_is_post_revision( $post_id ) ) {
        $activeCampaignTags = new ActiveCampaignTags();
        $subscribers = getSubscriberEmailsOfProductID($post_id);

        // get the list of tags associated with the post
        $tags = wp_get_post_tags($post_id, array('fields' => 'names'));

        foreach ($subscribers as $subscriber)
            $activeCampaignTags->addUserToArrayByEmail($subscriber);

        print_r($activeCampaignTags->getUsersArray());

        foreach ($tags as $tag)
            $tagsInActiveCampaign[] = $activeCampaignTags->getTagIdByName($tag);

        

        print_r($tagsInActiveCampaign);
        
        die();

        print_r( $activeCampaignTags->getTagIdByName('Alerts') );
        die();
    }
}
add_action( 'save_post', 'save_post_action', 10, 3 );

// достать пользователей которые имеют подписку на продукт
function getSubscriberEmailsOfProductID($product_id)
{
    return ["testemail@gmail.com", "test@gmail.com", "00154@ukr.net"];
}