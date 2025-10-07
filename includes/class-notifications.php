<?php
class TPGS_Notifications {

    public static function send_harvest_notification($user_id, $pod_id, $plant_id) {
        $user = get_user_by('id', $user_id);
        if (!$user) {
            return false;
        }
        
        $plant = TPGS_Plant_Manager::get_plant($plant_id);
        if (!$plant) {
            return false;
        }
        
        $subject = sprintf('Your %s is ready to harvest!', $plant['name']);
        
        $message = sprintf(
            "Hello %s,\n\nYour %s in Pod #%d is ready to harvest!\n\n" .
            "Log in to your garden to harvest it or plant something new.\n\n" .
            "Happy gardening!\n" .
            "The GRO Pod Gardening System",
            $user->display_name,
            $plant['name'],
            $pod_id
        );
        
        return wp_mail($user->user_email, $subject, $message);
    }
}