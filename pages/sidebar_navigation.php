<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);	
define("MODULE_NAME", "Branch_Ordering_System");
?>
<style>
.sidebar-nav { list-style-type:none; margin:0;padding:0 }
.navpadleft {margin-left:10px;cursor:pointer; width:100%;}
.sidebar-nav li { display: flex; padding:5px 5px 5px 5px;border-bottom: 1px solid #aeaeae; width:100%; gap: 15px;cursor:pointer}
.sidebar-nav li:hover {background:#e7e7e7;}
.sidebar-nav .nav-icon {width:30px;text-align:center;font-size:18px;}
.sidebar-nav span {right: 0;}
.sidebar-nav .caret-right {margin-left: auto;}
.active-nav {background: #dcdfe0;}
.active {border: 1px solid blue;}
.nav-bottom-btn {
	position:absolute;
	bottom: 2px;
	margin-left:3px;
	width: 98%;
}

</style>
<ul class="sidebar-nav">
<?php

// Always show dashboard module first
$dashboard_query = "SELECT * FROM wms_branch_navigation WHERE menu_name='dashboard' AND active=1 LIMIT 1";
$dashboard_result = mysqli_query($db, $dashboard_query);
$m = 0;
if ($dashboard_result && $dashboard_result->num_rows > 0) {
	$MENUROW = mysqli_fetch_array($dashboard_result);
	$m++;
?>
	<li id="nav<?php echo $m; ?>" data-nav="nav<?php echo $m; ?>" onclick="createRequest('<?php echo $MENUROW['page_name']; ?>')">
		<div class="nav-icon"><?php echo $MENUROW['icon_class']; ?></div> <span><?php echo $MENUROW['menu_name']; ?></span>
	</li>
<?php }

$sqlMenu = "SELECT * FROM wms_branch_navigation WHERE active=1 AND menu_name!='dashboard'";
$MenuResults = mysqli_query($db, $sqlMenu);
if ($MenuResults && $MenuResults->num_rows > 0) {
	$username = $_SESSION['branch_username'];
	$user_level = $_SESSION['branch_userlevel'];
	while ($MENUROW = mysqli_fetch_array($MenuResults)) {
		// Permission check: allow if user_level >= 80, else check tbl_system_permission
		$has_permission = false;
		if ($user_level >= 80) {
			$has_permission = true;
		} else {
			$module = $MENUROW['menu_name'];
			$permission = 'p_view';
			// For 'Order for Approval', require both p_view=1 and p_approver=1
			if ($module === 'Order for Approval') {
				$perm_query = "SELECT 1 FROM tbl_system_permission WHERE username='" . $db->real_escape_string($username) . "' AND modules='" . $db->real_escape_string($module) . "' AND applications='Branch Ordering System' AND p_view=1 AND p_approver=1 LIMIT 1";
			} else {
				$perm_query = "SELECT 1 FROM tbl_system_permission WHERE username='" . $db->real_escape_string($username) . "' AND modules='" . $db->real_escape_string($module) . "' AND applications='Branch Ordering System' AND p_view=1 LIMIT 1";
			}
			$perm_result = mysqli_query($db, $perm_query);
			if ($perm_result && $perm_result->num_rows > 0) {
				$has_permission = true;
			}
		}
		if ($has_permission) {
			$m++;
?>
	<li id="nav<?php echo $m; ?>" data-nav="nav<?php echo $m; ?>" onclick="Check_Permissions('p_view',createRequest,'<?php echo $MENUROW['page_name']; ?>','<?php echo $MENUROW['menu_name']; ?>')">
		<div class="nav-icon"><?php echo $MENUROW['icon_class']; ?></div> <span><?php echo $MENUROW['menu_name']; ?></span>
	</li>
<?php   }
	}
	if ($m === 0) {
		echo "<li>Menu is Empty.</li>";
	}
} else {
	echo "<li>Menu is Empty.</li>";
}
?></ul>
<div class="btn-group nav-bottom-btn" role="group" aria-label="Ronan Sarbon">
	<?php if($_SESSION['branch_userlevel'] == 50 || $_SESSION['branch_userlevel'] >= 80) { ?>
	<button class="btn btn-secondary" onclick="clusterSettings()">Cluster Settings <i class="fa-solid fa-gear"></i></button>
	<?php } ?>
	<button class="btn btn-danger" onclick="closeApps()">Exit <i class="fa-solid fa-right-from-bracket"></i></button>
</div>
<script>
function clusterSettings()
{
	var module = '<?php echo MODULE_NAME; ?>';
	$('#modaltitle').html("CLUSTER SETTINGS");
	$.post("./Modules/" + module + "/apps/cluster_settings.php", { },
	function(data) {
		$('#formmodal_page').html(data);
		$('#formmodal').show();
	});
}

function createRequest(page)
{
	var module = '<?php echo MODULE_NAME; ?>';
	$.post("./Modules/" + module + "/pages/menu_pages.php", { page: page },
	function(data) {
		$('#contents').html(data);
	});
}
$(function()	
{
	if (!sessionStorage.getItem('navwmsbos')) {
		$("#nav1").addClass('active-nav');
		$("#nav1").trigger('click');
	} else {
		$("#"+sessionStorage.navwmsbos).addClass('active-nav');
		$("#"+sessionStorage.navwmsbos).trigger('click');
	}	
	$('.sidebar-nav li').click(function()
	{
		var tab_id = $(this).attr('data-nav');
		sessionStorage.setItem("navwmsbos",tab_id);
		$('.sidebar-nav li').removeClass('active-nav');
		$(this).addClass('sidebar-nav');
		$("#"+tab_id).addClass('active-nav');	
	});
});
function closeApps()
{
	var module = '<?php echo MODULE_NAME; ?>';
	$.post("./Modules/" + module + "/actions/close_applications.php", { },
	function(data) {
		$('#contents').html(data);
	});
}
</script>
