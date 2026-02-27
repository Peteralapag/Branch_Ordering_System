<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);	
require $_SERVER['DOCUMENT_ROOT']."/Modules/Branch_Ordering_System/class/Class.functions.php";
$function = new WMSFunctions;

$show_limit = isset($_SESSION['BRANCH_REQUEST_LIMIT']) ? $_SESSION['BRANCH_REQUEST_LIMIT'] : 25;
if(isset($_SESSION['BRANCH_REQUEST_BRANCH']))
{
	$branch = $_SESSION['BRANCH_REQUEST_BRANCH'];
} else {
	$branch = $_SESSION['branch_branch'];
}
$userlevel = $_SESSION['branch_userlevel'];

if(isset($_SESSION['BRANCH_REQUEST_RECIPIENT']))
{
	$recipient = $_SESSION['BRANCH_REQUEST_RECIPIENT'];
} else {
	$recipient = '';
}

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
if(isset($_SESSION['BRANCH_REQUEST_RECIPIENT']))
{
	$recipient = $_SESSION['BRANCH_REQUEST_RECIPIENT'];
} else {
	$recipient = '';
}
// $order_count = $function->getPendingOrder($kwiri,$db);
$order_count = 0;
?>
<style>
.pages-wrapper {display: flex;flex-direction: column;height: 100%;gap: 10px;}
.pages-header {display: flex;justify-content: space-between;width: 100%;padding: 10px;border-radius: 7px 7px 0px 0px;border: 1px solid #aeaeae;
border-bottom: 5px solid #aeaeae;background: #fff;}
.pages-data {border: 1px solid #aeaeae; flex: 1;width: 100%;position:relative; background:#fff}
.select-control {display: flex;align-items: center;gap: 10px;width: auto;max-width: 100%}
.select-control select {width: auto !important}
.tableFixHead {overflow: auto;height: calc(100vh - 650px) !important;width: 100%;}
.tableFixHead thead th,
.tableFixHead tfoot th {position: sticky;background: #0091d5; color: #fff;z-index: 1;}
.tableFixHead thead th {top: 0;}
.tableFixHead tfoot th {bottom: 0;}
.tableFixHead table {border-collapse: collapse;}
.tableFixHead th, .tableFixHead td {font-size: 14px; white-space: nowrap;}
.reload-data {display: flex;gap: 15px;margin-left: auto;right:0;}
.branch-search {position:absolute;width: 100%;;max-height:250px;z-index:3;margin-top: 5px;border: 1px solid #f1f1f1;background: #fff;border-radius: 0px 0px 5px 5px;
box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.5);overflow: hidden;overflow-y: auto;}
.input-label {position: absolute;top:-11px;font-size:10px;left:5px;color: dodgerblue;letter-spacing:7px;font-weight: 600}
</style>
<div class="pages-wrapper">
    <div class="pages-header">
    	<div class="select-control">
    		<span style="position:relative">
    		<div class="input-label">RECIPIENTS</div>
    		<select id="recipient" class="form-control form-control-sm" onchange="load_data()">
    			<option value="">--- RECIPIENTS ---</option>
    			<?php echo $function->GetRecipient($recipient,'MRS',$db)?>
    		</select>
    		<div class="input-label">RECIPIENTS</div>
    		</span>
    		<?php if($userlevel >= 50) { ?>
			<span style="position:relative">
				<input id="branchsearch" type="text" class="form-control form-control-sm"  placeholder="--- Select Branch ---" autocomplete="off" value="<?php echo $branch?>">
				<div class="branch-search">
					<div id="branchsearchdata"></div>
				</div>
			<div class="input-label">BRANCH</div>
			</span>
			<?php } else { ?>
			<span style="position:relative">
				<input id="branchsearch" type="text" style="text-align:center; width:220px" class="form-control form-control-sm" value="<?php echo $_SESSION['branch_branch']; ?>" disabled>
				<div class="input-label">BRANCH</div>
			</span>
			<?php } ?>			
			<span style="position:relative">
				<button class="btn btn-primary btn-sm" onclick="genererateRequest('new')"><i class="fa-solid fa-cart-plus"></i> Generate Order</button>
			</span>
    	</div>
   		<span class="reload-data">
		<span style="margin-left:20px;margin-top:4px;">Show</span>
			<select id="limit" style="width:70px" class="form-control form-control-sm" onchange="load_data()">
				<?php echo $function->GetRowLimit($show_limit); ?>
			</select>
		</span>
    </div>
    <div class="pages-data tableFixHead" id="pagesdata"></div>
</div>
<script>
function selectedBranch(branch)
{
    let searchDropdown = $(".branch-search");
	$('#branchsearch').val(branch);
	searchDropdown.stop(true, true).slideUp(200);
	load_data();
	console.log(branch);
}
function genererateRequest(params)
{	
	var module = '<?php echo MODULE_NAME; ?>';
	var orderCount = '<?php echo $order_count?>';
	if( orderCount > 0)
	{
		if(checkGenerateRecipient() == 1)		
		{
			swal("Request Denied", "You have " + orderCount + " pending orders awaiting receipt. Please complete them in 'My Orders' to continue.", "error");
			return false;	
		}
	}
	var form_type = "MRS";
	var recipient = $('#recipient').val();
	var branch = $('#branchsearch').val();
	$('#modaltitle').html("CREATE ORDER REQUEST");
	$.post("./Modules/" + module + "/apps/request_form.php", { params: params, branch: branch, form_type: form_type, recipient: recipient },
	function(data) {		
		$('#formmodal_page').html(data);
		$('#formmodal').show();
	});
}
function checkGenerateRecipient()
{
		var module = '<?php echo MODULE_NAME; ?>';
	var recipient = $('#recipient').val()
	$.post("./Modules/" + module + "/actions/chech_pending_recipient.php", { recipient: recipient },
	function(data) {		
		if(data === 1)
		{
			return 1;
		} else {
			return 0;
		}
	});
}
$(document).ready(function()
{
	var module = '<?php echo MODULE_NAME; ?>';
    let searchBox = $("#branchsearch");
    let searchDropdown = $(".branch-search");
    searchDropdown.hide();
    searchBox.on("focus input", function () {
        searchDropdown.stop(true, true).slideDown(200);
        $.post('./Modules/' + module + '/actions/apps_load_branches.php', { },
		function (data) {
            $('#branchsearchdata').html(data);
        });
    })
    $(document).on("click", function (e) {
        if (!searchBox.is(e.target) && !searchDropdown.is(e.target) && searchDropdown.has(e.target).length === 0) {
            searchDropdown.stop(true, true).slideUp(200);
        }
    });    
    $('#branchsearch').keyup(function()
    {
		$.post('./Modules/' + module + '/actions/apps_load_branches.php', { },
		function (data) {
            $('#branchsearchdata').html(data);
        });
    });
	load_data(1);
	$(document).on('click', '.pagination-link:not(.disabled)', function(e) {
        e.preventDefault();
        var page = $(this).data('page');
        loadPage(page);
    });
	load_data(1);
	document.querySelectorAll('.pagination-btn').forEach(button => {
	    button.addEventListener('click', function () {
	        const page = this.getAttribute('data-page');
	        var limit = $('#limit').val();
	        var recipient = $('#recipient').val();
	        var branch = $('#branchsearch').val();
	        $.post('./Modules/' + module + '/includes/create_request_data.php', { limit: limit, branch: branch, page: page, recipient: recipient }, function (data) {
	            $('#pagesdata').html(data);
	        });
	    });
	})
});
function loadPage(page)
{
	var module = '<?php echo MODULE_NAME; ?>';
	var limit = $('#limit').val();
	var status = $('#status').val();
	var recipient = $('#recipient').val();
	var branch = $('#branchsearch').val();
	rms_reloaderOn('Loading...');
    $.post('./Modules/' + module + '/includes/create_request_data.php', { page: page, branch: branch, limit: limit, recipient: recipient }, function(data) {	     
        $('#pagesdata').html(data);       
    });
}
function load_data()
{
	var module = '<?php echo MODULE_NAME; ?>';
	var limit = $('#limit').val();
	var recipient = $('#recipient').val();
	var branch = $('#branchsearch').val();
	var page = 1;
	$.post("./Modules/" + module + "/includes/create_request_data.php", { page: page, branch: branch, limit: limit, recipient: recipient },
	function(data) {
		$('#pagesdata').html(data);
	});
}
</script>
