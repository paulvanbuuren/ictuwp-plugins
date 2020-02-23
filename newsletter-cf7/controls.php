<?php

class NewsletterControls {

    var $data;
    var $action = false;

    function __construct($options = null) {
        if ($options == null) {
            if (isset($_POST['options'])) {
                $this->data = stripslashes_deep($_POST['options']);
            }
        } else {
            $this->data = $options;
        }

        if (isset($_REQUEST['act'])) {
            $this->action = $_REQUEST['act'];
        }
    }

    /**
     * Return true is there in an asked action is no action name is specified or
     * true is the requested action matches the passed action.
     * Dies if it is not a safe call.
     */
    function is_action($action = null) {
        if ($action == null)
            return $this->action != null;
        if ($this->action == null)
            return false;
        if ($this->action != $action)
            return false;
        if (check_admin_referer('save'))
            return true;
        die('Invalid call');
    }

    function get_value($name) {
        if (!isset($this->data[$name]))
            return null;
        return $this->data[$name];
    }

    /**
     * Show the errors and messages.
     */
    function show() {
        static $shown = false;

        if ($shown)
            return;
        $shown = true;
        if (!empty($this->errors)) {
            echo '<div class="error">';
            echo $this->errors;
            echo '</div>';
        }
        if (!empty($this->messages)) {
            echo '<div class="updated"><p>';
            echo $this->messages;
            echo '</p></div>';
        }
    }

    /**
     * Creates a set of checkboxes named $name_[preference number] (so they are
     * distinct fields).
     * Empty preferences are skipped.
     */
    function preferences($name = 'preferences') {
        $options_profile = get_option('newsletter_profile');
        echo '<div class="newsletter-preferences-group">';

        for ($i = 1; $i <= NEWSLETTER_LIST_MAX; $i++) {
            if (empty($options_profile['list_' . $i]))
                continue;
            echo '<div class="newsletter-preferences-item">';
            $this->checkbox($name . '_' . $i, esc_html($options_profile['list_' . $i]));
            echo '</div>';
        }
        echo '<div style="clear: both"></div>';
        echo '</div>';
        echo '<div class="hints" style="margin-top: 10px">';
        echo '<small>User\'s preferences can be activated from the "Subscription Form" panel.<br>'
        . 'They can be used to simulate lists or create private groups. The number is the "preference number".<br>';
        echo '<a href="http://www.thenewsletterplugin.com/plugins/newsletter/newsletter-preferences" target="_blank">Read more about preferences</a>.</small>';
        echo '</div>';
    }

    function yesno($name) {
        $value = isset($this->data[$name]) ? (int) $this->data[$name] : 0;

        echo '<select style="width: 60px" name="options[' . $name . ']">';
        echo '<option value="0"';
        if ($value == 0)
            echo ' selected';
        echo '>No</option>';
        echo '<option value="1"';
        if ($value == 1)
            echo ' selected';
        echo '>Yes</option>';
        echo '</select>&nbsp;&nbsp;&nbsp;';
    }

    function enabled($name) {
        $value = isset($this->data[$name]) ? (int) $this->data[$name] : 0;

        echo '<select style="width: 100px" name="options[' . $name . ']">';
        echo '<option value="0"';
        if ($value == 0)
            echo ' selected';
        echo '>Disabled</option>';
        echo '<option value="1"';
        if ($value == 1)
            echo ' selected';
        echo '>Enabled</option>';
        echo '</select>';
    }

    function select($name, $options, $first = null) {
        $value = $this->get_value($name);

        echo '<select id="options-' . $name . '" name="options[' . $name . ']">';
        if (!empty($first)) {
            echo '<option value="">' . htmlspecialchars($first) . '</option>';
        }
        foreach ($options as $key => $label) {
            echo '<option value="' . $key . '"';
            if ($value == $key)
                echo ' selected';
            echo '>' . htmlspecialchars($label) . '</option>';
        }
        echo '</select>';
    }

    function value($name) {
        echo htmlspecialchars($this->data[$name]);
    }

    function value_date($name, $show_remaining) {
        $time = $this->get_value($name);

        echo gmdate(get_option('date_format') . ' ' . get_option('time_format'), $time + get_option('gmt_offset') * 3600);
        $delta = $time - time();
        if ($show_remaining && $delta > 0) {
            echo 'Remaining: ';
            $delta = $time - time();
            $days = floor($delta / (24 * 3600));
            $delta = $delta - $days * 24 * 3600;
            $hours = floor($delta / 3600);
            $delta = $delta - $hours * 3600;
            $minutes = floor($delta / 60);

            if ($days > 0)
                echo $days . ' days ';
            echo $hours . ' hours ';
            echo $minutes . ' minutes ';
        }
    }

    function text($name, $size = 20, $placeholder = '') {
        $value = $this->get_value($name);
        echo '<input placeholder="' . htmlspecialchars($placeholder) . '" name="options[' . $name . ']" type="text" size="' . $size . '" value="';
        echo htmlspecialchars($value);
        echo '"/>';
    }

    function text_email($name, $size = 40) {
        $value = $this->get_value($name);
        echo '<input name="options[' . $name . ']" type="email" placeholder="Valid email address" size="' . $size . '" value="';
        echo htmlspecialchars($value);
        echo '"/>';
    }

    function text_url($name, $size = 40) {
        $value = $this->get_value($name);
        echo '<input name="options[' . $name . ']" type="url" placeholder="http://..." size="' . $size . '" value="';
        echo htmlspecialchars($value);
        echo '"/>';
    }

    function hidden($name) {
        $value = $this->get_value($name);
        echo '<input name="options[' . $name . ']" type="hidden" value="';
        echo htmlspecialchars($value);
        echo '"/>';
    }

    function button($action, $label, $function = null) {
        if ($function != null) {
            echo '<input class="button-primary" type="button" value="' . $label . '" onclick="this.form.act.value=\'' . $action . '\';' . htmlspecialchars($function) . '"/>';
        } else {
            echo '<input class="button-primary" type="button" value="' . $label . '" onclick="this.form.act.value=\'' . $action . '\';this.form.submit()"/>';
        }
    }

    function button_primary($action, $label, $function = null) {
        if ($function != null) {
            echo '<input class="button-primary" type="button" value="' . $label . '" onclick="this.form.act.value=\'' . $action . '\';' . htmlspecialchars($function) . '"/>';
        } else {
            echo '<input class="button-primary" type="button" value="' . $label . '" onclick="this.form.act.value=\'' . $action . '\';this.form.submit()"/>';
        }
    }

    function button_confirm($action, $label, $message = '', $data = '') {
        if (empty($message)) {
            echo '<input class="button-secondary" type="button" value="' . $label . '" onclick="this.form.btn.value=\'' . $data . '\';this.form.act.value=\'' . $action . '\';this.form.submit()"/>';
        } else {
            echo '<input class="button-secondary" type="button" value="' . $label . '" onclick="this.form.btn.value=\'' . $data . '\';this.form.act.value=\'' . $action . '\';if (confirm(\'' .
            htmlspecialchars($message) . '\')) this.form.submit()"/>';
        }
    }

    function textarea($name, $width = '100%', $height = '50') {
        echo '<textarea class="dynamic" name="options[' . $name . ']" wrap="off" style="width:' . $width . ';height:' . $height . '">';
        echo htmlspecialchars($this->data[$name]);
        echo '</textarea>';
    }

    function checkbox($name, $label = '') {
        if ($label != '')
            echo '<label>';
        echo '<input type="checkbox" id="' . $name . '" name="options[' . $name . ']" value="1"';
        if (!empty($this->data[$name]))
            echo ' checked="checked"';
        echo '/>';
        if ($label != '')
            echo '&nbsp;' . $label . '</label>';
    }

    function init() {
        echo '<script type="text/javascript">
    jQuery(document).ready(function(){
        jQuery("textarea.dynamic").focus(function() {
            jQuery("textarea.dynamic").css("height", "50px");
            jQuery(this).css("height", "400px");
        });
        jQuery("#tabs").tabs();
    });
</script>
';
        echo '<input name="act" type="hidden" value=""/>';
        echo '<input name="btn" type="hidden" value=""/>';
        wp_nonce_field('save');
    }

    static function print_date($time = null, $now = false, $left = false) {
        if (is_null($time)) {
            $time = time();
        }
        if ($time == false) {
            $buffer = 'none';
        } else {
            $buffer = gmdate(get_option('date_format') . ' ' . get_option('time_format'), $time + get_option('gmt_offset') * 3600);
        }
        if ($now) {
            $buffer .= ' (now: ' . gmdate(get_option('date_format') . ' ' .
                            get_option('time_format'), time() + get_option('gmt_offset') * 3600);
            $buffer .= ')';
        }
        if ($left) {
            $buffer .= ', ' . gmdate('H:i:s', $time - time()) . ' left';
        }
        return $buffer;
    }

}

?>
