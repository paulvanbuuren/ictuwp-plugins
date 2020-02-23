<?php
global $wpdb;

$countries = $wpdb->get_results("select country, count(*) as total from " . NEWSLETTER_USERS_TABLE . " where status='C' and country<>'' group by country order by total");
?>

<p>A global overview of the geolocation data of your subscribers.</p>

<div id="country-chart" style="width:850px; height:400px"></div>

<script type="text/javascript" src="https://www.google.com/jsapi"></script>
<script type="text/javascript">

    google.load('visualization', '1.0', {'packages': ['corechart', 'geochart']});
    google.setOnLoadCallback(drawChart);

    function drawChart() {

<?php if (!empty($countries)) { ?>
            var countries = new google.visualization.DataTable();
            countries.addColumn('string', 'Country');
            countries.addColumn('number', 'Total');
    <?php foreach ($countries as &$country) { ?>
                countries.addRow(['<?php echo $country->country; ?>', <?php echo $country->total; ?>]);
    <?php } ?>

            var options = {'title': 'Country', 'width': '800px', 'height': '400px'};
            var chart = new google.visualization.GeoChart(document.getElementById('country-chart'));
            chart.draw(countries, options);
<?php } ?>
    }
</script>