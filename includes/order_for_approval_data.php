<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);	
require $_SERVER['DOCUMENT_ROOT']."/Modules/Warehouse_Management/class/Class.inventory.php";
define("MODULE_NAME", "Branch_Ordering_System");
$inventory = new WMSInventory;
$cluster = $_SESSION['branch_cluster'];
$userlevel = $_SESSION['branch_userlevel'];

$_SESSION['BRANCH_APPROVAL_LIMIT'] = $_POST['limit'];
$_SESSION['BRANCH_APPROVAL_RECIPIENT'] = $_POST['recipient'];
$_SESSION['BRANCH_APPROVAL_BRANCH'] = $_POST['branch'];



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
    	WHERE branch='$branch' AND status='Approval' 
		AND (recipient LIKE '%$search_term%' 
		OR control_no LIKE '%$search_term%' 
		OR trans_date LIKE '%$search_term%' 
		OR created_by LIKE '%$search_term%')
	";
} else {
    if (empty($recipient)) {
        $q = "WHERE branch='$branch' AND status='Approval' ORDER BY trans_date DESC $limitClause";
    } else {
        $q = "WHERE branch='$branch' AND status='Approval' AND recipient='$recipient' ORDER BY trans_date DESC $limitClause";
    }
}

$total_records_query = "SELECT COUNT(*) as total FROM wms_order_request $q";
$total_result = mysqli_query($db, $total_records_query);
$total_records = mysqli_fetch_assoc($total_result)['total'];
$total_pages = ceil($total_records / $records_per_page);
?>
<style>
.table td { padding:2px 5px 2px 5px !important; }
</style>
<table style="width: 100%" class="table table-bordered table-striped table-hover">
	<thead>
		<tr>
			<th style="width:50px;text-align:center">#</th>
			<th style="width:130px">BRANCH</th>
			<th style="width:130px">CONTROL No.</th>
			<th style="width:130px">ORDER TYPE</th>
			<th>RECIPIENT</th>
			<th style="width:150px">ORDER BY</th>
			<th style="width:80px">ORDER DATE</th>
			<th style="width:70px">STATUS</th>
			<th style="width:70px">PRIORITY</th>
			<th style="width: 70PX">ACTIONS</th>
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
			$control_no = $ORDERROW ['control_no'];
			$o_type = $ORDERROW ['order_type'];
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
				$sty_type = 'style="font-weight:600"';
			}
?>			
		<tr ondblclick="editRequest('edit','<?php echo $rowid; ?>')">
			<td style="width:50px;text-align:center"><?php echo $i; ?></td>
			<td><?php echo $ORDERROW['branch']; ?></td>
			<td><?php echo $ORDERROW['control_no']; ?></td>
			<td <?php echo $sty_type?>><?php echo $order_type; ?></td>
			<td><?php echo $ORDERROW['recipient']; ?></td>
			<td><?php echo $ORDERROW['created_by']; ?></td>
			<td><?php echo $ORDERROW['trans_date']; ?></td>
			<td style="text-align:center;<?php echo $status_indicator; ?>"><?php echo $ORDERROW ['status']; ?></td>
			<td style="text-align:center;<?php echo $priority_indicator; ?>"><?php echo $ORDERROW ['priority']; ?></td>
			<td style="padding:1px !important">
				<button class="btn btn-info btn-sm color-white" onclick="orderApproval('<?php echo $control_no; ?>','<?php echo $o_type; ?>')"><i class="fa-solid fa-thumbs-up color-orange"></i>&nbsp;Approved?</button>
			</td>
		</tr>
<?PHP 	} } else { ?>
		<tr>
			<td colspan="10" style="text-align:center"><i class="fa fa-bell"></i>&nbsp;&nbsp;No Records</td>
		</tr>
<?PHP } mysqli_close($db); ?>			
	</tbody>
</table>
<div id="orderapproval"></div>
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
function orderApproval(controlno,ordertype)
{	
	var module = '<?php echo MODULE_NAME; ?>';
	$('#modaltitle').html("ORDER APPROVAL");
	$.post("./Modules/" + module + "/includes/mrs_order_approval_data.php.php", { control_no: controlno, ordertype: ordertype },
	function(data) {		
		$('#formmodal_page').html(data);
		$('#formmodal').show();
	});
}
</script>
