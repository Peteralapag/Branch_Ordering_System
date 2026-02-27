<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
define("MODULE_NAME", "Branch_Ordering_System");
require_once($_SERVER['DOCUMENT_ROOT']."/Modules/".MODULE_NAME."/class/Class.functions.php");
$branch = $_SESSION['branch_branch'];

$order_type = $_POST['order_type'];
$rowid = $_POST['rowid'];
$control_no = $_POST['control_no'];

$ajaxQuery = isset($_POST['querymode']) ? trim(htmlspecialchars($_POST['querymode'])) : 0;

$userlevel = $_SESSION['branch_userlevel'];
$function = new WMSFunctions;
$sqlQueryData = "SELECT * FROM wms_order_request WHERE request_id='$rowid'";
$dataResults = mysqli_query($db, $sqlQueryData);    
if ( $dataResults->num_rows > 0 ) 
{
	$i=0;
	while($DATAROW = mysqli_fetch_array($dataResults))  
	{
		$branch = $DATAROW['branch'];
		$mrs_no = $DATAROW['control_no'];
		$recipient = $DATAROW['recipient'];
		$statts = $DATAROW['status'];
		$trans_date = $DATAROW['trans_date'];
		$logistics = $DATAROW['logistics'];
		$order_received = $DATAROW['order_received'];
		$order_received_date = $DATAROW['order_received_date'];
		$order_preparing = $DATAROW['order_preparing'];
		$order_preparing_date = $DATAROW['order_preparing_date'];
	}
}
if($function->GetOrderStatus($mrs_no,"order_received",$db) == 1)
{
	$class_or = "order-circle";
	$icon_color_or = "color-orange";
	$bar_color_or = "bar-color";
} else {
	$class_or = "order-circle-gray";
	$icon_color_or = "icontext-color";
	$bar_color_or = "bar-color-gray";
}
if($function->GetOrderStatus($mrs_no,"order_preparing",$db) == 1)
{
	$class_prep = "order-circle";
	$icon_color_prep = "color-orange";
	$bar_color_prep = "bar-color";
} else {
	$class_prep = "order-circle-gray";
	$icon_color_prep = "icontext-color";
	$bar_color_prep = "bar-color-gray";
}
if($function->GetOrderStatus($mrs_no,"order_transit",$db) == 1)
{
	$class_trans = "order-circle";
	$icon_color_trans = "color-orange";
	$bar_color_trans = "bar-color";
} else {
	$class_trans = "order-circle-gray";
	$icon_color_trans = "icontext-color";
	$bar_color_trans = "bar-color-gray";
}
if($function->GetOrderStatus($mrs_no,"order_delivered",$db) == 1)
{
	$class_del = "order-circle";
	$icon_color_del = "color-orange";
	$bar_color_del = "bar-color-gray";
} else {
	$class_del = "order-circle-gray";
	$icon_color_del = "icontext-color";
	$bar_color_del = "bar-color-gray";
}
if($order_type == 0)
{
	$table = 'wms_branch_order';
}
else if($order_type == 1)
{
	$table = 'wms_branch_order_unlisted';
}
?>
<style>
.tracker-form {margin-bottom: 10px;white-space:nowrap}
.mrs-wrappers {width: 8.5in;border:1px solid #aeaeae;padding:0.2in;}
.form-footer td {text-align: center;font-size: 12px;}
.btn-thin {padding: 0 !important}
.approval-text {font-weight: 600;font-size:18px;}
.names {font-weight: 600;}
.dates {font-style:italic;color: #aeaeae;}
.button {padding: 2px 7px 2px 7px !important;}
.order-circle {display: flex;width:50px;height:50px;border:5px solid green;border-radius:50%;justify-content: center;align-items: center;font-size:24px}
.order-circle-gray {display: flex;width:50px;height:50px;border:5px solid #aeaeae;border-radius:50%;justify-content: center;align-items: center;font-size:24px}
.status-text {text-align:center;font-size: 11px}
.status-date {text-align:center;text-align:center;font-style:italic;color:#AEAEAE;font-size:14px}
.icontext-color { color: #aeaeae; }
.bar-color {border: 2px solid green}
.bar-color-gray {border: 2px solid #aeaeae}
.logistics-wrapper {font-size: 14px;}
.basket-data-form {overflow:hidden;}
.order-receive-button {text-align:center;}
.order-stats {width: 8.5in;border:1px solid #aeaeae;padding:5px;background:#fae8d4;margin-bottom: 10px;
border: 1px solid #ff8502;text-align:center;color:#333;border-radius: 5px;font-style:italic;font-size:14px
}
.basket-data-form {
    min-height: 100px;
    max-height: 400px;
    overflow: auto;
}
</style>
<div class="tracker-form">
	<?php if($logistics == 1) {?>
	<div class="mrs-wrappers" style="margin:0 auto;margin-bottom:10px">		
		<table style="width: 100%" class="logistics-wrapper">
			<tr>
				<th style="width:65px">Driver: </th>
				<td style="width:5px;">&nbsp;</td>
				<td><?php echo $function->GetOrderStatus($mrs_no,"delivery_driver",$db); ?></td>
				<td style="width:100px">&nbsp;</td>
				<th style="width:100px">Plate Number: </th>
				<td style="width:5px;">&nbsp;</td>
				<td style="width:150px;text-align:center"><?php echo $function->GetOrderStatus($mrs_no,"plate_number",$db); ?></td>
			</tr>
		</table>		
	</div>
	<?php } else {
		if($order_received == 1) {
			$stats_message = "Your order has been received and is now being prepared as of ". date("F d, Y", strtotime($order_received_date)).".";
		}
		else if($order_preparing == 1) {
			$stats_message = $recipient . " has started preparing your order on " . date("F d, Y", strtotime($order_preparing_date)) . ".";
		} else {
			if ($statts == 'Submitted') {
			    $stats_message = "Your order has been submitted and is awaiting receipt by " . $recipient;
			} 
			else if ($statts == 'Open') {
			    $stats_message = "Your order is still open, and you need to add items to the basket.";
			} 
			else if ($statts == 'Approval') {
			    $stats_message = "Your order is pending approval.";
			}
			else if ($statts == 'Void') {
				$stats_message = "This order has been <strong>Canceled/Void</strong>.";
			}
		}
	?>
	<div class="order-stats">
		<?php echo $stats_message;?>
	</div>
	<?php }?>
	<div class="mrs-wrappers" style="margin:0 auto">
		<table style="width: 100%;border-collapse:collapse" cellpadding="0" cellspacing="0">
			<tr>
				<td style="width:50px"></td>
				<td style="width:50px;">
					<div class="<?php echo $class_or; ?>"><i class="fa-solid fa-file-arrow-down <?php echo $icon_color_or; ?>"></i></div>
				</td>
				<td><div style="width:100%;height:1px" class="<?php echo $bar_color_or; ?>"></div></td>
				<td style="width:50px;">
					<div class="<?php echo $class_prep; ?>"><i class="fa-solid fa-cart-flatbed-boxes <?php echo $icon_color_prep; ?>"></i></div>
				</td>
				<td><div style="width:100%;height:1px" class="<?php echo $bar_color_prep; ?>"></div></td>
				<td style="width:50px;">
					<div class="<?php echo $class_trans; ?>"><i class="fa-solid fa-truck-fast <?php echo $icon_color_trans; ?>"></i></div>
				</td>
				<td><div style="width:100%;height:1px" class="<?php echo $bar_color_trans; ?>"></div></td>
				<td style="width:50px;">
					<div class="<?php echo $class_del; ?>"><i class="fa-solid fa-inbox-in <?php echo $icon_color_del; ?>"></i></div>
				</td>
				<td style="width:50px"></td>
			</tr>
			<tr>
				<td style="height:10px;"></td>
				<td style="height:10px;"></td>
				<td style="height:10px;"></td>
				<td style="height:10px;"></td>
				<td style="height:10px;"></td>
				<td style="height:10px;"></td>
				<td style="height:10px;"></td>
				<td style="height:10px;"></td>
				<td style="height:10px;"></td>
			</tr>
		</table>		
		<table style="width: 100%">
			<tr class="status-text">
				<td style="width:170px">Order Received</td>
				<td>&nbsp;</td>
				<td style="width:170px">Prepairing Order</td>
				<td>&nbsp;</td>
				<td style="width:170px">In Transit</td>
				<td>&nbsp;</td>
				<td style="width:170px">Order Delivered</td>
			</tr>
			<tr class="status-date">
				<td style="width:170px"><span><?php echo $function->GetOrderStatus($mrs_no,"order_received_date",$db); ?></span></td>
				<td>&nbsp;</td>
				<td style="width:170px"><span><?php echo $function->GetOrderStatus($mrs_no,"order_preparing_date",$db); ?></span></td>
				<td>&nbsp;</td>
				<td style="width:170px"><span><?php echo $function->GetOrderStatus($mrs_no,"order_transit_date",$db); ?></span></td>
				<td>&nbsp;</td>
				<td style="width:170px"><span><?php echo $function->GetOrderStatus($mrs_no,"order_delivered_date",$db); ?></span></td>
			</tr>
		</table>		
	</div>
	<div class="basket-data-form" id="basketdataform" style="margin-top:10px"></div>		
</div>
<script>
$(document).ready(function()
{
	var module = '<?php echo MODULE_NAME; ?>';
	var control_no = '<?php echo $control_no?>';
	$.post('./Modules/' + module + '/includes/order_tracker_details.php', { control_no: control_no }, function (data) {
		$('#basketdataform').html(data);
    });
});
function reloaderKo()
{
	var module = '<?php echo MODULE_NAME; ?>';
	var QUERYMODE = '<?php echo $ajaxQuery; ?>';
	var control_no = '<?php echo $control_no?>';
	$.post('./Modules/' + module + '/includes/order_tracker_details.php', { control_no: control_no }, function (data) {
		$('#basketdataform').html(data);
		if(QUERYMODE == 'openpending')
		{
			showPendingOrder();
		}
    });
}
</script>