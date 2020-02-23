<?php
include '../../../wp-load.php';

$email = $wpdb->get_row($wpdb->prepare("select id, subject, message from " . NEWSLETTER_EMAILS_TABLE . " where private=0 and id=%d and type<>'followup' and status='sent'", (int)$_GET['email_id']));

if (empty($email)) die('Email not found');


// Force the UTF-8 charset
header('Content-Type: text/html;charset=UTF-8');
$message = NewsletterArchive::$instance->replace($email->message);
echo $message;
