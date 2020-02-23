<?php
if (NEWSLETTER_VERSION < '4') {
    echo 'Newsletter version 4 is required';
    return;
}

if (isset($_GET['id'])) {
    include dirname(__FILE__) . '/edit.php';
    return;
}

global $controls;
require_once dirname(__FILE__) . '/controls.php';
$module = NewsletterCF7::$instance;
$controls = new NewsletterControls();

if (!$controls->is_action()) {
    $controls->data = $module->options;
} else {
    if ($controls->is_action('save')) {
        $module->save_options($controls->data);
        $controls->messages = 'Saved.';
    }
}

$forms = get_posts(array('post_type' => 'wpcf7_contact_form', 'posts_per_page' => 100));

$active = class_exists('WPCF7_ContactForm');
if (!$active) {
    $controls->errors = 'Contact Form 7 is not active.';
}
?>

<div class="wrap" id="tnp-wrap">
    <?php @include NEWSLETTER_DIR . '/tnp-header.php' ?>

    <div id="tnp-heading">
        <h2>Newsletter CF7 Extension</h2>

        <?php $controls->show(); ?>

        <p>
            See the <a href="http://www.thenewsletterplugin.com/plugins/newsletter/contact-form-7-extension" target="_blank">official documentation</a>
            to correctly configure your Contact Form 7 forms.
        </p>
        <p>
            Below the lits of your Contact Form 7 forms you can bind to Newsletter.
        </p>
    </div>

    <div id="tnp-body">
        <form action="" method="post">
            <?php $controls->init(); ?>

            <table class="widefat" style="width: auto">
                <thead>
                    <tr>
                        <th>Id</th>
                        <th><?php _e('Title', 'newsletter-cf7') ?></th>
                        <th>&nbsp;</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($forms as $form) { ?>
                        <tr>
                            <td>
                                <?php echo esc_html($form->ID) ?>
                            </td>
                            <td>
                                <?php echo esc_html($form->post_title) ?>
                            </td>
                            <td>
                                <?php if ($active) { ?>
                                <a class="button" href="?page=newsletter_cf7_index&id=<?php echo $form->ID ?>">Configure</a>
                                <?php } else { ?>
                                <button class="button" onclick="alert('Contact Form 7 must be active to configure the integration'); return false;">Configure</button>
                                <?php } ?>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>

        </form>
    </div>
    <?php @include NEWSLETTER_DIR . '/tnp-footer.php' ?>

</div>