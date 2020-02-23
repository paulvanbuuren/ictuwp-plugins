<?php

class NewsletterStatisticsHelper {

    /** Returns the number of subscribers who read or clicked the email. */
    function get_read_count($email_id) {
        global $wpdb;
        $email_id = (int) $email_id;
        return (int) $wpdb->get_var("select count(distinct user_id) from " . NEWSLETTER_STATS_TABLE . " where email_id=" . $email_id);
    }

    /** Returns the number of subscribers who clicked the email (always lower than the number of readers). */
    function get_clicked_count($email_id) {
        global $wpdb;
        $email_id = (int) $email_id;

        return (int) $wpdb->get_var("select count(distinct user_id) from " . NEWSLETTER_STATS_TABLE . " where url<>'' and email_id=" . $email_id);
    }

    /** Return the total number of clicks. */
    function get_click_count($email_id) {
        global $wpdb;
        $email_id = (int) $email_id;

        return (int) $wpdb->get_var("select count(*) from " . NEWSLETTER_STATS_TABLE . " where url<>'' and email_id=" . $email_id);
    }

    function get_clicked_urls($email_id) {
        global $wpdb;
        $email_id = (int) $email_id;
        return $wpdb->get_results("select url, count(*) as number from " . NEWSLETTER_STATS_TABLE . " where url<>'' and email_id=" . $email_id . " group by url order by number desc");
    }

    function get_first_events($email_id) {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare("select min(unix_timestamp(created)) as created from " . NEWSLETTER_STATS_TABLE . " where email_id=%d group by user_id order by created", $email_id));
    }
    
    function get_click_events($email_id) {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare("SELECT COUNT(1) AS events_count, "
                . "DATE(created) AS event_day, url FROM " . NEWSLETTER_STATS_TABLE . " "
                . "WHERE email_id=%d AND url > '' GROUP BY event_day ORDER BY event_day", $email_id));
    }
    
    function get_open_events($email_id) {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare("SELECT COUNT(1) AS events_count, "
                . "DATE(created) AS event_day, url FROM " . NEWSLETTER_STATS_TABLE . " "
                . "WHERE email_id=%d GROUP BY event_day ORDER BY event_day", $email_id));
    }

    function aggregate_events_by_day($events, $start_time=0) {
        if ($start_time == 0) {
            $start_time = $events[0]->created;
        }
        $list = array_fill(0, 10, 0);
        foreach ($events as &$event) {
            $delta = max(0, $event->created-$start_time);
            $i = floor($delta / (3600*24));
            if ($i <= count($list)) $list[$i]++;
        }
        return $list;
    }

    function get_sex($email_id) {
        global $wpdb;

        // TODO: Optimized understanding the usage
        $users = $wpdb->get_results($wpdb->prepare("select distinct u.* from " . NEWSLETTER_STATS_TABLE . " s join " . NEWSLETTER_USERS_TABLE .
                " u on u.id=s.user_id where email_id=%d",
                $email_id));
        return $this->split_by_sex($users);
    }

    function get_users_by_url($email_id, $url) {
        global $wpdb;

        // TODO: Optimized understanding the usage
        return $wpdb->get_results($wpdb->prepare("select distinct u.* from " . NEWSLETTER_STATS_TABLE . " s join " . NEWSLETTER_USERS_TABLE .
                " u on u.id=s.user_id where email_id=%d and url=%s",
                $email_id, $url));
    }

    function split_by_sex(&$users) {
        $values = array('m'=>0, 'f'=>0, 'n'=>0);
        foreach ($users as &$user) {
            switch ($user->sex) {
                case 'f': $values['f']++;
                    break;
                case 'm': $values['m']++;
                    break;
                default:
                    $values['n']++;
            }
        }
        return $values;
    }

}
