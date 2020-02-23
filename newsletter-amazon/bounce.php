<?php

/*
 * HANDLE AMAZON SES NOTIFICATIONS
 * 
 */

require_once dirname(__FILE__) . '/api/aws-autoloader.php';

//define('SHORTINIT', true);
include '../../../wp-load.php';

use Aws\Sns\MessageValidator\Message;
use Aws\Sns\MessageValidator\MessageValidator;
use Guzzle\Http\Client;

$log_file = '../../logs/newsletter/amazon_sns.log';
$options = get_option('newsletter_amazon', array());

// Make sure the request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die;
}

//file_put_contents($log_file, file_get_contents('php://input') . "\n", FILE_APPEND);

try {

    // Create a message object from the POST body
    $message = Message::fromRawPostData();

    // Validate the message
    $validator = new MessageValidator();
    $validator->validate($message);

    $type = $message->get('Type');
    file_put_contents($log_file, '--- Handle the [' . $type . '] notification' . "\n", FILE_APPEND);

    // handle the notification
    if ($type === 'SubscriptionConfirmation') {
        file_put_contents($log_file, 'Type: SubscriptionConfirmation' . "\n", FILE_APPEND);
        confirmSubscription($message->get('SubscribeURL'));
    } elseif ($type === 'Notification') {
        file_put_contents($log_file, 'Type: Notification' . "\n", FILE_APPEND);
        processBounce($message->get('Message'));
    }
} catch (Exception $ex) {
    // handle errors
    file_put_contents($log_file, $ex->getMessage() . "\n", FILE_APPEND);
    http_response_code(404);
    die();
}

/*
 * send a request to the SubscribeURL to complete subscription
 */

function confirmSubscription($url) {

    global $log_file;

    file_put_contents($log_file, "SubscriptionConfirmation [URL: $url]" . "\n", FILE_APPEND);

    (new Client)->get($url)->send();
}

/*
 * handle the bounce
 */

function processBounce($message) {

    global $log_file;
    global $wpdb;
    global $options;

    file_put_contents($log_file, "Handle the message\n", FILE_APPEND);
    // update last run
    update_option('newsletter_amazon_last_run', time());

    // check if we have to process soft bounces and complaints
    $process_soft_bounces = $options['process_soft_bounces'] ? true : false;
    file_put_contents($log_file, 'Process soft bounces: ' . $process_soft_bounces . "\n", FILE_APPEND);

    $message = json_decode($message);

    if ($message->notificationType == "Bounce") {

        $bounceType = $message->bounce->bounceType;
        file_put_contents($log_file, 'Bounce type: ' . $bounceType . "\n", FILE_APPEND);

        if ($bounceType == 'Permanent' || $process_soft_bounces) {

            $recipients = $message->bounce->bouncedRecipients;
        }
    } elseif ($message->notificationType == "Complaint" && $process_soft_bounces) {
        file_put_contents($log_file, "Complaint\n", FILE_APPEND);
        $recipients = $message->complaint->complainedRecipients;
    }

    file_put_contents($log_file, '[' . count($recipients) . '] recipients' . "\n", FILE_APPEND);

    $query = "update " . NEWSLETTER_USERS_TABLE . " set status='B' where email=%s limit 1";

    foreach ($recipients as $recipient) {
        $email = $recipient->emailAddress;
        file_put_contents($log_file, 'Executing SQL: [' . sprintf($query, $email) . "]\n", FILE_APPEND);
        $wpdb->query($wpdb->prepare($query, strtolower($email)));
    }
}

