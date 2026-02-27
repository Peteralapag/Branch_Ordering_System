<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
define("MODULE_NAME", "Branch_Ordering_System");
require_once($_SERVER['DOCUMENT_ROOT']."/Modules/".MODULE_NAME."/class/Class.functions.php");
$branch = $_SESSION['branch_branch'];
$control_no = $_POST['control_no'];
$function = new WMSFunctions;
$sqlQueryData = "SELECT * FROM wms_order_request WHERE control_no='$control_no'";
$dataResults = mysqli_query($db, $sqlQueryData);    
if ( $dataResults->num_rows > 0 ) 
{
	$i=0;
	while($DATAROW = mysqli_fetch_array($dataResults))  
	{
		$rowid = $DATAROW['request_id'];
		$order_type = $DATAROW['order_type'];
		$branch = $DATAROW['branch'];
		$mrs_no = $DATAROW['control_no'];
		$recipient = $DATAROW['recipient'];
		$trans_date = $DATAROW['trans_date'];
		$form_type = $DATAROW['form_type'];
		$order_transit = $DATAROW['order_transit'];
		$created_by = $DATAROW['created_by'];
		$logistics = $DATAROW['logistics'];
		$order_delivered = $DATAROW['order_delivered'];
		if($DATAROW['order_delivered_date'] != NULL && $DATAROW['order_delivered_date'] != '')
		{
			$order_delivered_date = "<strong>".date("F d, Y @ h:i A", strtotime($DATAROW['order_delivered_date']))."</strong>";
		} else {
			$order_delivered_date ='';
		}
		
	}
}
if($order_type == 0) {
	$table = 'wms_branch_order';
}
else if($order_type == 1) {
	$table = 'wms_branch_order_unlisted';
} else {
	$table = 'wms_branch_order';
}
?>
<style>
.detalye-order th, .detalye-order td {border: 1px solid #aeaeae !important;padding: 5px;font-size: 12px;}
.detalye-order th {background: #fb7701;text-align:center;color: #fff;}
.table-thth td {background: dodgerblue !important;text-align:center;color: #fff;font-size: 16px;font-weight: 600}
.item-received {font-size: 18px;}
.item-received-not {font-size: 18px;cursor: pointer;}
.item-received-not:hover {color:red;}
.inptactqty {box-sizing: border-box;height:28px;width:100%;outline: none;border:0;text-align:center;background:#fbf5e2;}
.received-message {border: 1px solid #b59845;padding: 10px;background:#f9f2dd;color: gray;font-style:italic}
</style>
<div class="order-details-wrapper">	
	<table style="width: 100%" class="table table-bordered detalye-order">
		<thead>
			<tr class="table-thth">
				<td colspan="8">ITEM RECEIPT</td>
			</tr>
			<tr>
				<th style="width:50px; text-align:center">#</th>
				<th style="width:100px">Item Code</th>
				<th>Item Description</th>
				<th style="width:100px">Units (UOM)</th>
				<th style="width:75px">Quantity</th>
				<th style="width:75px;">W.H. Qty</th>
				<th style="width:75px;">Act. Qty</th>
				<th style="width:75px;">Recvd</th>
			</tr>
		</thead>
		<tbody>
<?php
	$x=0;$item_receipts=0;
	$user_branch = $_SESSION['branch_branch'];
	$sqlQueryData = "SELECT * FROM $table WHERE branch='$branch' AND control_no='$mrs_no'";
	$dataResults = mysqli_query($db, $sqlQueryData);    
	if ( $dataResults->num_rows > 0 ) 
	{
		while($DATAROW = mysqli_fetch_array($dataResults))  
		{
			$x++;
			$rowid = $DATAROW['id'];
			$remarks = $DATAROW['remarks'];
			$item_receipt = $DATAROW['item_receipt'];
			$remarks = $function->shortenText($remarks,20);
			if($item_receipt == 1)
			{
				$act_qty = 'disabled';
			}
			else if($item_receipt == 0)
			{
				$act_qty = '';
			}
			
			$item_receipts += $item_receipt;
			
?>		
		<tr>
			<td style="text-align:center"><?php echo $x; ?></td>
			<td style="text-align:center"><?php echo $DATAROW['item_code']; ?></td>
			<td><?php echo $DATAROW['item_description']; ?></td>
			<td style="text-align:center"><?php echo $DATAROW['uom']; ?></td>
			<td style="text-align:center"><?php echo $DATAROW['quantity']; ?></td>
			<td style="text-align:center"><?php echo $DATAROW['wh_quantity']; ?></td>
			<td style="text-align:center;padding:0 !important;position:relative !important" class="receiptactions">
				<input id="actual_qty<?php echo $x; ?>" <?php echo $act_qty; ?> class="inptactqty" type="text" value="<?php echo $DATAROW['actual_quantity']; ?>">
			</td>
			<td style="padding: 0 !important;text-align:center;vertical-align:middle" class="receiptactions">
			<?php
			if($item_receipt == 0) {
			    if ($user_branch == $branch) {
			        // User is allowed to mark as received
			        echo '<i id="recno' . $x . '" class="fa-regular fa-circle item-received-not" onclick="receiptThis(\'' . $rowid . '\',\'' . $x . '\')"></i>';
			    } else {
			        // User is NOT allowed, show non-interactive icon
			        echo '<i id="recno' . $x . '" class="fa-regular fa-circle item-received-not" style="color:#ccc;cursor:not-allowed;" title="You are not allowed to receive items for this branch."></i>';
			    }
			} else {
			    echo '<i id="recyes' . $x . '" class="fa-solid fa-circle-check item-received color-green"></i>';
			}
			?>
			</td>
		</tr>
<?php } ?>
		<tr>
			<td>REMARKS: </td>
			<td colspan="7" style="padding:5px;white-space:normal">
				<?php echo $function->getOrderRemarks($mrs_no,$db); ?>
			</td>
		</tr>
<?php } else { ?>		
		<tr>
			<td colspan="8" style="text-align:center"><i class="fa fa-bell color-orange"></i>&nbsp; No Items found</td>
		</tr>
<?php } ?>		
		</tbody>
	</table>
	<?php if($logistics == 1)
	{
		if($order_delivered == 1)
		{
	?>		
		<div class="order-receive-button received-message">
			Order was received and closed on <?php echo $order_delivered_date; ?>.
		</div>
	<?php } 
		if($order_transit == 1)
		{
			if($order_delivered == 0)
			{
		?>
		<div class="order-receive-button">
			<button class="btn btn-primary" onclick="receivedOrder('<?php echo $x?>','<?php echo $control_no?>')"><i class="fa-solid fa-inbox-in"></i>&nbsp; Receive Closed Order</button>
		</div>
	<?php
			} 
		}
	} 
	?>
</div>
<div id="recresults"></div>
<script>
function receiptThis(rowid,eid)
{
	var module = '<?php echo MODULE_NAME; ?>';
	var item_receipt = '<?php echo $order_transit?>';
	if(item_receipt == 0)
	{
		
		swal("Action Denied", "This order is still being processed and cannot be received at this time.", "error");
		return false;
	} else {
		$('#recno' + eid).removeClass("fa-regular fa-circle item-received-not");
		$('#recno' + eid).addClass("fa-solid fa-circle-check item-received color-green");
		var actual_qty = $('#actual_qty' + eid).val()
		var mode = 'itemreceiptsave';
		$.post("./Modules/" + module + "/actions/actions.php", { mode: mode, rowid: rowid, actual_qty: actual_qty },
		function(data) {		
			$('#recresults').html(data);
		});
	}
}
function receivedOrder(x,control_no)
{
	var module = '<?php echo MODULE_NAME; ?>';	
    let receivedCount = '<?php echo $item_receipts?>';    
    if (receivedCount === x)
    {
    	rms_reloaderOn('Closing Order...');
    	setTimeout(function()
    	{
	    	var mode = 'recieveddelivery';    	
			$.post("./Modules/" + module + "/actions/actions.php", { mode: mode, control_no: control_no },
			function(data) {		
				$('#recresults').html(data);
				rms_reloaderOff();
			});
		},1000);
    } else {
        swal("Receiving Failed", "Please ensure all required items are marked as checked before proceeding.", "error");
    }
}
$(document).ready(function() {
	var logistics = '<?php echo $logistics?>';
	if(logistics == 0)
	{
	   $('.receiptactions input').prop('disabled', true);
	}

});
</script>