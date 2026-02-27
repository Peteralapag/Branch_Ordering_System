<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);	
define("MODULE_NAME", "Branch_Ordering_System");
require_once($_SERVER['DOCUMENT_ROOT']."/Modules/".MODULE_NAME."/class/Class.functions.php");
$function = new WMSFunctions;
$control_no = $_POST['control_no'];
$branch = $_POST['branch'];
?>
<style>
.basket-wrapper {
	display: flex;
	width: calc(80vw);
	height: calc(80vh);
	margin-bottom: 10px;
	gap: 10px;
}
</style>
<div class="basket-wrapper">
	<div class="warehouse-wrapper" id="warehouseform"></div>
	<div class="basket-wrapper" id="basketdata"></div>
</div>
<script>
$(document).ready(function()
{
	loadWarehouse('add','');
});
function loadWarehouseEdit(params,control_no,editid)
{
	var module = '<?php echo MODULE_NAME; ?>';
	$.post("./Modules/" + module + "/apps/warehouse.php", { control_no: control_no, params: params, editid: editid},
	function(data) {		
		$('#warehouseform').html(data);
	});
}
function loadWarehouse(params,editid)
{
	var control_no = '<?php echo $control_no?>';
	var module = '<?php echo MODULE_NAME; ?>';
	$.post("./Modules/" + module + "/apps/warehouse.php", { control_no: control_no, params: params },
	function(data) {		
		$('#warehouseform').html(data);
		load_basket_data();
	});
}
function load_basket_data()
{
	var control_no = '<?php echo $control_no?>';
	var module = '<?php echo MODULE_NAME; ?>';
	let branch = '<?php echo $branch?>';
	$.post("./Modules/" + module + "/includes/basket_data.php", { branch: branch, control_no: control_no },
	function(data) {		
		$('#basketdata').html(data);
	});
}
</script>