<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);	
require $_SERVER['DOCUMENT_ROOT']."/Modules/Warehouse_Management/class/Class.inventory.php";
define("MODULE_NAME", "Branch_Ordering_System");
$inventory = new WMSInventory;
$cluster = $_SESSION['branch_cluster'];
$userlevel = $_SESSION['branch_userlevel'];

$_SESSION['BRANCH_CART_LIMIT'] = $_POST['limit'];
$_SESSION['BRANCH_CART_RECIPIENT'] = $_POST['recipient'];
$_SESSION['BRANCH_CART_BRANCH'] = $_POST['branch'];


$branch = $_POST['branch'];
$recipient = $_POST['recipient'];
$branch = $_POST['branch'];

$show_limit = isset($_POST['limit']) && is_numeric($_POST['limit']) ? (int)$_POST['limit'] : 25;
$page = isset($_POST['page']) && is_numeric($_POST['page']) ? (int)$_POST['page'] : 1;
$records_per_page = $show_limit > 0 ? $show_limit : 25;
$offset = max(0, ($page - 1) * $records_per_page);
$limitClause = "LIMIT $offset, $records_per_page";


if (isset($_POST['search']) && !empty($_POST['search']))
{
    $search_term = $_POST['search'];
    $q = "
    	WHERE branch='$branch' AND status='Open' 
		AND (recipient LIKE '%$search_term%' 
		OR control_no LIKE '%$search_term%' 
		OR trans_date LIKE '%$search_term%' 
		OR created_by LIKE '%$search_term%')
	";
} else {
    if (empty($recipient)) {
        $q = "WHERE branch='$branch' AND status='Open' ORDER BY trans_date DESC $limitClause";
    } else {
        $q = "WHERE branch='$branch' AND status='Open' AND recipient='$recipient' ORDER BY trans_date DESC $limitClause";
    }
}
$total_records_query = "SELECT COUNT(*) as total FROM wms_order_request $q";
$total_result = mysqli_query($db, $total_records_query);
$total_records = mysqli_fetch_assoc($total_result)['total'];
$total_pages = ceil($total_records / $records_per_page);

?>
<style>
.table-padding td button {
	padding: 6px !important;
	color: #fff;
}
</style>
<table style="width: 100%" class="table table-bordered table-hover table-padding">
	<thead>
		<tr>
			<th style="text-align:center;width:50px">#</th>
			<th>RECIPIENT</th>
			<th>BRANCH</th>			
			<th>ORDER TYPE</th>
			<th>CONTROL No.</th>
			<th>ORDER BY</th>
			<th>ORDER DATE</th>
			<th>STATUS</th>
			<th>PRIORITY</th>
			<th>ACTIONS</th>
		</tr>
	</thead>
	<tbody>
<?PHP
	$sqlQuery = "SELECT * FROM wms_order_request $q";
	$results = mysqli_query($db, $sqlQuery);    
    if ( $results->num_rows > 0 ) 
    {
    	$i=0;
    	while($ORDERROW = mysqli_fetch_array($results))  
		{
			$i++;$average=0;
			$rowid = $ORDERROW ['request_id'];
			$form_type = $ORDERROW ['form_type'];
			$o_type = $ORDERROW ['order_type'];
			$control_no = $ORDERROW ['control_no'];
			if($ORDERROW ['priority'] == 'Urgent')
			{
				$priority_indicator = 'background:#e34d5b;color:#fff';
			} else {
				$priority_indicator = '';
			}
			if($ORDERROW ['status'] == 'Closed')
			{
				$status_indicator = 'background:#e37b85;color:#fff';
			} else {
				$status_indicator = '';
			}
			if($ORDERROW ['order_type'] == 0)
			{
				$order_type = 'Listed Items';
				$sty_type = '';
			}
			if($ORDERROW ['order_type'] == 1)
			{
				$order_type = 'Unlisted Items';
				$sty_type = 'style="font-weight:600;color:#ffa800"';
			}
?>			
		<tr ondblclick="editRequest('edit','<?php echo $rowid; ?>')">
			<td style="width:50px;text-align:center"><?php echo $i; ?></td>
			<td><?php echo $ORDERROW['recipient']; ?></td>
			<td><?php echo $ORDERROW['branch']; ?></td>
			<td <?php echo $sty_type?>><?php echo $order_type; ?></td>
			<td><?php echo $ORDERROW['control_no']; ?></td>
			<td><?php echo $ORDERROW['created_by']; ?></td>
			<td><?php echo date("M. d, Y", strtotime($ORDERROW['trans_date'])); ?></td>
			<td style="text-align:center;<?php echo $status_indicator; ?>"><?php echo $ORDERROW ['status']; ?></td>
			<td style="text-align:center;<?php echo $priority_indicator; ?>"><?php echo $ORDERROW ['priority']; ?></td>
			<td style="padding:0 !important">
				<button class="btn btn-secondary btn-sm w-100" onclick="myBasket('<?php echo $control_no; ?>','<?php echo $form_type; ?>','<?php echo $o_type; ?>')"><i class="fa-solid fa-basket-shopping"></i>&nbsp;&nbsp;Basket</button>
			</td>
		</tr>
<?PHP 	} } else { ?>
		<tr>
			<td colspan="9" style="text-align:center"><i class="fa fa-bell color-orange"></i>&nbsp;&nbsp;No Records</td>
		</tr>
<?PHP } mysqli_close($db); ?>
	</tbody>	
	<tfoot>
		<tr>
			<th style="text-align:center;width:50px">#</th>
			<th>RECIPIENT</th>
			<th>BRANCH</th>
			<th>ORDER TYPE</th>
			<th>CONTROL No.</th>
			<th>ORDER BY</th>
			<th>ORDER DATE</th>
			<th>STATUS</th>
			<th>PRIORITY</th>
			<th>ACTIONS</th>
		</tr>
	</tfoot>
</table>
<div class="pagination">
        <a href="#" class="pagination-link <?php echo ($page <= 1) ? 'disabled' : ''; ?>" data-page="1">FIRST</a>
        <a href="#" class="pagination-link <?php echo ($page <= 1) ? 'disabled' : ''; ?>" data-page="<?php echo $page - 1; ?>">PREVIOUS</a>
<?php
	$range = 2;
	$start_page = max(1, $page - $range);
	$end_page = min($total_pages, $page + $range);
	
	for ($i = $start_page; $i <= $end_page; $i++)
	{
	    if ($i == $page)
	    {
	        echo '<a href="#" class="pagination-link active" data-page="' . $i . '">' . $i . '</a>';
	    } else {
	        echo '<a href="#" class="pagination-link" data-page="' . $i . '">' . $i . '</a>';
	    }
	}
	if ($end_page < $total_pages)
	{
	    if ($end_page < $total_pages - 1)
	    {
	        echo '<span>...</span>';
	    }
	    echo '<a href="#" class="pagination-link" data-page="' . $total_pages . '">' . $total_pages . '</a>';
	}
?>
        <a href="#" class="pagination-link <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>" data-page="<?php echo $page + 1; ?>">NEXT</a>
        <a href="#" class="pagination-link <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>" data-page="<?php echo $total_pages; ?>">LAST</a>
    </div>
<!-- ######################################################################################################## -->
<script>
function myBasket(control_no, form_type, order_type)
{
	var module = '<?php echo MODULE_NAME; ?>';
	if(order_type == '0')
	{	
		let branch = '<?php echo $branch?>';
		$('#modaltitle').html('Order Basket <strong>[[ ' + control_no + ' ]]</strong>');
		$.post("./Modules/" + module + "/apps/order_basket.php", { branch: branch, control_no: control_no, form_type: form_type, order_type: order_type },
		function(data) {		
			$('#formmodal_page').html(data);
			$('#formmodal').show();
		});		
	}
	if(order_type == '1')
	{
		let branch = '<?php echo $branch?>';
		$('#modaltitle').html('Order Basket for Unlisted Items - <strong>[[ ' + control_no + ' ]]</strong>');
		$.post("./Modules/" + module + "/apps/order_basket_unlisted.php", { branch: branch, control_no: control_no, form_type: form_type, order_type: order_type },
		function(data) {		
			$('#formmodal_page').html(data);
			$('#formmodal').show();
		});
	}
}
rms_reloaderOff();
</script>