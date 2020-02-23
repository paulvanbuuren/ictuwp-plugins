<?php
global $wpdb;
        $emails = $wpdb->get_results($wpdb->prepare("select id, subject, s.status, s.error from " . NEWSLETTER_EMAILS_TABLE . " e join " . $wpdb->prefix . "newsletter_sent s on s.email_id=e.id and s.user_id=%d", $user_id));

	echo '<table class="widefat">';
        echo '<thead><tr><th>ID</th><th>Subject</th><th>Delivered</th><th>Read</th><th>Clicked</th><th>Error</th></tr></thead>';
        foreach ($emails as $email) {
            echo '<tr>';
            echo '<td>';
            echo $email->id;
            echo '</td>';

            echo '<td>';
            echo esc_html($email->subject);
            echo '</td>';

            echo '<td>';

            echo $email->status ? '<span style="font-size: 1.5em; font-weight: bold; color: #990000">&#10007;</span>' : '<span style="font-size: 1.5em; font-weight: bold; color: #009900">&#10003;</span>';
            echo '</td>';

            $read = $wpdb->get_var($wpdb->prepare("select count(*) from " . NEWSLETTER_STATS_TABLE . " where user_id=%d and email_id=%d", $user_id, $email->id));

            echo '<td>';
            echo $read ? '<span style="font-size: 1.5em; font-weight: bold; color: #009900">&#10003;</span>' : '';
            echo '</td>';

            $clicked = $wpdb->get_var($wpdb->prepare("select count(*) from " . NEWSLETTER_STATS_TABLE . " where user_id=%d and email_id=%d and url<>''", $user_id, $email->id));

            echo '<td>';
            echo $clicked ? '<span style="font-size: 1.5em; font-weight: bold; color: #009900">&#10003;</span>' : '';
            echo '</td>';

            echo '<td>';
            echo esc_html($email->error);
            echo '</td>';

            echo '<tr>';
        }
        echo '</table>';
        ?>