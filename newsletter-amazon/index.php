<?php
require_once dirname(__FILE__) . '/controls.php';
require_once dirname(__FILE__) . '/api/aws-autoloader.php';
require_once dirname(__FILE__) . '/api/regions.inc.php';

use Aws\Common\Aws;

$newsletter = Newsletter::instance();
$module = NewsletterAmazon::$instance;
$controls = new NewsletterControls();
$logger = new NewsletterLogger('amazon');
$notification_endpoint = plugins_url('bounce.php', __FILE__);

if (!$controls->is_action()) {
    $controls->data = $module->options;
} else {

    if ($controls->is_action('save')) {
        $module->save_options($controls->data);
        $controls->messages = 'Saved.';
    }

    if ($controls->is_action('reset')) {
        $module->save_last_run(0);
        $controls->messages = 'Done.';
    }

    if ($controls->is_action('amazon_test')) {

        $module->save_options($controls->data);
        
        $logger->debug('amazon_test');

        $message['html'] = "<!DOCTYPE html>\n";
        $message['html'] .= "This is the rich text (HTML) version of a test message sent via Amazon.</p>\n";
        $message['html'] .= "This is a <strong>bold text</strong></p>\n";
        $message['html'] .= "This is a <a href='http://www.thenewsletterplugin.com'>link to www.thenewsletterplugin.com</a></p>\n";

        $message['text'] = 'This is the TEXT version of a test message sent via Amazon. You should see this message only if you email client does not support the rich text (HTML) version.';

        $subject = '[' . get_option('blogname') . '] API test message from the Newsletter Amazon Extension';

        $headers = array('X-Newsletter-Email-Id' => '0');

        $result = $module->mail($controls->data['test_email'], $subject, $message, $headers);
        if ($result) {
            $logger->debug('SUCCESS');
            $controls->messages = '<strong>Success!</strong><br><small>MessageId: ' . $module->amazon_result->get('MessageId') . '</small>';
        } else {
            $logger->debug('ERROR');
            $controls->errors = "<strong>ERROR</strong><br>" . htmlspecialchars(print_r($module->amazon_result, true));
        }
    }

    if ($controls->is_action('sns_setup')) {

        try {

            $aws = Aws::factory(array(
                        'key' => $controls->data['aws_key'],
                        'secret' => $controls->data['aws_secret'],
                        'region' => $controls->data['aws_region'],
            ));

            $sns_client = $aws->get('Sns');

            if (is_null($sns_client)) {
                $controls->errors = 'Error creating SNS client, check AWS credentials provided.<br>';
            } else {

                // create the topic
                $result = $sns_client->createTopic(array(
                    'Name' => 'wp_newsletter_bounce',
                ));
                $logger->debug(print_r($result, true));
                $topic_arn = $result->get('TopicArn');
                $logger->debug('Created topic ARN: ' . $topic_arn);

                if (empty($topic_arn)) {
                    $controls->errors = "Unable to create Amazon SNS topic.";
                } else {

                    $controls->messages = 'Topic created.<br>';

                    // subscribe to the topic
                    $result = $sns_client->subscribe(array(
                        'TopicArn' => $topic_arn,
                        'Protocol' => stripos($notification_endpoint,'https') === 0 ? 'https' : 'http',
                        'Endpoint' => $notification_endpoint,
                    ));

                    $subscription_arn = $result->get('SubscriptionArn');
                    if (empty($subscription_arn)) {
                        $controls->errors = "Unable to subcribe to the topic.";
                    } else {
                        $controls->messages .= 'Topic subscribed.<br>';
                        
                        $ses_client = $aws->get('Ses');
                        
                        // set as bounce topic on SES
                        $result = $ses_client->setIdentityNotificationTopic(array(
                            'Identity' => $newsletter->options['sender_email'],
                            'NotificationType' => 'Bounce',
                            'SnsTopic' => $topic_arn,
                        ));
                        
                        // set as complaints topic on SES
                        $result = $ses_client->setIdentityNotificationTopic(array(
                            'Identity' => $newsletter->options['sender_email'],
                            'NotificationType' => 'Complaint',
                            'SnsTopic' => $topic_arn,
                        ));

                        $controls->messages .= 'Topic set for SES bounce notifications.<br>';
                    }
                }
            }
        } catch (Exception $e) {
            $controls->errors .= '<small>' . $e->getMessage() . '</small><br>';
        }
    }
}
?>

<div class="wrap" id="tnp-wrap">
    <?php if (NEWSLETTER_VERSION > '4') @include NEWSLETTER_DIR . '/tnp-header.php' ?>
    <div id="tnp-heading">

    <h2>Newsletter Amazon Extension</h2>

    <?php $controls->show(); ?>

    <p>
        This module forces Newsletter to use Amazon SES as mail server and receives Amazon notifications setting the relative email addresses as bounced.
    </p>
    </div>
    <div id="tnp-body">
    <form action="" method="post">
        <?php $controls->init(); ?>

        <div id="tabs">
            <ul>
                <li><a href="#tabs-settings">Settings</a></li>
                <li><a href="#tabs-bounces">Bounces</a></li>
            </ul>

            <div id="tabs-settings">

                <p>This configuration overrides realtime the standard SMTP configuration of Newsletter plugin.</p>

                <table class="form-table">
                    <tr valign="top">
                        <th>Enabled?</th>
                        <td>
                            <?php $controls->yesno('enabled'); ?>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th>Access Key ID</th>
                        <td>
                            <?php $controls->text('aws_key', 50); ?>
                            <p class="description">You can generate an API key from AWS console, see <a href="http://www.thenewsletterplugin.com/plugins/newsletter/amazon-ses-extension" target="_blank">documentation</a>.</p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th>Secret Access Key</th>
                        <td>
                            <?php $controls->text('aws_secret', 50); ?>
                            <p class="description">You can generate an API key from AWS console, see <a href="http://www.thenewsletterplugin.com/plugins/newsletter/amazon-ses-extension" target="_blank">documentation</a>.</p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th>Service Region</th>
                        <td>
                            <?php $controls->select('aws_region', $ses_regions); ?>
                            <p class="description">To reduce data latency in your applications, most Amazon Web Services products allow you to select a regional endpoint to make your requests.</p>
                        </td>
                    </tr>
                    <tr>
                        <th>Current Sender Address
                            <span class="tip-button">Tip</span>
                            <span class="tip-content">
                                Amazon requires this address to be verified on SES console in order to use it as your sender.
                            </span>
                        </th>
                        <td>
                            <strong><?php echo $newsletter->options['sender_email'] ?></strong>
                            (<a href="admin.php?page=newsletter_main_main">Change it</a>)<br><br>
                            <?php
                            $nonce = wp_create_nonce("newsletter_amazon_checkaddr_nonce");
                            $link = admin_url('admin-ajax.php?action=newsletter_amazon_checkaddr&nonce=' . $nonce); ?>
                            <a class="button-primary" id="tnp_checkaddr" 
                               data-nonce="<?php echo $nonce ?>" href="<?php echo $link; ?>">
                                Check verification status
                            </a>
                            <span id="checkaddr_result" style="padding: 5px;"><img id="tnp_loading" src="images/loading.gif" style="display: none" /></span>
                            <?php
                            $nonce = wp_create_nonce("newsletter_amazon_verifyaddr_nonce");
                            $link = admin_url('admin-ajax.php?action=newsletter_amazon_verifyaddr&nonce=' . $nonce); ?>
                            <a class="button-primary" style="display: none" id="tnp_verifyaddr" 
                               data-nonce="<?php echo $nonce ?>" href="<?php echo $link; ?>">
                                Request verification
                            </a>
                        </td>
                    </tr>
                    <?php if (!empty($newsletter->options['reply_to'])) { ?>
                    <tr>
                        <th>Reply To</th>
                        <td>
                            <?php echo $newsletter->options['reply_to'] ?>
                            (<a href="admin.php?page=newsletter_main_main">Change it</a>)
                        </td>
                    </tr>
                    <?php } ?>
                    <?php if (!empty($newsletter->options['return_path'])) { ?>
                    <tr>
                        <th>Return Path
                            <span class="tip-button">Tip</span>
                            <span class="tip-content">
                                This email address must be either individually verified with Amazon SES, or from a domain that has been verified with Amazon SES.
                            </span>
                        </th>
                        <td>
                            <?php echo $newsletter->options['return_path'] ?>
                            (<a href="admin.php?page=newsletter_main_main">Change it</a>)
                        </td>
                    </tr>
                    <?php } ?>
                    <tr>
                        <th>To test this configuration</th>
                        <td>
                            <?php $controls->text('test_email', 30); ?>
                            <?php $controls->button('amazon_test', 'Send a message to this email'); ?>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th>License key</th>
                        <td>
                            <?php
                            if (empty(Newsletter::instance()->options['contract_key'])) {
                                echo 'Not set';
                            } else {
                                echo Newsletter::instance()->options['contract_key'];
                            }
                            ?>
                            <p class="description">
                                The license key can be set on main Newsletter configuration and will be used for the one clic
                                update feature.
                            </p>
                        </td>
                    </tr>
                </table>
            </div>


            <div id="tabs-bounces">
                <p>
                    Amazon SES can notify you of the status of your emails through Amazon Simple Notification Service (Amazon SNS).
                    Verify costs on your Amazon console.
                </p>
                <table class="form-table">
                    <tr>
                        <th>Bounce configuration</th>
                        <td>
                            <?php $controls->button('sns_setup', 'Do the magic for me!'); ?> in order to receive bounce notifications   
                        </td>
                    </tr>
                    <tr valign="top">
                        <th>Process Amazon soft bounces?</th>
                        <td>
                            <?php echo $controls->yesno('process_soft_bounces'); ?><br>
                            In order to process also the complaints, you have to set the same SNS topic for Bounces and Complaints.
                        </td>
                    </tr>
                    <tr valign="top">
                        <th>Last notification processed</th>
                        <td>
                            <?php echo $controls->print_date($module->get_last_run()); ?>
                        </td>
                    </tr>
                </table>

            </div>

        </div>

        <p>
            <?php $controls->button('save', 'Save'); ?>
        </p>
    </form>
    </div>
        <?php if (NEWSLETTER_VERSION > '4') @include NEWSLETTER_DIR . '/tnp-footer.php' ?>
</div>

<script type="text/javascript">

    jQuery(document).ready(function ($) {
        jQuery("#tnp_checkaddr").click(function (event) {
            event.preventDefault();
            $('#tnp_loading').show();
            nonce = jQuery(this).attr("data-nonce")
            var data = {
                action: 'tnp_amazon_checkaddr',
                nonce: nonce,
                aws_key: $("input[name=options\\[aws_key\\]]").val(),
                aws_secret: $("input[name=options\\[aws_secret\\]]").val(),
                aws_region: $("select[name=options\\[aws_region\\]]").val(),
            };
            $.post(ajaxurl, data, function (response) {
                $("#checkaddr_result").html(response.message);        
                if (response.valid > 0) {
                    $("#tnp_verifyaddr").hide();
                } else {
                    $("#tnp_verifyaddr").fadeIn('fast');
                }
            }, "json");
        });
        jQuery("#tnp_verifyaddr").click(function (event) {
            event.preventDefault();
            nonce = jQuery(this).attr("data-nonce")
            var data = {
                action: 'tnp_amazon_verifyaddr',
                nonce: nonce,
                aws_key: $("input[name=options\\[aws_key\\]]").val(),
                aws_secret: $("input[name=options\\[aws_secret\\]]").val(),
                aws_region: $("select[name=options\\[aws_region\\]]").val(),
            };
            $.post(ajaxurl, data, function (response) {
                $("#tnp_verifyaddr").hide();
                if (response.valid < 0) {
                    $("#checkaddr_result").html(response.message);
                } else if (response.valid == 0) {
                    $("#checkaddr_result").html(response.message);
                }
                else {
                    jQuery("#checkaddr_result").html(response.message);
                }
            }, "json");
        });
    });

</script>