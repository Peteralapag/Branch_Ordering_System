<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
define("MODULE_NAME", "Branch_Ordering_System");
require_once($_SERVER['DOCUMENT_ROOT']."/Modules/".MODULE_NAME."/class/Class.functions.php");
$branch = $_POST['branch'];
$control_no = $_POST['control_no'];
$function = new WMSFunctions;

$params = $function->getOrderRemarksExistence($control_no,$db);

$sqlQueryData = "SELECT * FROM wms_order_request WHERE control_no='$control_no' AND branch='$branch'";
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
		$form_type = $DATAROW['form_type'];
		$created_by = $DATAROW['created_by'];		
	}
}
if($form_type == 'POF')
{
	$FORM_TYPE = "PRODUCT ORDER FORM";
} else {
	$FORM_TYPE = "MATERIAL REQUISITION FORM";
}
?>
<style>
.basket-data-wrapper {display: flex;flex-direction: column;width: 100%;height: 100%;gap: 5px;}
.basket-data-label {padding: 5px;text-align: center;border: 1px solid orange;color:#fff;background: linear-gradient(to bottom, #fea11d, #b06d0e);border-radius: 0px 15px 0px 15px;font-size: 18px;font-weight: 600;}
.basket-data {flex: 1;border: 1px solid #cccccc;padding: 5px;overflow: auto;}
.basket-data input { border: 0;}
.basket-lamesa-ko th{background: #f5c683;color:#535353;}
.basket-lamesa-ko td {padding: 0 0 0 5px;vertical-align:middle;font-size: 14px;white-space:nowrap;}
.order-notifs {display: none;background: #fbf1e2;border: 1px solid #f9a226;padding: 5px;font-size: 14px;font-style:italic;color: #636363;text-align:center;border-radius: 3px;margin-top: 10px; }
.submit-button {text-align:right;}
</style>
<div class="basket-data-wrapper">
	<div class="basket-data-label">
		ORDER BASKET - <?php echo $branch?> - <?php echo $control_no?>
	</div>
	<div class="basket-data" id="basketdata">
		
		<table style="width: 100%" class="table table-bordered basket-lamesa-ko">
			<thead>
				<tr>
					<th style="text-align:center;font-weight:600;width:50px">#</th>
					<th style="width:100px">Item Code</th>
					<th style="text-align:center">Item Description</th>
					<th style="width:100px">Units (UOM)</th>
					<th style="width:100px">Quantity</th>
					<th style="width:100px">Inv. Ending</th>
					<th style="width:70px !important;text-align:center">Action</th>
				</tr>
			</thead>
			<tbody>
<?php
	$remarks_show = 0;	
	$sqlQueryData = "SELECT * FROM wms_branch_order WHERE branch='$branch' AND control_no='$mrs_no'";
	$dataResults = mysqli_query($db, $sqlQueryData);    
	if ( $dataResults->num_rows > 0 ) 
	{
		$remarks_show = 1;
		$submit_text = "";
		$x=0;
		while($DATAROW = mysqli_fetch_array($dataResults))  
		{
			$x++;
			$editid = $DATAROW['id'];
			$remarks = $DATAROW['remarks'];
			$remarks = $function->shortenText($remarks,20);
?>		
				<tr ondblclick="loadWarehouseEdit('edit','<?php echo $control_no; ?>','<?php echo $editid; ?>')">
					<td style="text-align:center;font-weight:600"><?php echo $x; ?></td>
					<td style="text-align:center"><?php echo $DATAROW['item_code']; ?></td>
					<td><?php echo $DATAROW['item_description']; ?></td>
					<td style="text-align:center"><?php echo $DATAROW['uom']; ?></td>
					<td style="text-align:center"><?php echo $DATAROW['quantity']; ?></td>
					<td style="text-align:center"><?php echo $DATAROW['inv_ending']." ". $DATAROW['inv_ending_uom']; ?></td>
					<td style="padding:0 !important"><button class="btn btn-primary btn-sm w-100" onclick="loadWarehouseEdit('edit','<?php echo $control_no; ?>','<?php echo $editid; ?>')"><i class="fa-solid fa-pen-to-square"></i> Edit</button></td>
				</tr>
<?php } } else { ?>
				<tr>
					<td colspan="7" style="text-align:center; padding:5px"><i class="fa fa-bell color-orange"></i> Basket is Empty.</td>
				</tr>
<?php } if($remarks_show == 1) { ?>
				<tr>
					<td colspan="7" style="border:0 !important;height:10px"></td>
				</tr>
				<tr>
					<td colspan="2" style="vertical-align:middle;text-align:center;padding:0 !important"><strong>Remarks: </strong> </td>
					<td colspan="4" style="padding:0 !important">
						<input id="remarks" type="text" class="form-control form-control-sm" placeholder="Enter your Notes/Remarks here" value="<?php echo $function->getOrderRemarks($control_no,$db)?>" autocomplete="off">
					</td>
					<td style="padding:0 !important">
						<button class="btn btn-secondary btn-sm w-100" onclick="saveRemarks('<?php echo $params?>')">Save</button>
					</td>
				</tr>			
<?php } ?>					
			</tbody>
		</table>
		<div class="submit-button">
		<?php if($remarks_show == 1) { ?>
			<button class="btn btn-primary" onclick="submitOrder('<?php echo $control_no?>')">Submit For Approval&nbsp;&nbsp;<i class="fa-solid fa-upload"></i></button>
		<?php } ?>
			<button class="btn btn-danger" onclick="voidRequest()">Void this Basket&nbsp;&nbsp;<i class="fa-solid fa-circle-stop"></i></button>			
		</div>
		<div class="order-notifs">
			You are not authorized to edit or modify this order. Please contact<strong> <?php echo $created_by ?> </strong>if you need any changes to the order basket.			
		</div>
	</div>
	<div class="submitresults"></div>
</div>
<script>
function voidRequest()
{
	var module = '<?php echo MODULE_NAME; ?>';
	var mode = "voidorderrequest";
	var rowid = '<?php echo $rowid; ?>';
	console.log(rowid);
	swal({
	    title: "Void Order",
	    text: "Are you sure you want to void this order?",
	    icon: "warning",
	    buttons: true,
	    dangerMode: true,
		}).then((willSubmit) => {
	    if (willSubmit)
	    {
		    rms_reloaderOn('Voiding Request...');
		    setTimeout(function () {
		        $.post("./Modules/" + module + "/actions/actions.php", { mode: mode, rowid: rowid }, function (data) {
		            $('.submitresults').html(data);
		            rms_reloaderOff();
		            swal.close(); // Manually close the SweetAlert after processing
		        });
		    }, 1000);
	    } else {
	        swal("Void order canceled!", {
	            icon: "info",
	        });
	    }
	});
}
function submitOrder(control_no)
{
	var module = '<?php echo MODULE_NAME; ?>';
	swal({
	    title: "Submit Order",
	    text: "Are you sure you want to finish and submit your order?",
	    icon: "warning",
	    buttons: true,
	    dangerMode: true,
		}).then((willSubmit) => {
	    if (willSubmit)
	    {
	        var mode = 'submitorder';
	        rms_reloaderOn("Submitting Order...");
	        setTimeout(function () {
	            $.post("./Modules/<?php echo MODULE_NAME; ?>/actions/actions.php", 
	            { mode: mode, control_no: control_no },
	            function (data) {
	                $('.submitresults').html(data);
	                rms_reloaderOff();
	            });
	        }, 1000);
	    } else {
	        swal("Order submission canceled!", {
	            icon: "info",
	        });
	    }
	});
}
function saveRemarks(params)
{
	var module = '<?php echo MODULE_NAME; ?>';
	var rowid = $('#rowid').val();
	var branch = '<?php echo $branch?>';
	var control_no = $('#control_no').val();
	var remarks = $('#remarks').val();	
	if(remarks === '')
	{
		return false;
	}
	if(params == 'add')
	{
		rms_reloaderOn("Saving...");
		var mode = 'saveremarks';		
	}
	if(params == 'edit')
	{
		rms_reloaderOn("Updating...");
		var mode = 'updateremarks';		
	}
	setTimeout(function()
	{
		$.post("./Modules/" + module + "/actions/save_order_remarks.php",
		{
			mode: mode,
			rowid: rowid,
			branch: branch,
			control_no: control_no,
			remarks: remarks,
		},
		function(data) {		
			$('.submitresults').html(data);
			rms_reloaderOff();
		});
	},500);
}
</script>
<?php
if($function->GetOrderCreatorName($control_no,$db) != ucwords(strtolower($function->GetMyRealName($_SESSION['branch_username'],$db))))
{
	if($_SESSION['branch_userlevel'] < 50)
	{
		echo '
			<script>
				$(".order-notifs").show();
				$(".basket-data-wrapper").find("input, select, textarea, button").prop("disabled", true);
			</script>
		';
	} else if($_SESSION['branch_userlevel'] >= 50) {
		
		echo '
			<script>
				$(".order-notifs").hide();
				$(".basket-data-wrapper").find("input, select, textarea, button").prop("disabled", false);
			</script>
		';
	}
} 
else if($function->GetOrderCreatorName($control_no,$db) == ucwords(strtolower($function->GetMyRealName($_SESSION['branch_username'],$db))))
{
	echo '
		<script>
			$(".order-notifs").hide();
			$(".basket-data-wrapper").find("input, select, textarea, button").prop("disabled", false);
		</script>
	';
}	
?>
