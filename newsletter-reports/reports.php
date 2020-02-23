<?php

/*
  Plugin Name: Newsletter Reports Extension
  Plugin URI: http://www.thenewsletterplugin.com/
  Description: Extends the statistic viewer adding graphs, link clicks, export and many other data. Automatic updates available setting the license key on Newsletter configuration panel.
  Version: 4.0.6
  Author: The Newsletter Team
  Author URI: http://www.thenewsletterplugin.com
  Disclaimer: Use at your own risk. No warranty expressed or implied is provided.
 */

if (!defined('NEWSLETTER_EXTENSION')) {
    define('NEWSLETTER_EXTENSION', true);
}

class NewsletterReports {

    var $prefix = 'newsletter_reports';
    var $slug = 'newsletter-reports';
    var $plugin = 'newsletter-reports/reports.php';
    var $id = 50;
    var $options;

    /**
     * @return NewsletterReports
     */
    static $instance;

    function __construct() {
        self::$instance = $this;
        add_action('init', array($this, 'hook_init'));
        $this->options = get_option($this->prefix, array());
    }

    function hook_newsletter_users_edit_general($id) {
        include __DIR__ . '/users/edit-general.php';
    }

    function hook_init() {

        if (!class_exists('Newsletter')) {
            return;
        }

        add_filter('site_transient_update_plugins', array($this, 'hook_site_transient_update_plugins'));

        if (is_admin()) {

            add_action('admin_menu', array($this, 'hook_admin_menu'), 100);

            add_filter('newsletter_statistics_view', array($this, 'hook_newsletter_statistics_view'));

            add_action('newsletter_users_edit_newsletters', array($this, 'hook_newsletter_users_edit_newsletters'));
            add_action('newsletter_statistics_index_map', array($this, 'hook_newsletter_statistics_index_map'));

            add_action('newsletter_statistics_settings_countries', array($this, 'hook_newsletter_statistics_settings_countries'));

            //add_action('newsletter_users_edit_general', array($this, 'hook_newsletter_users_edit_general'), 10, 1);

            add_action('newsletter_users_statistics_countries', array($this, 'hook_newsletter_users_statistics_countries'));
            add_action('newsletter_users_statistics_time', array($this, 'hook_newsletter_users_statistics_time'));
            add_action('newsletter_statistics_view', array($this, 'hook_newsletter_statistics_view'));
            add_action('newsletter_statistics_view_retarget', array($this, 'hook_newsletter_statistics_view_retarget'));
            add_action('newsletter_statistics_view_urls', array($this, 'hook_newsletter_statistics_view_urls'));
            add_action('newsletter_statistics_view_users', array($this, 'hook_newsletter_statistics_view_users'));

            add_action('newsletter_statistics_settings_init', array($this, 'hook_newsletter_statistics_settings_init'));

            add_action('wp_ajax_newsletter_reports_export', array($this, 'hook_wp_ajax_newsletter_reports_export'));
        }

        if (!defined('DOING_CRON') || !DOING_CRON) {
            if (wp_get_schedule('newsletter_reports_country') === false) {
                wp_schedule_event(time() + 60, 'newsletter', 'newsletter_reports_country');
            }
        }
        add_action('newsletter_reports_country', array($this, 'country'));
    }

    function hook_wp_ajax_newsletter_reports_export() {
        global $wpdb;
        
        $email_id = (int) $_GET['email_id'];

        header('Content-Type: application/octect-stream;charset=UTF-8');
        header('Content-Disposition: attachment; filename=newsletter-' . $email_id . '.csv');

        echo '"Subscriber ID";"Email";"Name";"Surname";"Sex";"Open";"URL"';
        echo "\n";

        $page = 0;
        while (true) {
            $users = $wpdb->get_results($wpdb->prepare("select distinct u.id, u.email, u.name, u.surname, u.sex, t.open as sent_open, s.url from " . NEWSLETTER_USERS_TABLE . " u
    join " . NEWSLETTER_SENT_TABLE . " t on t.user_id=u.id and t.email_id=%d
        left join " . NEWSLETTER_STATS_TABLE . " s on u.id=s.user_id and s.email_id=%d
        order by u.id limit " . $page * 500 . ",500", $email_id, $email_id));

            if (!empty($wpdb->last_error)) {
                die($wpdb->last_error);
            }

            for ($i = 0; $i < count($users); $i++) {
                echo '"' . $users[$i]->id;
                echo '";"';
                echo $users[$i]->email;
                echo '";"';
                echo $this->sanitize_csv($users[$i]->name);
                echo '";"';
                echo $this->sanitize_csv($users[$i]->surname);
                echo '";"';
                echo $users[$i]->sex;
                echo '";"';
                echo $users[$i]->sent_open == 0 ? '0' : '1';
                echo '";"';
                echo $this->sanitize_csv($users[$i]->url);
                echo '"';
                echo "\n";
                flush();
            }
            if (count($users) < 500) {
                break;
            }
            $page++;
        }
        die('');
    }

    function sanitize_csv($text) {
        $text = str_replace('"', "'", $text);
        $text = str_replace("\n", ' ', $text);
        $text = str_replace("\r", ' ', $text);
        $text = str_replace(";", ' ', $text);
        return $text;
    }

    function hook_newsletter_statistics_settings_init(NewsletterControls $controls) {

        if ($controls->is_action('countries')) {
            $result = $this->country(true);
            if (is_wp_error($result)) {
                $controls->errors .= $result->get_error_message();
            } else {
                $controls->messages .= 'Success (test IP resolved to ' . $result . ')';
            }
        }
    }

    function hook_newsletter_users_statistics_countries() {
        global $wpdb;
        include __DIR__ . '/users/statistics-countries.php';
    }

    function hook_newsletter_statistics_settings_countries($controls) {
        global $wpdb;
        include __DIR__ . '/statistics/settings-countries.php';
    }

    function hook_newsletter_users_statistics_time() {
        global $wpdb;
        include __DIR__ . '/users/statistics-time.php';
    }

    function hook_newsletter_statistics_index_map() {
        include __DIR__ . '/statistics/index-map.php';
    }

    function hook_newsletter_users_edit_newsletters($user_id) {
        global $wpdb;
        include __DIR__ . '/users/edit-newsletters.php';
    }

    function hook_site_transient_update_plugins($value) {
        if (!method_exists('Newsletter', 'set_extension_update_data')) {
            return $value;
        }

        return Newsletter::instance()->set_extension_update_data($value, $this);
    }

    var $country_result = '';

    function get_response_data($url) {
        $response = wp_remote_get($url);
        if (is_wp_error($response)) {
            return $response;
        } else if (wp_remote_retrieve_response_code($response) != 200) {
            return new WP_Error(wp_remote_retrieve_response_code($response), 'Error on connection to ' . $url . ' with error HTTP code ' . wp_remote_retrieve_response_code($response));
        }
        $data = json_decode(wp_remote_retrieve_body($response));
        if (!$data) {
            return new WP_Error(1, 'Unable to decode the JSON from ' . $url, $body);
        }
        return $data;
    }

    function resolve($ip) {
        static $service = 0;

        if ($service == 0) {
            $data = $this->get_response_data('http://geoip.nekudo.com/api/' . $ip);
            if (is_wp_error($data)) {
                $service++;
            } else {
                return $data->country->code;
            }
            //{
            //city: "Nuremberg",
            //country: {
            //name: "Germany",
            //code: "DE"
            //},
            //location: {
            //latitude: 49.4478,
            //longitude: 11.0683,
            //time_zone: "Europe/Berlin"
            //},
            //ip: "85.10.211.69"
            //}
        }

        if ($service == 1) {
            $data = $this->get_response_data('http://ip-api.com/json/' . $ip);
            if (is_wp_error($data)) {
                $service++;
            } else {
                return $data->countryCode;
            }
            //{
            //as: "AS14907 Wikimedia Foundation Inc.",
            //city: "San Francisco",
            //country: "United States",
            //countryCode: "US",
            //isp: "Wikimedia Foundation",
            //lat: 37.7898,
            //lon: -122.3942,
            //org: "Wikimedia Foundation",
            //query: "208.80.152.201",
            //region: "CA",
            //regionName: "California",
            //status: "success",
            //timezone: "America/Los_Angeles",
            //zip: "94105"
            //}
        }

        if ($service == 2) {
            $data = $this->get_response_data('http://www.freegeoip.net/json/' . $ip);
            if (is_wp_error($data)) {
                $service++;
            } else {
                return $data->country_code;
            }
        }

        return new WP_Error(1, 'No service for country resolution reachable');
    }

    function country($test = false) {
        global $wpdb;
        if (!$test) {
            $list = $wpdb->get_results("select id, ip from " . NEWSLETTER_STATS_TABLE . " where ip<>'' and country='' limit 50");
            $this->save_last_run(time());
        } else {
            $list = array();
            $list[] = new stdClass();
            $list[0]->ip = '85.10.211.69';
        }

        if (!empty($list)) {
            $this->country_result .= 'Processed ' . count($list) . ' statistic entries.';

            foreach ($list as $r) {
                $code = $this->resolve($r->ip);
                if (is_wp_error($code)) {
                    return $code;
                } else {
                    if (!$test) {
                        if (!empty($code)) {
                            $wpdb->query($wpdb->prepare("update " . NEWSLETTER_STATS_TABLE . " set country=%s where id=%d limit 1", $code, $r->id));
                        } else {
                            $wpdb->query($wpdb->prepare("update " . NEWSLETTER_STATS_TABLE . " set country='XX' where id=%d limit 1", $r->id));
                        }
                    }
                }
            }
        }

        if ($test) {
            return $code;
        }

        $list = $wpdb->get_results("select id, ip from " . NEWSLETTER_USERS_TABLE . " where ip<>'' and country='' limit 50");
        if (!empty($list)) {
            $this->country_result .= ' Processed ' . count($list) . ' subscribers.';
            foreach ($list as $r) {
                $code = $this->resolve($r->ip);
                if (is_wp_error($code)) {
                    return $code;
                } else {
                    if (!empty($code)) {
                        $wpdb->query($wpdb->prepare("update " . NEWSLETTER_USERS_TABLE . " set country=%s where id=%d limit 1", $code, $r->id));
                    } else {
                        $wpdb->query($wpdb->prepare("update " . NEWSLETTER_USERS_TABLE . " set country='XX' where id=%d limit 1", $r->id));
                    }
                }
            }
        }
    }

    function hook_admin_menu() {
        $newsletter = Newsletter::instance();
        $capability = ($newsletter->options['editor'] == 1) ? 'manage_categories' : 'manage_options';
        add_submenu_page(null, 'Reports', 'Reports', $capability, 'newsletter_reports_index', array($this, 'menu_page_index'));
        add_submenu_page(null, 'Report', 'Report', $capability, 'newsletter_reports_view', array($this, 'hook_newsletter_reports_view'));
        add_submenu_page(null, 'Users', 'Users', $capability, 'newsletter_reports_view_users', array($this, 'hook_newsletter_reports_view_users'));
        add_submenu_page(null, 'URLs', 'URLs', $capability, 'newsletter_reports_view_urls', array($this, 'hook_newsletter_reports_view_urls'));
        add_submenu_page(null, 'Retarget', 'Retarget', $capability, 'newsletter_reports_view_retarget', array($this, 'hook_newsletter_reports_view_retarget'));
    }

    function menu_page_index() {
        global $wpdb, $newsletter;
        require dirname(__FILE__) . '/index.php';
    }

    function hook_newsletter_reports_view() {
        global $wpdb, $newsletter;
        require dirname(__FILE__) . '/view.php';
    }

    function hook_newsletter_reports_view_users() {
        global $wpdb, $newsletter;
        require dirname(__FILE__) . '/view-users.php';
    }

    function hook_newsletter_reports_view_urls() {
        global $wpdb, $newsletter;
        require dirname(__FILE__) . '/view-urls.php';
    }

    function hook_newsletter_reports_view_retarget() {
        global $wpdb, $newsletter;
        require dirname(__FILE__) . '/view-retarget.php';
    }

    function hook_newsletter_statistics_view($page) {
        return 'newsletter_reports_view';
    }

    function save_last_run($time) {
        update_option($this->prefix . '_last_run', $time);
    }

    function get_last_run() {
        return get_option($this->prefix . '_last_run', 0);
    }

}

new NewsletterReports();
