<?php
/**
 * 
 */
global $wpdb;

if (empty($controls->data['type'])) {
    $countries = $wpdb->get_results("select country, count(*) as total from {$wpdb->prefix}newsletter_stats where country<>'' group by country order by total");
} else {
    $countries = $wpdb->get_results($wpdb->prepare("select s.country, count(*) as total from {$wpdb->prefix}newsletter_stats s join {$wpdb->prefix}newsletter_emails e on s.email_id=e.id where s.country<>'' and e.type=%s group by s.country order by total", $controls->data['type']));
}
?>

<p class="tnp-map-legend">The map shows in which countries<br>the newsletters have been opened.</p>

<?php if (empty($countries)) { ?> 
    <p class="tnp-map-legend">No data available, just wait some time to let the processor to work to resolve the countries. Thank you.</p>
<?php } else { ?>
    <div id="tnp-map-chart"></div>
    <?php
    $data = array();
    foreach ($countries as &$country) {
        $data[strtolower($country->country)] = $country->total;
    }
    ?>

    <script type="text/javascript">
        var world_data = <?php echo json_encode($data) ?>;
        jQuery(document).ready(function ($) {
            $('#tnp-map-chart').vectorMap({
                map: 'world_en',
                backgroundColor: null,
                color: '#ffffff',
                hoverOpacity: 0.7,
                selectedColor: '#666666',
                enableZoom: true,
                showTooltip: true,
                values: world_data,
                scaleColors: ['#C8EEFF', '#006491'],
                normalizeFunction: 'polynomial',
                onLabelShow: function (event, label, code) {
                    label.text(label.text() + ': ' + world_data[code]);
                }
            });
        });
    </script>
<?php } ?>