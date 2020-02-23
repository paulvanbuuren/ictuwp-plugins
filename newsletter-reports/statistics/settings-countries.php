<?php
$module = NewsletterReports::$instance;
?>
<table class="form-table">
    <tr>
        <th>Country detection last run</th>
        <td>
            <?php echo $controls->print_date($module->get_last_run()); ?>
            <p class="description">
                The country detection finds the countries from which the users subscribed (visible on user statistic panel) and
                the countries from ehich the single newsletters have been read.
            </p>
        </td>
    </tr>
    <tr>
        <th>Country detection next run</th>
        <td>
            <?php echo $controls->print_date(wp_next_scheduled('newsletter_reports_country')); ?> 
            <?php $controls->button('countries', 'Run for test') ?>
        </td>
    </tr>
    <tr>
        <th>Country detection data</th>
        <td>
            Subscribers to be processed: <?php echo $wpdb->get_var("select count(*) from " . NEWSLETTER_USERS_TABLE . " where ip<>'' and country=''"); ?>
            <br>
            Subscribers resolved: <?php echo $wpdb->get_var("select count(*) from " . NEWSLETTER_USERS_TABLE . " where ip<>'' and country<>'' and country<>'XX'"); ?>
            <br>
            Subscribers not resolvable: <?php echo $wpdb->get_var("select count(*) from " . NEWSLETTER_USERS_TABLE . " where ip<>'' and country='XX'"); ?>

            <br><br>

            Statistic entries to be processed: <?php echo $wpdb->get_var("select count(*) from " . NEWSLETTER_STATS_TABLE . " where ip<>'' and country=''"); ?>
            <br>
            Statistic entries resolved: <?php echo $wpdb->get_var("select count(*) from " . NEWSLETTER_STATS_TABLE . " where ip<>'' and country<>'' and country<>'XX'"); ?>
            <br>
            Statistic entries not resolvable: <?php echo $wpdb->get_var("select count(*) from " . NEWSLETTER_STATS_TABLE . " where ip<>'' and country='XX'"); ?>
            <br>
            <p class="description">
                Totals refer only to subscribers or statistic entries which have an IP address. Some may have not.
            </p>
        </td>
    </tr>            

</table>

</form>