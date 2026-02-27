<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
define("MODULE_NAME", "Branch_Ordering_System");
require_once($_SERVER['DOCUMENT_ROOT']."/Modules/".MODULE_NAME."/class/Class.functions.php");
$control_no = $_POST['control_no'];
//$branch = $_SESSION['branch_branch'];
$function = new WMSFunctions;
$creator = $_SESSION['branch_appnameuser'];

$formdisabled = $function->getOrderCreator($creator,$control_no,$db);
$sqlQuery = "SELECT * FROM wms_order_request WHERE control_no='$control_no'";
$results = mysqli_query($db, $sqlQuery);    
if ( $results->num_rows > 0 ) 
{
	$i=0;
	while($ORDERROW = mysqli_fetch_array($results))  
	{
		$branch = $ORDERROW['branch'];
		$control_no = $ORDERROW['control_no'];
		$mrs_no = $ORDERROW['control_no'];
		$recipient = $ORDERROW['recipient'];
		$form_type = $ORDERROW['form_type'];
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

$basket_mode = ($_POST['params'] == 'edit') ? $_POST['params'] : "add";

if(isset($_POST['editid']) && isset($_POST['params']))
{
	$editid = $_POST['editid'];
	$sqlQueryEdit = "SELECT * FROM wms_branch_order WHERE id='$editid'";
	$editResults = mysqli_query($db, $sqlQueryEdit);    
	if ( $editResults->num_rows > 0 ) 
	{
		$i=0;
		while($EDITROW = mysqli_fetch_array($editResults))  
		{
			$branch = $EDITROW['branch'];
			$mrs_no = $EDITROW['control_no'];
			$uom = $EDITROW['uom'];
			$item_code = $EDITROW['item_code'];
			$item_description = $EDITROW['item_description'];
			$unit_price = $EDITROW['unit_price'];
			$quantity = $EDITROW['quantity'];
			$inv_ending = $EDITROW['inv_ending'];
		}
	}
	
	$sqlQueryEditRemarks = "SELECT * FROM wms_branch_order_remarks WHERE control_no='$mrs_no'";
	$remarksEditResults = mysqli_query($db, $sqlQueryEditRemarks);    
	if ( $remarksEditResults->num_rows > 0 ) 
	{
		$i=0;
		while($REMARKSROW = mysqli_fetch_array($remarksEditResults))  
		{
			$remarks = $REMARKSROW['remarks'];			
			$update_r = 1;
		}
	} else {
		$remarks = "";
		$update_r = 0;
	}
} else {
	$editid = "";
	$item_description = "";
	$item_code = "";
	$unit_price = "0";
	$quantity =  "0";
	$remarks =  "";
	$inv_ending = "0";
	$uom =  "--|--";
}
?>
<style>
.warehouse-form-wrapper {display: flex;flex-direction: column;width: 350px;height: 100%;gap: 5px;}
.ware-house-label {padding: 5px;text-align: center;border: 1px solid #aeaeae;background: linear-gradient(to bottom, #077216, #05390c);border-radius: 0px 15px 0px 15px;font-size: 18px;font-weight: 600;color:#fff}

.marketplace-data {flex: 1;border: 1px solid #cccccc;padding: 5px;overflow: auto;}
.lamesa-ko td, th {border: 0 !important;padding: 3px !important;vertical-align:middle;white-space:nowrap;}
.shop-overlay {width: 100%;height: 100%;display: none;overflow:auto;position: fixed;top: 0px;left: 0px;background: rgba(0,0,0,0.2);z-index: 1000;}
.shop-dd {position:absolute;top: 50%;left: 50%;-webkit-transform: translate(-50%, -50%);transform: translate(-50%, -50%);background: #fff;
min-width: 300px;border-radius: 8px;box-shadow: 0 0px 9px rgba(0,0,10,0.5);}
</style>
<div class="warehouse-form-wrapper">
	<div class="ware-house-label">
		MARKET PLACE
	</div>
	<div class="marketplace-data" id="marketdata">		
		<table style="width: 100%" class="table lamesa-ko">
			<tr>
				<th>Branch</th>
				<td>
					<input id="editid" type="hidden" value="<?php echo $editid; ?>">
					<input id="controlno" type="hidden" value="<?php echo $control_no; ?>">
					<input id="branchname" type="text" class="form-control form-control-sm" value="<?php echo $branch; ?>" disabled>
				</td>
			</tr>
			<tr>
				<th>Control No.</th>
				<td><input id="control_no" type="text" class="form-control form-control-sm" value="<?php echo $mrs_no; ?>" disabled></td>
			</tr>
			<tr>
				<th>Recipient</th>
				<td><input id="recipients" type="text" class="form-control form-control-sm" value="<?php echo $recipient; ?>" disabled></td>
			</tr>
			<tr>
				<td colspan="2" style="position:relatives">
					<button class="btn btn-primary w-100" onclick="selectItem()"><i class="fa-solid fa-cart-plus color-yellow"></i>&nbsp;&nbsp;Start Ordering</button>
					<div class="shop-overlay">
						<div class="shop-dd">
							<div class="shop-data"></div>
						</div>
					</div>
				</td>
			</tr>
			<tr>
				<th colspan="2">Item Description</th>
			</tr>
			<tr>
				<td colspan="2">
					<input id="item_description" type="text" class="form-control form-control-sm searchinput" placeholder="Select Item here" value="<?php echo $item_description?>" autocomplete="offers" readonly>
				</td>
			</tr>
			<tr>
				<th>Item Code</th>
				<td><input id="item_code" type="text" class="form-control form-control-sm" value="<?php echo $item_code?>" disabled></td>
			</tr>
			<tr>
				<th>U.O.M.</th>
				<td><input id="uom" type="text" class="form-control form-control-sm" value="<?php echo $uom?>" disabled></td>
			</tr>
			<tr>
				<th>Unit Price</th>
				<td><input id="unit_price" type="text" class="form-control form-control-sm" value="<?php echo $unit_price?>" disabled></td>
			</tr>
			<tr>
				<th>Quantity</th>
				<td><input id="quantity" type="number" class="form-control form-control-sm" style="width:100px" value="<?php echo $quantity?>"></td>
			</tr>
			<tr>
				<td colspan="2">
					<hr style="margin:10px 0px 0px 0px">
				</td>
			</tr>
		</table>
		<table style="width: 100%" class="table lamesa-ko">
			<tr>
				<th colspan="2" style="background:#f1f1f1;text-align:center">INVENTORY ENDING</th>
			</tr>
			<tr>
				<th style="width: 50%;text-align:center">Weight/Quantity</th>
				<th style="width: 50%;text-align:center">U.O.M.</th>
			</tr>
			<tr>
				<td style="width: 50%"><input id="inv_ending" type="number" class="form-control form-control-sm" value="<?php echo $inv_ending; ?>"></td>
				<td style="width: 50%">
					<input id="inv_ending_uom" type="text" class="form-control form-control-sm" value="<?php echo $uom?>" disabled>
				</td>
			</tr>
		</table>
		<hr>
		<div class="market-control">
		<?php if($basket_mode == 'add') { ?>
			<button class="btn btn-success btn-sm" onclick="saveUpdateBasket('add')">Add To Basket</button>
		<?php } if($basket_mode == 'edit') { ?>
			<button class="btn btn-info btn-sm color-white" onclick="saveUpdateBasket('edit')">Update</button>
			<button class="btn btn-warning btn-sm color-white" onclick="loadWarehouse('add','')">Cancel Edit</button>
			<button class="btn btn-danger btn-sm" title="Delete" onclick="deleteItem('<?php echo $editid; ?>')"><i class="fa fa-trash"></i></button>
		<?php } ?>
		</div>
	</div>
</div>
<div id="inforesults"></div>
<script>
function deleteItem(editid) {
    var module = '<?php echo MODULE_NAME; ?>';
    var mode = "deleteorderitem";
    var control_no = $('#control_no').val();

    swal({
        title: "Are you sure?",
        text: "Once deleted, you will not be able to recover this item!",
        icon: "warning",
        buttons: ["Cancel", "Yes, delete it!"],
        dangerMode: true,
    }).then((willDelete) => {
        if (willDelete) {
            rms_reloaderOn("Deleting...");
            setTimeout(function () {
                $.post("./Modules/" + module + "/actions/actions.php", { mode: mode, editid: editid, control_no: control_no },
                function (data) {
                    $('#inforesults').html(data);
                    rms_reloaderOff();
                });
            }, 500);
        }
    });
}
function saveUpdateBasket(params)
{
	var module = '<?php echo MODULE_NAME; ?>';
	var editid = $('#editid').val();
	var rowid = $('#rowid').val();
	var branch = $('#branchname').val();
	var control_no = $('#control_no').val();
	var item_code = $('#item_code').val();
	var item_description = $('#item_description').val();
	var uom = $('#uom').val();
	var unit_price = $('#unit_price').val();
	var quantity = $('#quantity').val();
	var ending = $('#inv_ending').val();
	var inv_ending_uom = $('#inv_ending_uom').val();
	
	if(item_description === '')
	{
		app_alert("Item Description","Please select Item","warning","Ok","item_description","focus");
		return false;
	}	
	if(uom === '')
	{
		app_alert("Unit of Measures","Units of Measures is missing please check your itemlist or report to the system Administrator","warning","Ok","uom","focus");
		return false;
	}
	if(item_code === '')
	{
		app_alert("Item Code","Item Code is missing please check your itemlist or report to the system Administrator","warning","Ok","uom","focus");
		return false;
	}
	if(quantity <= 0 || quantity === '')
	{
		app_alert("Order Quantity","Please enter Quantity","warning","Ok","quantity","focus");
		return false;
	}
	if(ending === 0 || ending === '')
	{
		app_alert("Inventory Ending","Please enter Quantity of your Inventory Ending","warning","Ok","inv_ending","focus");
		return false;
	}
	if(inv_ending_uom === 0 || inv_ending_uom === '')
	{
		app_alert("Inventory Ending UOM","Please select Units of Measure of your Inventory Ending","warning","Ok","inv_ending_uom","focus");
		return false;
	}
	if(params == 'edit')
	{
		var mode = "updateorder";
		rms_reloaderOn("Updating...");
	}
	if(params == 'add')
	{
		var mode = "saveorder";
		rms_reloaderOn("Saving...")
	}
	/* ############################# PROCESSING ############################## */
		setTimeout(function()
	{
		$.post("./Modules/" + module + "/actions/create_order_process.php",
		{
			mode: mode,
			editid: editid,
			rowid: rowid,
			branch: branch,
			control_no: control_no,
			item_code: item_code,
			item_description: item_description,
			uom: uom,
			unit_price: unit_price,
			quantity: quantity,
			ending: ending,
			inv_ending_uom: inv_ending_uom
		},
		function(data) {		
			$('#inforesults').html(data);
			rms_reloaderOff();
		});
	},1000);
}
function selectItem()
{
	var module = '<?php echo MODULE_NAME; ?>';
	var recipient = $('#recipients').val();
	console.log(recipient);
	$.post("./Modules/" + module + "/apps/market_place_storage.php", { recipient: recipient },
	function(data) {		
		$('.shop-data').html(data);
		$('.shop-overlay').show();
	});
}
function closeMarket()
{
	$('.shop-overlay').fadeOut('slow');
}
</script>
<?php
if($function->GetOrderCreatorName($control_no,$db) != ucwords(strtolower($function->GetMyRealName($_SESSION['branch_username'],$db))))
{
	if($_SESSION['branch_userlevel'] < 50)
	{
		echo '
			<script>
				$(".warehouse-form-wrapper,.basket-data-wrapper").find("input, select, textarea, button").prop("disabled", true);
			</script>
		';
	} else if($_SESSION['branch_userlevel'] >= 50) {
		
		echo '
			<script>
		//		$(".warehouse-form-wrapper,.basket-data-wrapper").find("input, select, textarea, button").prop("disabled", false);
			</script>
		';
	}
} 
else if($function->GetOrderCreatorName($control_no,$db) == ucwords(strtolower($function->GetMyRealName($_SESSION['branch_username'],$db))))
{
	echo '
		<script>
	//		$(".warehouse-form-wrapper,.basket-data-wrapper").find("input, select, textarea, button").prop("disabled", false);
		</script>
	';
}	
?>
