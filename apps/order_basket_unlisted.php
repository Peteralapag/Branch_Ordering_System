<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
define("MODULE_NAME", "Branch_Ordering_System");
require_once($_SERVER['DOCUMENT_ROOT']."/Modules/".MODULE_NAME."/class/Class.functions.php");
$control_no = $_POST['control_no'];
$branch = $_SESSION['branch_branch'];
$function = new WMSFunctions;
$creator = $_SESSION['branch_appnameuser'];
$order_type = 1;
$formdisabled = $function->getOrderCreator($creator,$control_no,$db);
$sqlQuery = "SELECT * FROM wms_order_request WHERE control_no='$control_no'";
$results = mysqli_query($db, $sqlQuery);    
if ( $results->num_rows > 0 ) 
{
	$i=0;
	while($ORDERROW = mysqli_fetch_array($results))  
	{
		$rowid = $ORDERROW['request_id'];
		$branch = $ORDERROW['branch'];
		$control_no = $ORDERROW['control_no'];
		$mrs_no = $ORDERROW['control_no'];
		$recipient = $ORDERROW['recipient'];
		$form_type = $ORDERROW['form_type'];
		$trans_date = $ORDERROW['trans_date'];
		$status = $ORDERROW['status'];
		if($form_type = 'MRS')
		{
			$form_text = 'MRS';
		}
		else if($form_type = 'POF')
		{
			$form_text = 'POF';
		}

	}
} else {
	echo "";
}
$uom = '';
?>
<style>
.lamess td {border:0 !important;padding: 0 !important;vertical-align:middle !important;}
.lamess td input {border:0;padding: 5px 10px 5px 10px}
.lamesako th {padding: 3px !important;background:#f1f1f1;text-align:center;}
.lamesako th, td {font-size: 14px}
.inputdata td {padding:0 !important;border-top:3px solid #232323;border-bottom:3px solid #232323}
.inputdata input,.inputdata select {border:0;background:#effde6}
}
.datarows td {vertical-align:middle;padding:0 !important;}
.datarows input, .datarows select {border: 0 !important;width: 100% !important;}
.datavalues:disabled{background-color: #e9ecef;cursor: not-allowed;}
.padinginfo {display: none;padding: 5px;background-color: #f8d7da;color: #721c24;border: 1px solid red;margin-top: 20px;text-align: center;border-radius: 10px}
</style>
<div style="width:100%;border:1px solid #aeaeae;padding:5px;font-size:14px;">
	<table style="width: 100%;white-space:nowrap">
		<tr>
			<td style="width:100px;font-size:13px">Section/Branch:</td>
			<td style="width:0px;">&nbsp;</td>
			<td style="border-bottom:1px solid #232323;width:300px;font-size:13px"><?php echo $branch?></td>
			<td style="width:10px;">&nbsp;</td>
			<td style="width:10px;font-size:13px;white-space:nowrap">Control No.:</td>
			<td style="width:10px;">&nbsp;</td>
			<td style="width:150px;font-size:13px;border-bottom:1px solid #232323;color:red;text-align:center"><?php echo $control_no?></td>
			<td style="width:10px;">&nbsp;</td>
			<td style="width:10px;font-size:13px">Date:</td>
			<td style="width:10px;">&nbsp;</td>
			<td style="width:200px;font-size:13px;border-bottom:1px solid #232323;text-align:center"><?php echo $trans_date?></td>
		</tr>
	</table>
	<table style="width: 100%;margin-top:20px" class="lamess">
		<tr>
			<th>Control No.:</th>
			<td><input type="text" value="<?php echo $control_no?>" disabled></td>
			<td style="width:100px">&nbsp;</td>
			<th>RECIPIENT:</th>
			<td><input type="text" value="<?php echo $recipient?>" disabled></td>
		</tr>
	</table>
</div>
<div class="padinginfo"></div>
<div style="width:100%;border:1px solid #aeaeae;padding:5px;font-size:14px;margin-top:20px;margin-bottom:10px">
	<table style="width: 100%" class="table-bordered lamesako">
		<tr>
			<th style="width:40px !important;text-align:center">#</th>
			<th style="width:80px">ACTION</th>
			<th>Description</th>
			<th style="width:100px">UOM</th>
			<th style="width:100px">Quantity</th>
		</tr>
<?php
	$sqlQueryData = "SELECT * FROM wms_branch_order_unlisted WHERE branch='$branch' AND control_no='$mrs_no'";
	$dataResults = mysqli_query($db, $sqlQueryData);    
	if ( $dataResults->num_rows > 0 ) 
	{
		$no_content = 0;
		$submit_text = "";
		$x=0;
		while($DATAROW = mysqli_fetch_array($dataResults))  
		{
			$x++;
			$rowid = $DATAROW['id'];
			$uom = $DATAROW['uom'];

?>		
		<tr class="datarows" id="datarows<?php echo $x?>">
			<td style="text-align:center"><?php echo $x?></td>			
			<td style="padding:0 !important;white-space:nowrap">
				<div class="btn-group" role="group" aria-label="Basic example" style="width:100%">
					<button class="btn btn-danger btn-sm w-100" onclick="removeData('<?php echo $rowid?>')"><i class="fa-solid fa-x"></i></button>
					<button class="btn btn-success btn-sm w-100" onclick="editData('<?php echo $x?>')"><i class="fa-solid fa-pen-to-square"></i></button>
				</div>
			</td>
			<td>
				<input id="item_description<?php echo $x?>" type="text" class="form-control form-control-sm datavalues" value="<?php echo $DATAROW['item_description']?>" onchange="saveThisUpdate('<?php echo $x?>','<?php echo $rowid?>')">
			</td>
			<td>
				<select id="uom<?php echo $x?>" class="form-control form-control-sm datavalues" onchange="saveThisUpdate('<?php echo $x?>','<?php echo $rowid?>')">
					<?php echo $function->GetUOM($uom,$db)?>
				</select>
			</td>
			<td>
				<input id="quantity<?php echo $x?>" type="text" class="form-control form-control-sm datavalues" value="<?php echo $DATAROW['quantity']?>" onchange="saveThisUpdate('<?php echo $x?>','<?php echo $rowid?>')">
			</td>
		</tr>
<?php }  } else { $no_content = 1; ?>
		<tr>
			<td colspan="5" style="text-align:center">No Items</td>
		</tr>
<?php } ?>		
		<tr class="inputdata">
			<td colspan="2" style="padding:0 !important">
				<button class="btn btn-primary btn-sm w-100" onclick="saveThisData()"><i class="fa-solid fa-cart-plus"></i> Add/Save</button>
			</td>
			<td colspan="">
				<input id="item_description" type="text" class="form-control form-control-sm" autocomplete="off" placeholder="Enter Item Name here"></td>
			<td>
				<select id="uom" class="form-control form-control-sm ">
					<?php echo $function->GetUOM('',$db)?>
				</select>
			</td>
			<td>
				<input id="quantity" type="number" class="form-control form-control-sm" autocomplete="off" placeholder="Quantity">
			</td>
		</tr>
	</table>
	<div style="margin-top:10px;text-align:right">
	<?php if($no_content == 0) { ?>
		<button class="btn btn-success" onclick="submitOrder('<?php echo $control_no; ?>')"><i class="fa-solid fa-file-signature"></i>&nbsp;&nbsp;Submit For Approval</button>
	<?php } ?>
		<button class="btn btn-warning" onclick="closeModal('formmodal')"><i class="fa-solid fa-x"></i>&nbsp;&nbsp;Cancel</button>
		<button class="btn btn-danger" onclick="voidOrderRequest('<?php echo $rowid; ?>')"><i class="fa-solid fa-ban"></i>&nbsp;&nbsp;Void Order Request</button>
	</div>
</div>
<div class="resultas"></div>
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
					$('.resultas').html(data);
					rms_reloaderOff();
				});
			},1000);
		}
	});
}
function submitOrder(controlno)
{
	swal({
        title: "Submit Order",
        text: "Are you sure to finish and submit your order?",
        icon: "warning",
        buttons: ["Cancel", "Yes, Submit it!"],
        dangerMode: true,
    }).then((willDelete) => {
	    if (willDelete) {
			var mode = 'submitorderunlisted';
			rms_reloaderOn("Submitting Order...");
			setTimeout(function()
			{
				$.post("./Modules/<?php echo MODULE_NAME; ?>/actions/actions.php", { mode: mode, control_no: controlno },
				function(data) {		
					$('.resultas').html(data);
					closeModal('formmodal');
					rms_reloaderOff();
				});
			},1000);
		}
	});

}
function removeData(rowid)
{
	var mode = 'removeorder';
	var editid = rowid;
	var order_type = '1';
	var form_type = 'MRS';
	var module = '<?php echo MODULE_NAME; ?>';
	var branch = '<?php echo $branch?>';
	var control_no = '<?php echo $control_no?>';
	var item_code = '';
	var unit_price = 0;
	var ending = 0;
	var inv_ending_uom = '';	
	var item_description = '';
    var uom = ''
    var quantity = '';
	rms_reloaderOn("Removing Item");
	setTimeout(function()
	{
		$.post("./Modules/" + module + "/actions/unlisted_process.php",
		{
			mode: mode,
			editid: editid,
			order_type: order_type,
			form_type: form_type,
			branch: branch,
			control_no: control_no,
			item_code: item_code,
			unit_price: unit_price,
			ending: ending,
			inv_ending_uom: inv_ending_uom,
			item_description: item_description,
			uom: uom,
			quantity: quantity
	
		},
		function(data) {		
			$('.resultas').html(data);
			rms_reloaderOff();
		});
	},1000);
}
function saveThisUpdate(elemid,rowid)
{
	var mode = 'updateorder';
	var editid = rowid;
	var order_type = '1';
	var form_type = 'MRS';
	var module = '<?php echo MODULE_NAME; ?>';
	var branch = '<?php echo $branch?>';
	var control_no = '<?php echo $control_no?>';
	var item_code = '';
	var unit_price = 0;
	var ending = 0;
	var inv_ending_uom = '';
	
	var item_description = $('#item_description' + elemid).val();
    var uom = $('#uom' + elemid).val();
    var quantity = $('#quantity' + elemid).val();
    
    if (item_description === '')
	{
        swal("Required", "Please fill in the Item Description.", "warning").then(() => {
            $('#item_description' + elemid).focus();
        });
    } else if (uom === '') {
        swal("Required", "Please fill in the UOM.", "warning").then(() => {
            $('#uom' + elemid).focus();
        });
    } else if (quantity === '') {
        swal("Required", "Please fill in the Quantity.", "warning").then(() => {
            $('#quantity' + elemid).focus();
        });
    } 
	else
	{
		$.post("./Modules/" + module + "/actions/unlisted_process.php", {
			mode: mode,
			editid: editid,
			order_type: order_type,
			form_type: form_type,
			branch: branch,
			control_no: control_no,
			item_code: item_code,
			unit_price: unit_price,
			ending: ending,
			inv_ending_uom: inv_ending_uom,
			item_description: item_description,
			uom: uom,
			quantity: quantity
		},
		function(data) {		
			$('.resultas').html(data);
			$('.padinginfo').fadeIn('slow', function() {
                setTimeout(function() {
                    $('.padinginfo').fadeOut('slow');
                }, 1100);
            });
		});	
	}
}
function editData(elemid)
{
	if(sessionStorage.ELEMID != '')
	{
		$('.datavalues').prop('disabled', true);
	}
	$('#item_description' + elemid).prop('disabled', false);
    $('#uom' + elemid).prop('disabled', false);
    $('#quantity' + elemid).prop('disabled', false);
    sessionStorage.setItem("ELEMID", elemid);
}
$(function()
{
	var status = '<?php echo $status?>';
	if(status == 'Approval')
	{
		$('.padinginfo').html("Request is in Approval Status");
		$('.padinginfo').show();
	}
	$('.datavalues').prop('disabled', true);
	$('#item_description, #uom, #quantity').on('keypress', function(event)
	{
		if (event.which === 13)
		{ 
		    event.preventDefault();
		    saveThisData();
		}
	});
});
function saveThisData()
{
	var mode = 'saveorder';
	var editid = '';
	var order_type = '1';
	var form_type = 'MRS';
	var module = '<?php echo MODULE_NAME; ?>';
	var branch = '<?php echo $branch?>';
	var control_no = '<?php echo $control_no?>';
	var item_code = '';
	var unit_price = 0;
	var ending = 0;
	var inv_ending_uom = '';
	
	var item_description = $('#item_description').val().trim();
	var uom = $('#uom').val().trim();
	var quantity = $('#quantity').val().trim();
	
	if (item_description === '')
	{
        swal("Required", "Please fill in the Item Description.", "warning").then(() => {
            $('#item_description').focus();
        });
    } else if (uom === '') {
        swal("Required", "Please fill in the UOM.", "warning").then(() => {
            $('#uom').focus();
        });
    } else if (quantity === '') {
        swal("Required", "Please fill in the Quantity.", "warning").then(() => {
            $('#quantity').focus();
        });
    } 
	else
	{
		rms_reloaderOn("Saving Item...");
		setTimeout(function()
		{	
			$.post("./Modules/" + module + "/actions/unlisted_process.php", {
				mode: mode,
				editid: editid,
				order_type: order_type,
				form_type: form_type,
				branch: branch,
				control_no: control_no,
				item_code: item_code,
				unit_price: unit_price,
				ending: ending,
				inv_ending_uom: inv_ending_uom,
				item_description: item_description,
				uom: uom,
				quantity: quantity
			},
			function(data) {		
				$('.resultas').html(data);
				rms_reloaderOff();
			});	
		},1000);	
	}
}
</script>
