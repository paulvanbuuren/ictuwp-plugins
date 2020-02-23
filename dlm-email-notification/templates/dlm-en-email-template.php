<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
} // Exit if accessed directly
?>
<!DOCTYPE html>
<html>
<head lang="en">
    <meta charset="UTF-8">
    <style type="text/css">
        html, body { padding: 0; margin: 0; background: #F5F5F5;}
        body { padding: 25px; }
    </style>
</head>
<body>
<table cellpadding="0" cellspacing="0" border="0" align="center" width="500" style="font-family: Arial; font-size: 14px;">
    <tr>
        <td style="font-size: 16px; font-weight: bold; background-color: #459ac9; color: #fff; height: 50px; padding: 0 15px;-webkit-border-top-left-radius: 5px;-webkit-border-top-right-radius: 5px;-moz-border-radius-topleft: 5px;-moz-border-radius-topright: 5px;border-top-left-radius: 5px;border-top-right-radius: 5px;">
            New Download on %WEBSITE_URL%!
        </td>
    </tr>
    <tr>
        <td style="padding: 25px 15px;background: #fff;-webkit-border-bottom-right-radius: 5px;-webkit-border-bottom-left-radius: 5px;-moz-border-radius-bottomright: 5px;-moz-border-radius-bottomleft: 5px;border-bottom-right-radius: 5px;border-bottom-left-radius: 5px;">
            Hey there,<br/>
            <br/>
            There was a new download on your website!<br/>
            <br/>
            Download information:<br/>
            <br/>
            <table cellpadding="0" cellspacing="0" border="0">
	            <?php foreach ( $fields as $field ) : ?>
                <tr>
                    <td width="300" style="font-weight: bold;" valign="top"><?php echo $field['label']; ?>:</td>
                    <td valign="top"><?php echo $field['value']; ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
            <br/>
            That's all for now!<br/>
            </br>
            <em>~Your Download Monitor powered website</em>
        </td>
    </tr>
</table>
</body>
</html>