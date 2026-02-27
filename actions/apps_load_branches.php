<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);	
require $_SERVER['DOCUMENT_ROOT']."/Modules/Branch_Ordering_System/class/Class.functions.php";
$function = new WMSFunctions;
$cluster = $_SESSION['branch_cluster'];
?>
<style>
.branch-search ul {list-style-type: none;margin: 0;padding: 0;font-size: 13px}
.branch-search li {display: flex;padding: 5px 5px 5px 10px;border-bottom: 1px solid #aeaeae;cursor: pointer;}
.branch-search li:hover {background: #aeaeae;color: #fff}
.branch-search li:last-child {border: 0;}
.branch-icon {margin-right: 10px;}
</style>
<ul>
<?php
	$QUERY = "SELECT * FROM tbl_branch WHERE location='$cluster'";
	$RESULTS = mysqli_query($db, $QUERY);    
	if ( $RESULTS->num_rows > 0 ) 
	{
		while($ROW = mysqli_fetch_array($RESULTS))  
		{
			$branch = $ROW['branch'];
?>
	<li onclick="selectedBranch('<?php echo $branch?>')">
		<span class="branch-icon"><i class="fa-solid fa-angles-right"></i></span> <?php echo $branch?>
	</li>
<?php } } else { ?>	
	<li>No Records</li>
<?php } ?>
</ul>