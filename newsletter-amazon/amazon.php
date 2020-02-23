<?php

/*
  Plugin Name: Newsletter Amazon SES Extension
  Plugin URI: http://www.thenewsletterplugin.com/plugins/newsletter/amazon-ses-extension
  Description: Integrates Newsletter with Amazon SES service for sending emails and processing bounces. Automatic updates available with the license key.
  Version: 1.0.9
  Author: The Newsletter Team
  Author URI: http://www.thenewsletterplugin.com
  Disclaimer: Use at your own risk. No warranty expressed or implied is provided.

 */

if (!defined('NEWSLETTER_EXTENSION')) {
    define('NEWSLETTER_EXTENSION', true);
}

class NewsletterAmazon {

    /**
     * @var NewsletterAmazon
     */
    static $instance;
    var $prefix = 'newsletter_amazon';
    var $slug = 'newsletter-amazon';
    var $plugin = 'newsletter-amazon/amazon.php';
    var $id = 60; // id filecommerce
    var $options;

    /* @var NewsletterLogger */
    var $logger;

    function __construct() {
        self::$instance = $this;

        add_action('init', array($this, 'hook_init'));
        $this->options = get_option($this->prefix, array());
    }

    function hook_init() {
        if (!class_exists('Newsletter')) {
            return;
        }

        include_once NEWSLETTER_INCLUDES_DIR . '/logger.php';
        $this->logger = new NewsletterLogger('amazon');

        add_filter('site_transient_update_plugins', array($this, 'hook_site_transient_update_plugins'));
        if (!empty($this->options['enabled'])) {
            add_action('newsletter_amazon_bounce', array($this, 'bounce'));
            if (method_exists('Newsletter', 'register_mail_method')) {
                Newsletter::instance()->register_mail_method(array($this, 'mail'));
            }
        }
        if (is_admin()) {
            add_action('admin_menu', array($this, 'hook_admin_menu'), 100);
            add_action("wp_ajax_tnp_amazon_checkaddr", array($this, "hook_wp_ajax_tnp_amazon_checkaddr"));
            add_action("wp_ajax_nopriv_tnp_amazon_checkaddr", array($this, "hook_wp_ajax_tnp_must_login"));
            add_action("wp_ajax_tnp_amazon_verifyaddr", array($this, "hook_wp_ajax_tnp_amazon_verifyaddr"));
            add_action("wp_ajax_nopriv_tnp_amazon_verifyaddr", array($this, "hook_wp_ajax_tnp_must_login"));

            add_filter('newsletter_menu_settings', array($this, 'hook_newsletter_menu_settings'));
        }
    }

    function hook_newsletter_menu_settings($entries) {
        $entries[] = array('label' => '<i class="fa fa-envelope-o"></i> Amazon SES', 'url' => '?page=newsletter_amazon_index', 'description' => 'Send emails with Amazon SES');
        return $entries;
    }

    function save_options($options) {
        $this->options = $options;
        update_option($this->prefix, $options);
    }

    function hook_newsletter_smtp($smtp_options) {
        $smtp_options['enabled'] = 1;
        $smtp_options['host'] = $this->options['smtp_host'];
        $smtp_options['port'] = $this->options['smtp_port'];
        $smtp_options['secure'] = $this->options['smtp_secure'];
        $smtp_options['user'] = $this->options['smtp_user'];
        $smtp_options['pass'] = $this->options['smtp_password'];

        return $smtp_options;
    }

    function hook_site_transient_update_plugins($value) {
        if (!method_exists('Newsletter', 'set_extension_update_data')) {
            return $value;
        }

        return Newsletter::instance()->set_extension_update_data($value, $this);
    }

    function hook_admin_menu() {
        $parent = null;
        if (NEWSLETTER_VERSION < 4)
            $parent = 'newsletter_main_index';

        add_submenu_page($parent, 'Amazon SES Extension', 'Amazon SES', 'manage_options', 'newsletter_amazon_index', array($this, 'menu_page_index'));
    }

    function menu_page_index() {
        global $wpdb;
        require dirname(__FILE__) . '/index.php';
    }

    function hook_wp_ajax_tnp_amazon_checkaddr() {

        $newsletter = Newsletter::instance();
        require_once dirname(__FILE__) . '/api/aws-autoloader.php';

        if (!wp_verify_nonce($_REQUEST['nonce'], "newsletter_amazon_checkaddr_nonce")) {
            exit("No naughty business please");
        }

        $address = $newsletter->options['sender_email'];
        $ajax_result['valid'] = -1;
        $ajax_result['message'] = "";

        try {

            $aws = Aws\Common\Aws::factory(array(
                        'key' => $_POST['aws_key'], // $this->options['aws_key'],
                        'secret' => $_POST['aws_secret'], // $this->options['aws_secret'],
                        'region' => $_POST['aws_region'], // $this->options['aws_region'],
            ));

            $ses_client = $aws->get('Ses');

            if (is_null($ses_client)) {
                $ajax_result['message'] = 'Error creating SNS client, check AWS credentials provided.';
            } else {
                // get the verification status of the identity
                $result = $ses_client->getIdentityVerificationAttributes(array(
                    'Identities' => array($address),
                ));

                $attributes = $result->get('VerificationAttributes');
                $this->logger->debug($attributes);

                if (is_array($attributes[$address])) {
                    if ($attributes[$address]['VerificationStatus'] == 'Success') {
                        $ajax_result['valid'] = 1;
                        $ajax_result['message'] = 'Address ' . $address . ' <strong>verified</strong>.';
                    } else {
                        $ajax_result['valid'] = 0;
                        $ajax_result['message'] = '<strong>Pending verification</strong>, check your inbox.';
                    }
                } else {
                    $ajax_result['valid'] = 0;
                    $ajax_result['message'] = 'Address ' . $address . ' <strong>NOT verified<strong>.';
                }
            }
        } catch (Exception $e) {
            $ajax_result['message'] = $e->getMessage();
        }

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            echo json_encode($ajax_result);
        } else {
            header("Location: " . $_SERVER["HTTP_REFERER"]);
        }

        wp_die();
    }

    function hook_wp_ajax_tnp_amazon_verifyaddr() {

        $newsletter = Newsletter::instance();
        require_once dirname(__FILE__) . '/api/aws-autoloader.php';

        if (!wp_verify_nonce($_REQUEST['nonce'], "newsletter_amazon_verifyaddr_nonce")) {
            exit("No naughty business please");
        }

        $address = $newsletter->options['sender_email'];
        $ajax_result['valid'] = -1;
        $ajax_result['message'] = "";

        try {

            $aws = Aws\Common\Aws::factory(array(
                        'key' => $_POST['aws_key'], // $this->options['aws_key'],
                        'secret' => $_POST['aws_secret'], // $this->options['aws_secret'],
                        'region' => $_POST['aws_region'], // $this->options['aws_region'],
            ));

            $ses_client = $aws->get('Ses');

            if (is_null($ses_client)) {
                $ajax_result['message'] = 'Error creating SNS client, check AWS credentials provided.';
            } else {

                // verify the identity
                $result = $ses_client->verifyEmailIdentity(array(
                    'EmailAddress' => $address,
                ));

                $ajax_result['valid'] = 1;
                $ajax_result['message'] = 'Email address verification requested. This action causes a <strong>confirmation email message</strong> to be sent to the specified address. Now <strong>check your inbox</strong>.';
            }
        } catch (Exception $e) {
            $ajax_result['message'] = $e->getMessage();
        }

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            echo json_encode($ajax_result);
        } else {
            header("Location: " . $_SERVER["HTTP_REFERER"]);
        }

        wp_die();
    }

    function hook_wp_ajax_tnp_must_login() {
        echo "You must log in";
        wp_die();
    }

    var $amazon = null;
    var $amazon_result;

    function mail($to, $subject, $message, $headers = null) {

        $newsletter = Newsletter::instance();
        require_once dirname(__FILE__) . '/api/aws-autoloader.php';

        if (!is_array($message)) {
            $html = $message;
        } else {
            if (!empty($message['html'])) {
                $html = $message['html'];
            }
            if (!empty($message['text'])) {
                $text = $message['text'];
            }
        }

        $from_email = $newsletter->options['sender_email'];
        $from_name = $newsletter->options['sender_name'];
        $from = '"' . $from_name . '" <' . $from_email . '>';
        $reply_to = $newsletter->options['reply_to'];
        $return_path = $newsletter->options['return_path'];
        
        $aws_key = $this->options['aws_key'];
        $aws_secret = $this->options['aws_secret'];
        $aws_region = $this->options['aws_region'];

        try {

            $aws = Aws\Common\Aws::factory(array(
                        'key' => $aws_key,
                        'secret' => $aws_secret,
                        'region' => $aws_region,
            ));

            $ses_client = $aws->get('Ses');

            if (!empty($text)) {
                $body['Text'] = array('Data' => $text);
            }

            if (!empty($html)) {
                $body['Html'] = array('Data' => $html);
            }

            $ses_email = array(
                'Source' => $from,
                'Destination' => array(
                    'ToAddresses' => array($to),
                ),
                'Message' => array(
                    'Subject' => array(
                        'Data' => $subject,
                    ),
                    'Body' => $body,
                ),
            );
            
            if (!empty($reply_to)) {
                $ses_email['ReplyToAddresses'] = array($reply_to);
            }
            if (!empty($return_path)) {
                $ses_email['ReturnPath'] = $return_path;
            }
            
            $this->amazon_result = $ses_client->sendEmail($ses_email);
        } catch (Exception $e) {
            $this->amazon_result = $e->getMessage();
            return false;
        }

        $this->logger->debug($this->amazon_result);

        if (is_object($this->amazon_result)) {
            $message_id = $this->amazon_result->get('MessageId');
            if (!empty($message_id)) {
                return true;
            }
        }
        return false;
    }

    function save_last_run($time) {
        update_option($this->prefix . '_last_run', $time);
    }

    function get_last_run() {
        return get_option($this->prefix . '_last_run', 0);
    }

}

new NewsletterAmazon();
