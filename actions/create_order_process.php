<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
define("MODULE_NAME", "Branch_Ordering_System");
require_once($_SERVER['DOCUMENT_ROOT']."/Modules/".MODULE_NAME."/class/Class.functions.php");
$function = new WMSFunctions;
$year = date("Y-");
if(isset($_POST['mode']))
{
	$mode = $_POST['mode'];
} else {
	print_r('
		<script>
			app_alert("Warning"," The Mode you are trying to pass does not exist","warning","Ok","","no");
		</script>
	');
	exit();
}
$editid = $_POST['editid'];
$cluster = $_SESSION['branch_cluster'];
$branch = $_POST['branch'];
$control_no = $_POST['control_no'];
$item_code = $_POST['item_code'];
$item_description = $_POST['item_description'];
$uom = $_POST['uom'];
$quantity = $_POST['quantity'];
$ending = $_POST['ending'];
$unit_price = $_POST['unit_price'];
$inv_ending_uom = $_POST['inv_ending_uom'];

$app_user = $_SESSION['branch_username'];
$date_user = date("Y-m-d H:i:s");
$trans_date = date("Y-m-d");
if($mode == 'saveorder')
{
	if($function->GetItemStock($item_code,$db) <= 0)
	{
		print_r('
			<script>
				swal("Warning", "Sorry the '.$item_description.' is out of stock.", "warning");
				loadWarehouse("new","");
			</script>
		');
		exit();
	}
	elseif($function->GetItemStock($item_code,$db) < $quantity)
	{
		print_r('
			<script>
				swal("Warning", "Sorry your Quantity is higher than the current stock.", "warning");
			</script>
		');
		exit();
	} 
	$query = "SELECT * FROM wms_branch_order WHERE item_code='$item_code' AND branch='$branch' AND control_no='$control_no'";
	$checkRes = mysqli_query($db, $query);    
    if ( $checkRes->num_rows === 0 ) 
    {
    	$column = "`cluster`,`branch`,`control_no`,`item_code`,`item_description`,`uom`,`quantity`,`created_by`,`trans_date`,`date_created`,`status`,`inv_ending`,`inv_ending_uom`,`unit_price`";
    	$insert = "'$cluster','$branch','$control_no','$item_code','$item_description','$uom','$quantity','$app_user','$trans_date','$date_user','Approval','$ending','$inv_ending_uom','$unit_price'";
		$queryInsert = "INSERT INTO wms_branch_order ($column)";
		$queryInsert .= "VALUES($insert)";
		if ($db->query($queryInsert) === TRUE)
		{
			print_r('
				<script>
					var control_no = "'.$control_no.'";
					swal("Success", "Item has been successfully added", "success");
					loadWarehouse("new","");
				</script>
			');
		} else {
			print_r('
				<script>
					swal("Warning", "'.$db->error.'", "warning");
				</script>
			');
		}	
	} else {
		print_r('
			<script>
				swal("Warning", "Item name '.$item_description.' is already exists.", "warning");
				loadWarehouse("new","");
			</script>
		');
	}
	mysqli_close($db);	
}
if($mode == 'updateorder')
{
	$update = "item_code='$item_code',item_description='$item_description',uom='$uom',quantity='$quantity',updated_by='$app_user',date_updated='$date_user',inv_ending='$ending',inv_ending_uom='$inv_ending_uom'";
	$queryDataUpdate = "UPDATE wms_branch_order SET $update WHERE id='$editid'";
	if ($db->query($queryDataUpdate) === TRUE)
	{
		print_r('
			<script>
				var controlno = "'.$control_no.'";
				swal("Success", "Order has been successfully updated", "success");
				loadWarehouse("new","");
			</script>
		');		
	} else {
		print_r('
			<script>
				swal("Warning", "'.$db->error.'", "warning");
			</script>
		');
	}
	mysqli_close($db);
}
?>