<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);	
require $_SERVER['DOCUMENT_ROOT']."/Modules/Warehouse_Management/class/Class.inventory.php";
define("MODULE_NAME", "Branch_Ordering_System");
$inventory = new WMSInventory;

$_SESSION['BRANCH_MYORDER_LIMIT'] = $_POST['limit'];
$_SESSION['BRANCH_MYORDER_RECIPIENT'] = $_POST['recipient'];
$_SESSION['BRANCH_MYORDER_STATUS'] = $_POST['orderstatus'];
$_SESSION['BRANCH_MYORDER_BRANCH'] = $_POST['branch'];

$branch = $_POST['branch'];
$status = $_POST['orderstatus'];
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
		if ($status == '' || strtolower($status) == 'all') {
			$q = "\n     WHERE branch='$branch' \n\tAND (recipient LIKE '%$search_term%' \n\tOR control_no LIKE '%$search_term%' \n\tOR trans_date LIKE '%$search_term%' \n\tOR created_by LIKE '%$search_term%')\n";
		} else {
			$q = "\n     WHERE branch='$branch' AND status='$status' \n\tAND (recipient LIKE '%$search_term%' \n\tOR control_no LIKE '%$search_term%' \n\tOR trans_date LIKE '%$search_term%' \n\tOR created_by LIKE '%$search_term%')\n";
		}
} else {
    if (empty($recipient)) {
			if ($status == '' || strtolower($status) == 'all') {
				$q = "WHERE branch='$branch' ORDER BY trans_date DESC $limitClause";
			} else {
				$q = "WHERE branch='$branch' AND status='$status' ORDER BY trans_date DESC $limitClause";
			}
    } else {
			if ($status == '' || strtolower($status) == 'all') {
				$q = "WHERE branch='$branch' AND recipient='$recipient' ORDER BY trans_date DESC $limitClause";
			} else {
				$q = "WHERE branch='$branch' AND status='$status' AND recipient='$recipient' ORDER BY trans_date DESC $limitClause";
			}
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
.table-padding td {
	vertical-align:middle
}
</style>
<table style="width: 100%" class="table table-bordered table-hover table-padding">
	<thead>
		<tr>
			<th style="text-align:center;width:50px">#</th>
			<th>BRANCH</th>
			<th>CONTROL No.</th>
			<th>RECIPIENT</th>
			<th>ORDER BY</th>
			<th>ORDER DATE</th>
			<th>STATUS</th>
			<th>PRIORITY</th>
			<th style="width:80px">ACTIONS</th>
		</tr>
	</thead>
	<tbody>
<?PHP

	$col = "request_id,control_no,priority,status,order_type,branch,recipient,created_by,trans_date";
	$sqlQuery = "SELECT $col FROM wms_order_request $q";
	$results = mysqli_query($db, $sqlQuery);
	$i = 0;
	if ($results && $results->num_rows > 0)
	{
		while($ORDERROW = mysqli_fetch_array($results))
		{
			$i++;
			$rowid = $ORDERROW['request_id'];
			$control_no = $ORDERROW['control_no'];
			if($ORDERROW['priority'] == 'Urgent')
			{
				$priority_indicator = 'background:#e34d5b;color:#fff';
			} else {
				$priority_indicator = '';
			}
			if($ORDERROW['status'] == 'Closed')
			{
				$status_indicator = 'background:#e37b85;color:#fff';
			} else {
				$status_indicator = '';
			}
			if($ORDERROW['order_type'] == 0)
			{
				$order_type = 'Listed Items';
				$sty_type = '';
			}
			else if($ORDERROW['order_type'] == 1)
			{
				$order_type = 'Unlisted Items';
				$sty_type = 'style=\"font-weight:600;color:#ffa800\"';
			}
	?>
			<tr ondblclick="editRequest('edit','<?php echo $rowid; ?>')">
				<td style="width:50px;text-align:center"><?php echo $i; ?></td>
				<td><?php echo $ORDERROW['branch']; ?></td>
				<td><?php echo $ORDERROW['control_no']; ?></td>
				<td><?php echo $ORDERROW['recipient']; ?></td>
				<td><?php echo $ORDERROW['created_by']; ?></td>
				<td><?php echo date("F d, Y", strtotime($ORDERROW['trans_date'])) ?></td>
				<td style="text-align:center;<?php echo $status_indicator; ?>"><?php echo $ORDERROW['status']; ?></td>
				<td style="text-align:center;<?php echo $priority_indicator; ?>"><?php echo $ORDERROW['priority']; ?></td>
				<td style="padding:0 !important">
					<button class="btn btn-secondary btn-sm w-100" onclick="trackingOrder('<?php echo $control_no; ?>','<?php echo $order_type; ?>','<?php echo $rowid; ?>')"><i class="fa-solid fa-circle-info color-dodger"></i>&nbsp;&nbsp;Details</button>
				</td>
			</tr>
	<?PHP   }
	} else { ?>
			<tr>
				<td colspan="10" style="text-align:center"><i class="fa fa-bell color-orange"></i>&nbsp;&nbsp;No Records</td>
			</tr>
	<?PHP }
	mysqli_close($db);
	?>

	</tbody>
	<tfoot>
		<tr>
			<th style="text-align:center;width:50px">#</th>
			<th>BRANCH</th>
			<th>CONTROL No.</th>
			<th>RECIPIENT</th>
			<th>ORDER BY</th>
			<th>ORDER DATE</th>
			<th>STATUS</th>
			<th>PRIORITY</th>
			<th>ACTIONS</th>
		</tr>
	</tfoot>
</table>
<!-- ######################################################################################################## -->    
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
function trackingOrder(control_no, order_type, rowid)
{
	var module = '<?php echo MODULE_NAME; ?>';
	$('#modaltitle').html("MY ORDER DETAILS [[ <strong>" + control_no + "</strong> ]]");
	$.post("./Modules/" + module + "/apps/form_order_tracking_data.php", { rowid: rowid, order_type: order_type, control_no: control_no },
	function(data) {		
		$('#formmodal_page').html(data);
		$('#formmodal').show();
	});
}
rms_reloaderOff();
</script>