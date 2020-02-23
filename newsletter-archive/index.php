<?php

if (NEWSLETTER_VERSION < '4') {
    echo 'Newsletter version 4 is required';
    return;
}

require_once dirname(__FILE__) . '/controls.php';
$module = NewsletterArchive::$instance;
$controls = new NewsletterControls();

if (!$controls->is_action()) {
    $controls->data = $module->options;
} else {
    if ($controls->is_action('save')) {
        $module->save_options($controls->data);
        $controls->messages = 'Saved.';
    }
}
?>

<div class="wrap" id="tnp-wrap">
    <?php if (NEWSLETTER_VERSION > '4') @include NEWSLETTER_DIR . '/tnp-header.php' ?>
    <div id="tnp-heading">
        <h2>Newsletter Archive Extension</h2>

        <?php $controls->show(); ?>

        <p>
            This module lets to add an archive of the sent newsletter to your blog. Since a newesletter is a full
            web page, at the end, it will be shown inside a frame, eventually scrollable.<br>
            To start, create a WordPress page and add inside the single tag <code>[newsletter_archive]</code>. That's all.<br>
            Newsletters, by default, are not public, so you should mark them as public to be listed. This prevent the publishing
            of reserved newsletters.<br>
            Find more information
            on its <a href="http://www.thenewsletterplugin.com/plugins/newsletter/archive-module" target="_blank">official page</a>.
        </p>

    </div>

    <div id="tnp-body">
        <form action="" method="post">
            <?php $controls->init(); ?>

            <div id="tabs">
                <ul>
                    <li><a href="#tabs-general">General</a></li>
                    <li><a href="#tabs-help">Help</a></li>
                </ul>

                <div id="tabs-general">
                    <table class="form-table">
                        <tr valign="top">
                            <th>Show newsletter date?</th>
                            <td>
                                <?php $controls->checkbox('date'); ?>
                            </td>
                        </tr>
                    </table>

                </div>

                <div id="tabs-help">

                </div>

            </div>

            <p>
                <?php $controls->button('save', 'Save'); ?>

            </p>
        </form>
    </div>
    <?php if (NEWSLETTER_VERSION > '4') @include NEWSLETTER_DIR . '/tnp-footer.php' ?>
</div>