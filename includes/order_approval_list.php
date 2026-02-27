<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);	
require $_SERVER['DOCUMENT_ROOT']."/Modules/Branch_Ordering_System/class/Class.functions.php";
$function = new WMSFunctions;

if($_SESSION['branch_userlevel'] > 50)
{
	$kwiri = "";
} 
else if($_SESSION['branch_userlevel'] == 50)
{
	$cluster = $_SESSION['branch_cluster'];
	$kwiri = "cluster='$cluster' AND";
} 
else if($_SESSION['branch_userlevel'] < 50) {
	$branch = $_SESSION['branch_branch'];
	$kwiri = "branch='$branch' AND";
}
?>
<style>
.openFixHead { overflow: auto; height: auto; max-height: calc(100vh - 250px); width:100% }
.openFixHead thead th,
.openFixHead tfoot th {position: sticky;background: #0091d5; color: #fff;z-index: 1;}
.openFixHead thead th {top: 0;}
.openFixHead tfoot th {bottom: 0;}
.openFixHead table {border-collapse: collapse;}
.openFixHead th, .openFixHead td {font-size: 14px; white-space: nowrap;}
.open-table th {
	text-align:center;
}
.open-table th,.open-table td {
	padding: 6px !important;
	font-size: 14px !important;
	vertical-align: middle;
}
.open-table button {
	padding:3px 6px 3px 6px!important
}
</style>
<div class="openFixHead">
<table style="width: 100%" class="table table-bordered table-hover open-table">
	<thead>
		<tr>
			<th style="text-align:center;width:50px">#</th>
			<th>BRANCH</th>
			<th>ORDER BY</th>
			<th>CONTROL No.</th>
			<th>RECIPIENT</th>
			<th>ORDER DATE</th>
			<th>ACTION</th>
		</tr>
	</thead>		
	<tbody>
<?php
	$OPENQUERY = "SELECT request_id,branch,control_no,recipient,created_by,trans_date,order_type FROM wms_order_request WHERE $kwiri status='Approval'";
	$OPENRESULTS = mysqli_query($db, $OPENQUERY);    
    if ( $OPENRESULTS->num_rows > 0 ) 
    {
    	$o=0;
    	while($OPENROW = mysqli_fetch_array($OPENRESULTS))  
		{
			$o++;
			$rowid = $OPENROW['request_id'];
			$control_no = $OPENROW['control_no'];
			$order_type = $OPENROW['order_type'];

?>	
		<tr>
			<td style="text-align:center"><?php echo $o?></td>
			<td><?php echo $OPENROW['branch']?></td>
			<td><?php echo $OPENROW['created_by']?></td>
			<td><?php echo $OPENROW['control_no']?></td>
			<td><?php echo $OPENROW['recipient']?></td>
			<td><?php echo $OPENROW['trans_date']?></td>
			<td style="padding:1px !important">
				<button class="btn btn-primary btn-sm" onclick="openApproval('<?php echo $control_no?>','<?php echo $order_type?>')"><i class="fa-solid fa-circle-info"></i>&nbsp; Details</button>
			</td>
		</tr>
<?php } } else {?>		
		<tr>
			<td colspan="6" style="text-align:center"><i class="fa fa-bell fa-spin color-orange"></i>&nbsp; No Pending</td>
		</tr>
<?php } ?>		
	</tbody>
	<tfoot>
		<tr>
			<th style="text-align:center;width:50px">#</th>
			<th>BRANCH</th>
			<th>ORDER BY</th>
			<th>CONTROL No.</th>
			<th>RECIPIENT</th>
			<th>ORDER DATE</th>
			<th>ACTION</th>
		</tr>
	</tfoot>
</table>
</div>
<div class="results"></div>
<script>
function openApproval(controlno,ordertype)
{	
	var querymode = 'openapproval';
	$('#formodalsmtitle').html("ORDER APPROVAL");
	$.post("./Modules/Branch_Ordering_System/includes/mrs_order_approval_data.php.php", { control_no: controlno, ordertype: ordertype },
	function(data) {		
		$('#formodalsm_page').html(data);
		$('#formodalsm').show();
	});
}
</script>
