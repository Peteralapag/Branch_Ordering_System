<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);	
require $_SERVER['DOCUMENT_ROOT']."/Modules/Branch_Ordering_System/class/Class.functions.php";
$function = new WMSFunctions;
$cluster = $_SESSION['branch_cluster'];
$username = isset($_SESSION['branch_username']) ? $_SESSION['branch_username'] : '';
// Fetch user record for debug (may contain idcode or other identifier)
$user_debug = array();
if(!empty($username)){
	$uq = "SELECT * FROM tbl_system_user WHERE username='".mysqli_real_escape_string($db,$username)."' LIMIT 1";
	$ures = mysqli_query($db, $uq);
	if($ures && $ures->num_rows > 0){
		$user_debug = mysqli_fetch_assoc($ures);
	}
		
}
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
	// Build list of clusters: include default cluster from session plus any clusters
	// assigned to the user in tbl_system_user_add_cluster.
	$clusters = array();
	if(!empty($cluster)) $clusters[] = $cluster;
	if(!empty($username)) {
		// Some schemas don't have a username column in tbl_system_user_add_cluster.
		// Fetch all rows and match in PHP to avoid SQL errors.
		$ucq = "SELECT * FROM tbl_system_user_add_cluster";
		$ucres = mysqli_query($db, $ucq);
		$uc_debug_rows = array();
		if($ucres) {
			while($ucrow = mysqli_fetch_assoc($ucres)) {
				$uc_debug_rows[] = $ucrow;
				$matched = false;
				// direct match against any cell for username
				foreach($ucrow as $val) {
					if($val === $username) { $matched = true; break; }
				}
				// also match against user's idcode if available
				if(!$matched && !empty($user_debug) && isset($user_debug['idcode'])) {
					foreach($ucrow as $val) {
						if($val === $user_debug['idcode']) { $matched = true; break; }
					}
				}
				if($matched) {
					// pick likely cluster column names first
					$found = false;
					foreach(array('cluster','location','branch_cluster','assigned_cluster','assigned','branch','branch_name') as $col) {
						if(isset($ucrow[$col]) && $ucrow[$col] !== '') { $clusters[] = $ucrow[$col]; $found = true; }
					}
					// fallback: take third column value if no named column matched
					if(!$found) {
						$vals = array_values($ucrow);
						if(isset($vals[2]) && $vals[2] !== '') { $clusters[] = $vals[2]; }
					}
				}
			}
		}
			
	}
	$clusters = array_unique(array_filter($clusters));
	// Also collect specific branches assigned to the user (tbl_system_user_add_branch)
	$explicit_branches = array();
	if(!empty($username)) {
		$ubq = "SELECT * FROM tbl_system_user_add_branch";
		$ubres = mysqli_query($db, $ubq);
		if($ubres) {
			while($ubrow = mysqli_fetch_assoc($ubres)) {
				$matched = false;
				// match against any column value for username
				foreach($ubrow as $val) {
					if($val === $username) { $matched = true; break; }
				}
				// match against idcode if available
				if(!$matched && !empty($user_debug) && isset($user_debug['idcode'])) {
					foreach($ubrow as $val) {
						if($val === $user_debug['idcode']) { $matched = true; break; }
					}
				}
				if($matched) {
					// try common column names for branch
					$found = false;
					foreach(array('branch','assigned_branch','branch_name','user_branch') as $col) {
						if(isset($ubrow[$col]) && $ubrow[$col] !== '') { $explicit_branches[] = $ubrow[$col]; $found = true; }
					}
					// fallback: take third column value if no named column matched
					if(!$found) {
						$vals = array_values($ubrow);
						if(isset($vals[2]) && $vals[2] !== '') { $explicit_branches[] = $vals[2]; }
					}
				}
			}
		}
	}
	$explicit_branches = array_unique(array_filter($explicit_branches));

	// Build the branch query: include branches by cluster (location) and explicit branch names
	$conditions = array();
	if(count($clusters) > 0) {
		$escaped = array_map(function($c) use ($db){ return "'".mysqli_real_escape_string($db,$c)."'"; }, $clusters);
		$in = implode(',', $escaped);
		$conditions[] = "location IN (".$in.")";
	}
	if(count($explicit_branches) > 0) {
		$escapedB = array_map(function($b) use ($db){ return "'".mysqli_real_escape_string($db,$b)."'"; }, $explicit_branches);
		$inB = implode(',', $escapedB);
		$conditions[] = "branch IN (".$inB.")";
	}
	if(count($conditions) > 0) {
		$QUERY = "SELECT * FROM tbl_branch WHERE (".implode(' OR ',$conditions).")";
	} else {
		$QUERY = "SELECT * FROM tbl_branch WHERE location='".mysqli_real_escape_string($db,$cluster)."'";
	}
	$RESULTS = mysqli_query($db, $QUERY);
	if ( $RESULTS && $RESULTS->num_rows > 0 ) 
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