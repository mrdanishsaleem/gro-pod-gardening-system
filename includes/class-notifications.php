<?php
class TPGS_Notifications {

    public static function send_harvest_notification($user_id, $pod_id, $vegetable_id) {
        $user = get_user_by('id', $user_id);
        if (!$user) {
            return false;
        }
        
        $vegetable = TPGS_Vegetable_Manager::get_vegetable($vegetable_id);
        if (!$vegetable) {
            return false;
        }
        
        $subject = sprintf('Your %s is ready to harvest!', $vegetable['name']);
        
        $message = sprintf(
            "Hello %s,\n\nYour %s in Pod #%d is ready to harvest!\n\n" .
            "Log in to your garden to harvest it or plant something new.\n\n" .
            "Happy gardening!\n" .
            "The 12-Pod Gardening System",
            $user->display_name,
            $vegetable['name'],
            $pod_id
        );
        
        return wp_mail($user->user_email, $subject, $message);
    }
}