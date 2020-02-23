<?php
/**
 * 
 */
global $wpdb;

$user = Newsletter::instance()->get_user($id);
// Total email sent to this subscriber
$total_sent = $wpdb->get_var($wpdb->prepare("select count(*) from " . $wpdb->prefix . "newsletter_sent where user_id=%d", $id));
$open_count = $wpdb->get_var($wpdb->prepare("select count(distinct email_id) from " . NEWSLETTER_STATS_TABLE . " where user_id=%d", $id));
$click_count = $wpdb->get_var($wpdb->prepare("select count(distinct email_id) from " . NEWSLETTER_STATS_TABLE . " where user_id=%d and url<>''", $id));
?>

<div class="row">

    <div class="col-md-3">
    
    <div style="text-align: center;">
        <img src="http://www.gravatar.com/avatar/<?php echo md5($user->email) ?>?s=250">
	</div>
    </div>

                <div class="col-md-9">
                    <!-- START Statistics -->
                    <div class="tnp-widget" style="height: 250px;">

                        <!--<h3>Subscribers Reached <a href="admin.php?page=newsletter_reports_view_users&id=<?php echo $email->id ?>">Details</a> 
			<a href="admin.php?page=newsletter_reports_view_retarget&id=<?php echo $email->id ?>">Retarget</a></h3>-->
                        <div class="inside">
                            <div id="tnp-rates-chart" style="width: 550px; height: 170px; margin: 0 auto;"></div>
                            <?php
                            $rates_data = array();
                            $rates_data[] = array('Open and Click', 'Rate');
                            $rates_data[] = array('No Action', $total_sent - $open_count);
			    if ($open_count - $click_count > 0) {
				$rates_data[] = array('Only Open', $open_count - $click_count);
			    }
			    if ($click_count > 0) {
				$rates_data[] = array('Open and Click', $click_count);
			    }
                            ?>
                            <script>
                                google.charts.setOnLoadCallback(drawRatesChart);

                                function drawRatesChart() {

                                    var rates_data = google.visualization.arrayToDataTable(<?php echo json_encode($rates_data) ?>);
                                    var options = {
                                        title: 'Open and Click',
                                        //is3D: true,
                                        pieHole: 0.3,
                                        backgroundColor: '#ffffff',
                                        colors: ['#E67E22', '#2980B9', '#27AE60']
                                    };

                                    var chart = new google.visualization.PieChart(document.getElementById('tnp-rates-chart'));
                                    chart.draw(rates_data, options);
                                }
                            </script>

                            <div class="row">
                                <div class="col-md-3">
                                    <div class="tnp-data">
                                            <div class="tnp-data-title">Sent</div>
                                            <div class="tnp-data-value"><?php echo $total_sent; ?></div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="tnp-data">
                                        <div class="tnp-data-title">Open or Click</div>
                                        <div class="tnp-data-value"><?php echo $open_count; ?> (<?php echo percent($open_count, $total_sent); ?>)</div>
                                    </div>

                                </div>
                                <div class="col-md-3">
                                    <div class="tnp-data">
                                        <div class="tnp-data-title">Only Open</div>
                                        <div class="tnp-data-value"><?php echo $open_count - $click_count; ?> (<?php echo percent($open_count - $click_count, $total_sent); ?>)</div>
                                    </div>

                                </div>
                                <div class="col-md-3">
                                    <div class="tnp-data">
                                        <div class="tnp-data-title">Open and Click</div>
                                        <div class="tnp-data-value"><?php echo $click_count; ?> (<?php echo percent($click_count, $total_sent); ?>)</div>
                                    </div>
                                </div>
                            </div>

                        </div>

                    </div>
                </div>
		
	
		</div>
