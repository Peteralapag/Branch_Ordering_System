<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
define("MODULE_NAME", "Branch_Ordering_System");
require_once($_SERVER['DOCUMENT_ROOT']."/Modules/".MODULE_NAME."/class/Class.functions.php");
$branch = $_SESSION['branch_branch'];
$control_no = $_POST['control_no'];
$order_type = $_POST['ordertype'];
$userlevel = $_SESSION['branch_userlevel'];
$function = new WMSFunctions;
$sqlQueryData = "SELECT * FROM wms_order_request WHERE control_no='$control_no'";
$dataResults = mysqli_query($db, $sqlQueryData);    
if ( $dataResults->num_rows > 0 ) 
{
	$i=0;
	while($DATAROW = mysqli_fetch_array($dataResults))  
	{
		$rowid = $DATAROW['request_id'];
		$branch = $DATAROW['branch'];
		$mrs_no = $DATAROW['control_no'];
		$recipient = $DATAROW['recipient'];
		$trans_date = $DATAROW['trans_date'];
		$order_by = $DATAROW['created_by']; 
		if($DATAROW['checked_by'] != '' && $DATAROW['checked_date'] != '')
		{
			$checked_en = '';
		} else {
			$checked_en = 'disabled';
		}
	}
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
.lamesa-form-approval th,.lamesa-form-approval td {border: 0 !important;white-space:nowrap;font-size: 15px}
.approval-wrapper {width: 1000px; height: auto; max-height: 80vh; overflow:auto}
.lamesa-ng-items th,.lamesa-ng-items td {font-size: 14px !important;font-weight:normal;white-space:nowrap;}
.lamesa-ng-items th {padding: 3px 5px 3px 5px;background: #f1f1f1;text-align:center}
.approver-navs {display: flex;flex-wrap: nowrap;gap: 50px;}
.approver-navs > div {flex: 1;padding: 5px;text-align: center;font-size: 14px;}
.req-by,.chk-by,.apv-by {display: flex;flex-direction: column;justify-content: center;align-items: center;}
.approver-head,.approver-button,.approver-dates {width: 100%;}
.approver-head {margin-bottom: 20px;}
.approver-button {height: 29px !important;border-bottom: 1px solid #232323;margin-bottom: 5px;font-weight: 600;}
.approver-button button {width: 100%;padding: 2px 5px 2px 5px !important;}
.approver-dates {height: 25px;font-style:italic;color: #aeaeae;	}
.rejection-wrapper {text-align:right;margin-bottom: 10px;}
.rejection-approved {text-align:center;padding: 10px;border: 1px solid orange !important;border-radius:7px;background: #fef7e9;font-style: italic;color: #636363}
</style>
<div class="approval-wrapper">
	<table style="width: 100%" class="table lamesa-form-approval">
		<tr>
			<td style="width: 200px !important;padding-right:20px !important">Requesting Section/Branch: </td>
			<td style="border-bottom: 1px solid #232323 !important;padding-right: 20px !important"><?php echo $branch?></td>
			<td style="padding-right:20px;width:100px">Control No.</td>
			<td style="border-bottom: 1px solid #232323 !important;width: 170px;text-align:center;color:red;vertical-align:bottom !important"><?php echo $mrs_no?></td>
		</tr>
		<tr>
			<td style="width: 200px !important;padding-right:20px !important;text-align:right">Ordered By: </td>
			<td style="border-bottom: 1px solid #232323 !important;padding-right: 20px !important"><?php echo $order_by?></td>
			<td style="padding-right:20px;width:100px;text-align:right">Date:</td>
			<td style="border-bottom: 1px solid #232323 !important;width: 170px;text-align:center;vertical-align:bottom !important"><?php echo $trans_date;?></td>
		</tr>
	</table>	
	<table style="width: 100%" class="table table-bordered lamesa-ng-items">
		<tr>
			<th style="width:40px;text-align:center">#</th>
			<th style="width:80px">Item Code</th>
			<th>Description</th>
			<th style="width:100px">Units (UOM)</th>
			<th style="width:75px">Quantity</th>
			<th style="width:75px;">Inv. Ending</th>
		</tr>
<?php
	$sqlQueryData = "SELECT * FROM $table WHERE branch='$branch' AND control_no='$mrs_no' ORDER BY id DESC";
	$dataResults = mysqli_query($db, $sqlQueryData);    
	if ( $dataResults->num_rows > 0 ) 
	{
		$x=0;
		while($DATAROW = mysqli_fetch_array($dataResults))  
		{
			$x++;
			$editid = $DATAROW['id'];
			$remarks = $DATAROW['remarks'];
			$remarks = $function->shortenText($remarks,20);
?>		
		<tr>
			<td style="text-align:center"><?php echo $x; ?></td>
			<td style="text-align:center"><?php echo $DATAROW['item_code']; ?></td>
			<td><?php echo $DATAROW['item_description']; ?></td>
			<td style="text-align:center"><?php echo $DATAROW['uom']; ?></td>
			<td style="text-align:center"><?php echo $DATAROW['quantity']; ?></td>
			<td style="text-align:center"><?php echo $DATAROW['inv_ending']; ?></td>
		</tr>
<?php } ?>
		<tr>
			<td colspan="6" style="padding:5px;white-space:normal">
				<span style="font-weight:600">REMARKS:</span> <?php echo $function->getOrderRemarks($mrs_no,$db); ?>
			</td>
		</tr>
<?php } else { ?>		
		<tr>
			<td colspan="6" style="text-align:center">No Items</td>
		</tr>
<?php } ?>	
	</table>
<div class="approver-navs">
	<div class="req-by">
		<div class="approver-head">Prepared By:</div>
		<div class="approver-button"><?php echo $function->GetChecked($control_no,"created_by",$db); ?></div>
		<div class="approver-dates"><?php echo $function->GetChecked($control_no,"trans_date",$db) ?></div>
	</div>
	<div class="chk-by">
		<div class="approver-head">Reviewed/Checked By:</div>
		<div class="approver-button">
			<?php if($function->GetChecked($control_no,"checked",$db) == NULL) { ?>				
					<button class="btn btn-warning btn-thin btn-sm button color-white" onclick="Check_Accesss('<?php echo $control_no; ?>','p_approver',approveReview)">Approve?</button>
				<?php } else { echo $function->GetChecked($control_no,"checked_by",$db); } ?>
		</div>
		<div class="approver-dates"><?php echo $function->GetChecked($control_no,"checked_date",$db) ?></div>
	</div>
	<div class="apv-by">
		<div class="approver-head">Approved By:</div>
		<div class="approver-button">
			<?php if($function->GetChecked($control_no,"approved",$db) == NULL) { ?>
				<button class="btn btn-primary btn-thin btn-sm button" onclick="Check_Accesss('<?php echo $control_no; ?>','p_approver',aproveOrder)" <?php echo $checked_en; ?>>Approve?</button>
			<?php } else { echo $function->GetChecked($control_no,"approved_by",$db); } ?>
		</div>
		<div class="approver-dates"><?php echo $function->GetChecked($control_no,"approved_date",$db) ?></div>	
	</div>
</div>
<?php if($function->GetChecked($control_no,"checked",$db) == NULL || $function->GetChecked($control_no,"approved",$db) == NULL) { ?>
<hr>
<div class="rejection-wrapper">
	<button class="btn btn-success" onclick="returnToChange('<?php echo $control_no; ?>')"><i class="fa-solid fa-arrow-left"></i>&nbsp;&nbsp;Return to <?php echo $function->GetChecked($control_no,"created_by",$db); ?> For changes</button>
	<button class="btn btn-danger" onclick="voidOrderRequest('<?php echo $rowid; ?>')"><i class="fa-solid fa-ban"></i>&nbsp;&nbsp;Void Order Request</button>
</div>
<?php } else { ?>
<div class="rejection-wrapper rejection-approved">
	<span>This order was approved on
		<?php echo date("F d, Y ", strtotime($function->GetChecked($control_no,"approved_date",$db))) ?>
	</span>
</div>
<?php } ?>
<div class="results"></div>
<script>
function voidOrderRequest(rowid)
{
	var mode = 'voidorderrequest';
    swal({
        title: "Are you sure?",
        text: "Once void, you will not be able to recover this item!",
        icon: "warning",
        buttons: ["Cancel", "Yes, Void it!"],
        dangerMode: true,
    }).then((willVoid) => {
    if (willVoid)
    {
		rms_reloaderOn("Voiding Order Request!...");
		setTimeout(function()
		{
			$.post("./Modules/<?php echo MODULE_NAME; ?>/actions/actions.php", { mode: mode, rowid: rowid },
			function(data) {		
				$('.results').html(data);
				rms_reloaderOff();
			});
		},1000);
	}
	});
}
function returnToChange(controlno)
{
	var mode = 'sendingbackrequest';
	rms_reloaderOn("Sending Back...");
	setTimeout(function()
	{
		$.post("./Modules/<?php echo MODULE_NAME; ?>/actions/actions.php", { mode: mode, control_no: controlno },
		function(data) {		
			$('.results').html(data);
			rms_reloaderOff();
		});
	},1000);

}
function aproveOrder(controlno)
{
	var mode = 'approveorder';
	var order_type = '<?php echo $order_type?>';
	var userlevel = '<?php echo $userlevel; ?>';
	if(userlevel >= 50)
	{		
		rms_reloaderOn("Approving...");
		setTimeout(function()
		{
			$.post("./Modules/<?php echo MODULE_NAME; ?>/actions/actions.php", { mode: mode, control_no: controlno, order_type: order_type },
			function(data) {		
				$('.results').html(data);
				rms_reloaderOff();
			});
		},1000);
	} else {
		swal("Un-Authorized", "You have unsificient priviledge.", "warning");
	}
}
function approveReview(controlno)
{
	var mode = 'approvecheckreview';
	var order_type = '<?php echo $order_type?>';
	var userlevel = '<?php echo $userlevel; ?>';
	if(userlevel >= 10 || userlevel <= 50 || userlevel >= 80)
	{		
		rms_reloaderOn("Approving...");
		setTimeout(function()
		{
			$.post("./Modules/<?php echo MODULE_NAME; ?>/actions/actions.php", { mode: mode, control_no: controlno, order_type: order_type },
			function(data) {		
				$('.results').html(data);
				rms_reloaderOff();
			});
		},1000);
	} else {
		swal("Un-Authorized", "You have unsificient priviledge.", "warning");
	}
}
/*function orderApproval(controlno,ordertype)
{
	var module = '<?php echo MODULE_NAME; ?>';
	$.post("./Modules/" + module + "/includes/mrs_order_approval_data.php", { control_no: controlno,ordertype:ordertype },
	function(data) {		
		$('#smnavdata').html(data);
	});	
} */
</script>
<script src="../Modules/<?php echo MODULE_NAME; ?>/scripts/script.js"></script>
<?php mysqli_close($db); ?>