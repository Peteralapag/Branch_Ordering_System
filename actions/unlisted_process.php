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

$order_type = $_POST['order_type'];
$form_type = $_POST['form_type'];
$cluster = $_SESSION['branch_cluster'];
$branch = $_POST['branch'];
$control_no = $_POST['control_no'];
$item_code = $_POST['item_code'];
$item_description = $_POST['item_description'];
$uom = $_POST['uom'];
$quantity = $_POST['quantity'];
$ending = $_POST['ending'];
$inv_ending_uom = $_POST['inv_ending_uom'];

$app_user = $_SESSION['branch_username'];
$date_user = date("Y-m-d H:i:s");
$trans_date = date("Y-m-d");
if($mode == 'saveorder')
{
	$column = "`cluster`,`branch`,`control_no`,`item_code`,`item_description`,`uom`,`quantity`,`created_by`,`trans_date`,`date_created`,`status`,`inv_ending`,`inv_ending_uom`";
	$insert = "'$cluster','$branch','$control_no','$item_code','$item_description','$uom','$quantity','$app_user','$trans_date','$date_user','Approval','$ending','$inv_ending_uom'";
	$queryInsert = "INSERT INTO wms_branch_order_unlisted ($column)";
	$queryInsert .= "VALUES($insert)";
	if ($db->query($queryInsert) === TRUE)
	{
		print_r('
			<script>
				var controlno = "'.$control_no.'";
				var order_type =  "'.$order_type.'";
				var form_type =  "'.$form_type.'";
				swal("Success", "Item has been successfully added", "success");
				myBasket(controlno,form_type,order_type);				
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
if($mode == 'removeorder')
{
	$queryDataDelete = "DELETE FROM wms_branch_order_unlisted WHERE id='$editid' ";
	if ($db->query($queryDataDelete) === TRUE)
	{ 
		print_r('
			<script>
				var controlno = "'.$control_no.'";
				var order_type =  "'.$order_type.'";
				var form_type =  "'.$form_type.'";
				swal("Success","Item has been removed", "success");
				myBasket(controlno,form_type,order_type);
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
if($mode == 'updateorder')
{
	$update = "item_description='$item_description',uom='$uom',quantity='$quantity',updated_by='$app_user',date_updated='$date_user'";
	$queryDataUpdate = "UPDATE wms_branch_order_unlisted SET $update WHERE id='$editid'";
	if ($db->query($queryDataUpdate) === TRUE)
	{
		print_r('
			<script>
				$(".padinginfo").html("Successfuly Updated");
				swal("Success","Item has been Updated", "success");
				myBasket(controlno,form_type,order_type);
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