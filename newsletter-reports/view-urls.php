<?php
require_once dirname(__FILE__) . '/controls.php';
require_once dirname(__FILE__) . '/helper.php';

$email_id = (int) $_GET['id'];
$module = NewsletterStatistics::instance();
$controls = new NewsletterControls();
$email = $module->get_email($email_id);

if ($controls->is_action('set')) {
    $r = $wpdb->query($wpdb->prepare("update " . NEWSLETTER_USERS_TABLE . " set list_" . ((int) $controls->data['preference']) . "=1 where id in (select distinct user_id from " . NEWSLETTER_STATS_TABLE . " where url=%s and email_id=%d)", $controls->data['url'], $email_id));
    $controls->messages = '<strong>Added ' . $r . ' subscribers to list ' . ((int) $controls->data['preference']) . '</strong><br>(number could not match the total since subscribers could have been removed after the newsletter has been sent or alredy in that list)';
}

$urls = $wpdb->get_results($wpdb->prepare("select url, count(distinct user_id) as number from " . NEWSLETTER_STATS_TABLE . " where url<>'' and email_id=%d group by url order by number desc", $email->id));
$total = $wpdb->get_var($wpdb->prepare("select count(distinct user_id) from " . NEWSLETTER_STATS_TABLE . " where url<>'' and email_id=%d", $email->id));

function percent($value, $total) {
    if ($total == 0) {
        return '-';
    }
    return sprintf("%.2f", $value / $total * 100) . '%';
}

function percentValue($value, $total) {
    if ($total == 0) {
        return 0;
    }
    return round($value / $total * 100);
}
?>

<div class="wrap" id="tnp-wrap">
    <?php include NEWSLETTER_DIR . '/tnp-header.php' ?>
    <div id="tnp-heading">
        <h2>Click on links of "<?php echo htmlspecialchars($email->subject); ?>"</h2>

        <?php $controls->show(); ?>

    </div>

    <div id="tnp-body" style="min-width: 500px">
        
        <p>
	<a href="admin.php?page=newsletter_reports_view&id=<?php echo $email->id ?>" class="button">Back to the dashboard</a>
	<a href="admin.php?page=newsletter_reports_view_retarget&id=<?php echo $email->id?>" class="button">Retarget by clicked link</a>
	
	</p>


        <?php if (empty($urls)) { ?>
            <p>No clicks by now.</p>

        <?php } else { ?>

            <table class="widefat">
                <thead>
                    <tr>
                        <th>URL</th>
                        <th>Clicks</th>
                        <th>%</th>
                        <th>&nbsp;</th>

                    </tr>
                </thead>
                <tbody>
                    <?php for ($i = 0; $i < count($urls); $i++) { ?>
                        <tr>
                            <td><?php echo htmlspecialchars($urls[$i]->url) ?></td>
                            <td><?php echo $urls[$i]->number ?></td>

                            <td>
                                <?php echo percent($urls[$i]->number, $total); ?>
                            </td>

                            <td>
                                <?php
                                $users = $wpdb->get_results($wpdb->prepare("select distinct u.sex from " . NEWSLETTER_STATS_TABLE . " s join " . NEWSLETTER_USERS_TABLE .
                                                " u on u.id=s.user_id where email_id=%d and url=%s", $email->id, $urls[$i]->url));

                                $values = array('m' => 0, 'f' => 0, 'n' => 0);
                                foreach ($users as &$user) {
                                    switch ($user->sex) {
                                        case 'f': $values['f'] ++;
                                            break;
                                        case 'm': $values['m'] ++;
                                            break;
                                        default:
                                            $values['n'] ++;
                                    }
                                }
                                // Patch
                                $values['n'] = $urls[$i]->number - $values['m'] - $values['f'];
                                ?>
                                <?php echo $values['m']; ?>&nbsp;Males<br>
                                <?php echo $values['f']; ?>&nbsp;Females<br>
                                <?php echo $values['n']; ?>&nbsp;Unknown<br>
                            </td>
                            

                        <?php } ?>
                </tbody>
            </table>
        <?php } ?>


    </div>

    <?php include NEWSLETTER_DIR . '/tnp-footer.php' ?>
</div>
