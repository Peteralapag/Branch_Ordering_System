<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);	
require $_SERVER['DOCUMENT_ROOT']."/Modules/Branch_Ordering_System/class/Class.functions.php";
$function = new WMSFunctions;

$show_limit = isset($_SESSION['BRANCH_APPROVAL_LIMIT']) ? $_SESSION['BRANCH_APPROVAL_LIMIT'] : 25;
if(isset($_SESSION['BRANCH_APPROVAL_BRANCH']))
{
	$branch = $_SESSION['BRANCH_APPROVAL_BRANCH'];
} else {
	$branch = $_SESSION['branch_branch'];
}
$userlevel = $_SESSION['branch_userlevel'];

if(isset($_SESSION['BRANCH_APPROVAL_RECIPIENT']))
{
	$recipient = $_SESSION['BRANCH_APPROVAL_RECIPIENT'];
} else {
	$recipient = '';
}
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
				<div class="input-group">
					<input id="search" type="text" class="form-control form-control-sm" placeholder="Search" autocomplete="off">
					<button class="btn btn-primary btn-sm btn-search" type="button"><i class="fa-solid fa-magnifying-glass"></i></button>
				</div>
				<div class="input-label">SEARCH</div>
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
}
function performSearch()
{
	var module = '<?php echo MODULE_NAME; ?>';
    let search = $('#search').val();
    let limit = $('#limit').val();
    let branch = $('#branchsearch').val();
    let recipient = $('#recipient').val();
    rms_reloaderOn("Searching...");
    $.post("./Modules/" + module + "/includes/order_for_approval_data.php", 
        { search: search, branch: branch, limit: limit, recipient: recipient }, 
        function(data) {
            $('#pagesdata').html(data);
            rms_reloaderOff();
        }
    );        
}
$(document).ready(function ()
{
	var module = '<?php echo MODULE_NAME; ?>';	
	
	$('#search').on('change', performSearch);
	$('.btn-search').on('click', performSearch);
	
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
	        var branch = $('#branchsearch').val();
	        var orderstatus = $('#status').val();
	        var recipient = $('#recipient').val();
	        $.post('./Modules/' + module + '/includes/order_for_approval_data.php', { limit: limit, branch: branch, page: page, recipient: recipient }, function (data) {
	            $('#pagesdata').html(data);
	        });
	    });
	})
});
function loadPage(page)
{
	var module = '<?php echo MODULE_NAME; ?>';
	var limit = $('#limit').val();
	var branch = $('#branchsearch').val();
	var orderstatus = $('#status').val();
	var recipient = $('#recipient').val();
	rms_reloaderOn('Loading...');
    $.post('./Modules/' + module + '/includes/order_for_approval_data.php', { page: page, branch: branch, limit: limit, recipient: recipient }, function(data) {	     
        $('#pagesdata').html(data);       
    });
}
function load_data()
{
	var module = '<?php echo MODULE_NAME; ?>';
	var limit = $('#limit').val();
	var branch = $('#branchsearch').val();
	var recipient = $('#recipient').val();
	var orderstatus = $('#status').val();
	var page = 1;
	rms_reloaderOn('Loading...');
	$.post("./Modules/" + module + "/includes/order_for_approval_data.php", { page: page, branch: branch, limit: limit, recipient: recipient },
	function(data) {
		$('#pagesdata').html(data);
		rms_reloaderOff();
	});
}
</script>
