<?php

/*
  Plugin Name: Archive Extension for Newsletter
  Plugin URI: http://www.thenewsletterplugin.com/plugins/newsletter/newsletter-archive-extension
  Description: Enables a special short code which can be used in a WordPress page to show the sent newsletter archives.
  Version: 4.0.0
  Author: Stefano Lissa
  Author URI: http://www.thenewsletterplugin.com
  Disclaimer: Use at your own risk. No warranty expressed or implied is provided.
 */

if (!defined('NEWSLETTER_EXTENSION')) {
    define('NEWSLETTER_EXTENSION', true);
}

class NewsletterArchive {

    /**
     * @var NewsletterArchive
     */
    static $instance;
    var $prefix = 'newsletter_archive';
    var $slug = 'newsletter-archive';
    var $plugin = 'newsletter-archive/archive.php';
    var $id = 58;
    var $options;

    function __construct() {
        self::$instance = $this;
        //register_activation_hook(__FILE__, array($this, 'hook_activation'));
        //register_deactivation_hook(__FILE__, array($this, 'hook_deactivation'));
        $this->options = get_option($this->prefix, array());
        add_action('init', array($this, 'hook_init'));
    }

//    function hook_activation() {
//    }
//
//    function hook_deactivation() {
//    }

    function hook_init() {
        global $wpdb;
        if (!class_exists('Newsletter')) {
            return;
        }
        add_filter('site_transient_update_plugins', array($this, 'hook_site_transient_update_plugins'));
        if (is_admin()) {
            add_action('admin_menu', array($this, 'hook_admin_menu'), 100);
            add_filter('newsletter_menu_newsletters', array($this, 'hook_newsletter_menu_newsletters'));
        } else {
            add_shortcode('newsletter_archive', array($this, 'shortcode_archive'));
        }

        if (isset($_GET['na']) && $_GET['na'] == 'archive') {
            $email = $wpdb->get_row($wpdb->prepare("select id, subject, message from " . NEWSLETTER_EMAILS_TABLE . " where private=0 and id=%d and type<>'followup' and status='sent'", (int) $_GET['email_id']));

            if (empty($email)) {
                die('Email not found');
            }

            // Force the UTF-8 charset
            header('Content-Type: text/html;charset=UTF-8');
            $message = $this->replace($email->message);
            echo $message;
            die();
        }
    }

    function hook_newsletter_menu_newsletters($entries) {
        $entries[] = array('label' => '<i class="fa fa-archive"></i> Archive', 'url' => '?page=newsletter_archive_index', 'description' => 'Publish your sent newsletters');
        return $entries;
    }

    function shortcode_archive($attrs, $content) {
        global $wpdb;

        // TODO: Manage the type
        $default_attrs = array('type' => 'message', 'url' => get_permalink(), 'max' => 10000);
        if (!is_array($attrs))
            $attrs = $default_attrs;
        else
            $attrs = array_merge($default_attrs, $attrs);

        $type = $attrs['type'];
        $max = (int) $attrs['max'];

        if (isset($_GET['email_id'])) {
            $email = $wpdb->get_row($wpdb->prepare("select id, subject, message from " . NEWSLETTER_EMAILS_TABLE . " where id=%d and private=0 and type=%s and status='sent' limit 1", (int) $_GET['email_id'], $type));
            if (!$email) {
                return 'Invalid email identifier';
            }
            $buffer .= '<h2>' . $this->replace($email->subject) . '</h2>';
            $buffer .= '<iframe style="width: 100%; height: 700px; border:1px solid #999" framborder="0" ';
            $buffer .= 'src="' . home_url() . '?na=archive&email_id=' . $email->id . '"></iframe>';
        } else {

            $emails = $wpdb->get_results($wpdb->prepare("select id, subject, send_on from " . NEWSLETTER_EMAILS_TABLE . " where private=0 and type=%s and status='sent' order by send_on desc limit %d", $type, $max));
            //$emails = $wpdb->get_results("select id, subject from " . NEWSLETTER_EMAILS_TABLE);
            $buffer .= $content;

            $buffer .= '<ul>';

            $date_format = get_option('date_format');
            $time_format = get_option('time_format');
            $gmt_offset = get_option('gmt_offset') * 3600;
            $url = $attrs['url'];

            foreach ($emails as &$email) {

                // TODO: Other replacements
                $subject = $this->replace($email->subject);

                $buffer .= '<li>';
                $buffer .= '<a href="' . NewsletterModule::add_qs($url, 'email_id=' . $email->id) . '">' . htmlspecialchars($subject) . '</a>';
                if (isset($this->options['date'])) {
                    $buffer .= ' <span>(' . gmdate($date_format, $email->send_on + $gmt_offset) . ')</span>';
                }
                $buffer .= '</li>';
            }

            $buffer .= '</ul>';
        }
        return $buffer;
    }

    function replace($text) {
        $text = str_replace('{name}', '', $text);
        $text = str_replace('{surname}', '', $text);
        $text = str_replace('{email_url}', '#', $text);
        $text = str_replace('{profile_url}', '#', $text);
        $text = str_replace('%7dprofile_url%7d', '#', $text);
        $text = str_replace('{unsubscription_url}', '#', $text);
        $text = str_replace('%7bemail_url%7d', '#', $text);

        return $text;
    }

    function hook_admin_menu() {
        $parent = null;
        if (NEWSLETTER_VERSION < 4) {
            $parent = 'newsletter_main_index';
        }

        add_submenu_page($parent, 'Archive', 'Archive', 'manage_options', 'newsletter_archive_index', array($this, 'menu_page_index'));
    }

    function menu_page_index() {
        global $wpdb;
        require dirname(__FILE__) . '/index.php';
    }

    function hook_site_transient_update_plugins($value) {
        if (!method_exists('Newsletter', 'set_extension_update_data')) {
            return $value;
        }

        return Newsletter::instance()->set_extension_update_data($value, $this);
    }

    function save_options($options) {
        $this->options = $options;
        update_option($this->prefix, $options);
    }

}

new NewsletterArchive();
