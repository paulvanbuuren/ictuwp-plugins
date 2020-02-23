<?php
require_once dirname(__FILE__) . '/controls.php';
require_once dirname(__FILE__) . '/helper.php';

$email_id = (int) $_GET['id'];
$module = NewsletterStatistics::instance();
$controls = new NewsletterControls();
$email = $module->get_email($email_id);

if ($controls->is_action('open')) {
    $r = $wpdb->query($wpdb->prepare("update " . NEWSLETTER_USERS_TABLE . " set list_" . ((int) $controls->data['preference']) . "=1 where id in (select distinct user_id from " . NEWSLETTER_STATS_TABLE . " where email_id=%d)", $email_id));
    $controls->messages = '<strong>Added ' . $r . ' subscribers to list ' . ((int) $controls->data['preference']) . '</strong><br>(number could not match the total since subscribers could have been removed after the newsletter has been sent or alredy in that list)';
}

if ($controls->is_action('click')) {
    $r = $wpdb->query($wpdb->prepare("update " . NEWSLETTER_USERS_TABLE . " set list_" . ((int) $controls->data['preference']) . "=1 where id in (select distinct user_id from " . NEWSLETTER_STATS_TABLE . " where url<>'' and email_id=%d)", $email_id));
    $controls->messages = '<strong>Added ' . $r . ' subscribers to list ' . ((int) $controls->data['preference']) . '</strong><br>(number could not match the total since subscribers could have been removed after the newsletter has been sent or alredy in that list)';
}

if ($controls->is_action('nothing')) {
    $r = $wpdb->query($wpdb->prepare("update " . NEWSLETTER_USERS_TABLE . " set list_" . ((int) $controls->data['preference']) . "=1 where id in (select distinct user_id from " . NEWSLETTER_SENT_TABLE . " where open=0 and email_id=%d)", $email_id));
    $controls->messages = '<strong>Added ' . $r . ' subscribers to list ' . ((int) $controls->data['preference']) . '</strong><br>(number could not match the total since subscribers could have been removed after the newsletter has been sent or alredy in that list)';
}

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

$urls = $wpdb->get_results($wpdb->prepare("select url, count(distinct user_id) as number from " . NEWSLETTER_STATS_TABLE . " where url<>'' and email_id=%d group by url order by number desc", $email->id));

?>
<style>
.form-table .widefat td, .form-table .widefat th {
	padding: 4px!important;
	font-size: .9em;
}
</style>

<div class="wrap" id="tnp-wrap">
    <?php include NEWSLETTER_DIR . '/tnp-header.php' ?>
    <div id="tnp-heading">
        <h2>Retargeting for "<?php echo htmlspecialchars($email->subject); ?>"</h2>

        <?php $controls->show(); ?>

    </div>

    <div id="tnp-body" style="min-width: 500px">
        
        <a href="admin.php?page=newsletter_reports_view&id=<?php echo $email->id ?>" class="button">Back to the dashboard</a>
        
        
        <div class="tnp-retarget">
            <table class="form-table tnp-retarget-table">
               
                        <tr>
                            <th>Subscribers who open the newsletter</td>
                            <td>
			    <form action="" method="post">
                                    <?php $controls->init() ?>
                                    <?php $controls->preferences_select() ?>
                                    <?php $controls->button_primary('open', 'Add to this list') ?>
                                </form>
			    
			    </td>
		    </tr>
		    <tr>
                            <th>Subscribers who click the newsletter</td>
                            <td>
			    <form action="" method="post">
                                    <?php $controls->init() ?>
                                    <?php $controls->preferences_select() ?>
                                    <?php $controls->button_primary('click', 'Add to this list') ?>
                                </form>
			    
			    </td>
		    </tr>
		    
		    <tr>
                            <th>Subscribers who did nothing</td>
                            <td>
			    <form action="" method="post">
                                    <?php $controls->init() ?>
                                    <?php $controls->preferences_select() ?>
                                    <?php $controls->button_primary('nothing', 'Add to this list') ?>
                                </form>
			    
			    </td>
		    </tr>
		    
		    <tr>
                            <th>By clicked link</th>
			    <td>
		    <table class="widefat">
                <thead>
                    <tr>
                        <th>URL</th>
                        <th>Clicks</th>
                        
                        <th>&nbsp;</th>
                    </tr>
                </thead>
                <tbody>
                    <?php for ($i = 0; $i < count($urls); $i++) { ?>
                        <tr>
                            <td><?php echo htmlspecialchars($urls[$i]->url) ?></td>
                            <td><?php echo $urls[$i]->number ?></td>
                            
                            <td>
                                <form action="" method="post">
                                    <?php $controls->init() ?>
                                    <?php $controls->data['url'] = $urls[$i]->url; ?>
                                    <?php $controls->hidden('url')?>
                                    <?php $controls->preferences_select() ?>
                                    <?php $controls->button_primary('set', 'Add to this list') ?>
                                </form>
                            </td>

                        </tr>
			<?php } ?>

                        
                </tbody>
            </table>
	    </td>
	    </tr>
	    	    
            </table>
        </div>
	    
	    



    </div>

    <?php include NEWSLETTER_DIR . '/tnp-footer.php' ?>
</div>
