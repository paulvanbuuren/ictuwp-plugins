<?php
global $wpdb;
$months = $wpdb->get_results("select count(*) as c, concat(year(created), '-', date_format(created, '%m')) as d from " . NEWSLETTER_USERS_TABLE . " where status='C' group by concat(year(created), '-', date_format(created, '%m')) order by d desc limit 24");
$days = $wpdb->get_results("select count(*) as c, date(created) as d from " . NEWSLETTER_USERS_TABLE . " where status='C' group by date(created) order by d desc limit 30");

$m = array();
for ($i = 30; $i >= 0; $i--) {
    $m[date("Y-m", strtotime('-' . $i . ' months'))] = 0;
}

foreach ($months as $day) {
    if (!isset($m[$day->d]))
        continue;
    $m[$day->d] = $day->c;
}
$d = array();
for ($i = 30; $i >= 0; $i--) {
    $d[date("Y-m-d", strtotime('-' . $i . ' days'))] = 0;
}

foreach ($days as $day) {
    if (!isset($d[$day->d]))
        continue;
    $d[$day->d] = $day->c;
}
?>

<h3>Last 24 months</h3>
<div id="tnp-months-chart" style="width: 850px; height: 350px"></div>

<h3>Last 30 days</h3>
<div id="tnp-days-chart" style="width: 850px; height: 350px"></div>

<script>
    google.charts.setOnLoadCallback(drawTimeChart);

    function drawTimeChart() {
        var months = new google.visualization.DataTable();
        months.addColumn('string', 'Month');
        months.addColumn('number', 'Subscribers');

<?php foreach ($m as $date => $count) { ?>
            months.addRow(['<?php echo $date ?>', <?php echo $count ?>]);
<?php } ?>

        var options = {'title': 'By month'};

        var chart = new google.visualization.LineChart(document.getElementById('tnp-months-chart'));
        chart.draw(months, options);

        var days = new google.visualization.DataTable();
        days.addColumn('string', 'Date');
        days.addColumn('number', 'Subscribers');

<?php foreach ($d as $date => $count) { ?>
            days.addRow(['<?php echo $date ?>', <?php echo $count ?>]);
<?php } ?>

        var options = {'title': 'By day'};

        var chart = new google.visualization.LineChart(document.getElementById('tnp-days-chart'));
        chart.draw(days, options);
    }
</script>

<h3>Tabular format</h3>
<div class="row">
    <div class="col-md-6">
        <table class="widefat" style="width: 300px">
            <thead>
                <tr valign="top">
                    <th>Date</th>
                    <th>Subscribers</th>
                </tr>
            </thead>
<?php foreach ($days as $day) { ?>
                <tr valign="top">
                    <td><?php echo $day->d; ?></td>
                    <td><?php echo $day->c; ?></td>
                </tr>
<?php } ?>
        </table>
    </div>
    <div class="col-md-6">
        <table class="widefat" style="width: 300px">
            <thead>
                <tr valign="top">
                    <th>Date</th>
                    <th>Subscribers</th>
                </tr>
            </thead>
<?php foreach ($months as &$day) { ?>
                <tr valign="top">
                    <td><?php echo $day->d; ?></td>
                    <td><?php echo $day->c; ?></td>
                </tr>
<?php } ?>
        </table>
    </div>
</div>