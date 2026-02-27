<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);	
require $_SERVER['DOCUMENT_ROOT']."/Modules/Branch_Ordering_System/class/Class.functions.php";
$function = new WMSFunctions;
if(isset($_SESSION['BRANCH_SHOW_LIMIT']))
{
	$show_limit = $_SESSION['BRANCH_SHOW_LIMIT'];
} else {
	$show_limit = '50';
}

$prnumber = $_POST['prnumber'] ?? '';
$status  = $_POST['status'] ?? '';
$destination = $_POST['destination'] ?? '';
?>
<style>
.smnav-header{
	display:flex;
	align-items:center;
	gap:12px;
	padding:10px 12px;
	border:1px solid var(--bs-border-color, #dee2e6);
	border-radius:10px;
	background:var(--bs-body-bg, #fff);
	flex-wrap:wrap;
}

.smnav-header input[type=text] {
	width:100%;
	padding-left:34px;
	padding-right:34px;
	height:34px;
	border-radius:8px;
	font-size:13px;
}

.smnav-header select {margin-left:10px;width:270px;}

.search-shell{
	position:relative;
	min-width:280px;
	max-width:360px;
	width:100%;
}

.search-magnifying,
.search-xmark{
	position:absolute;
	top:50%;
	transform:translateY(-50%);
	font-size:13px;
	line-height:1;
	color:var(--bs-secondary-color, #6c757d);
}

.search-magnifying{left:11px;pointer-events:none;}
.search-xmark{right:10px;cursor:pointer;opacity:0;pointer-events:none;transition:opacity .15s ease-in-out;}
.search-xmark.is-visible{opacity:1;pointer-events:auto;}

.pr-info{
	display:flex;
	gap:10px;
	align-items:center;
	flex-wrap:wrap;
}

.pr-number,
.pr-destination{
	display:flex;
	align-items:center;
	gap:6px;
	font-size:12px;
	padding:6px 10px;
	border:1px solid var(--bs-border-color, #dee2e6);
	border-radius:8px;
	color:var(--bs-secondary-color, #6c757d);
	background:var(--bs-tertiary-bg, #f8f9fa);
}

.pr-number strong,
.pr-destination strong{
	font-size:13px;
	font-weight:600;
	color:var(--bs-emphasis-color, #212529);
	letter-spacing:0.2px;
}

.pr-number i,
.pr-destination i{
	color:var(--bs-primary, #0d6efd);
	font-size:13px;
}

.right-actions{margin-left:auto;}

.reload-data {display:flex;gap:15px;margin-left:auto;right:0;}
.tableFixHead {margin-top:15px;background:#fff;overflow:auto;height:calc(100vh - 222px);width:100%;border:1px solid var(--bs-border-color, #dee2e6);border-radius:10px;}
.tableFixHead thead th {position:sticky;top:0;z-index:1;background:#0cccae;color:#fff;}
.tableFixHead table  {border-collapse:collapse;}
.tableFixHead th, .tableFixHead td {font-size:14px;white-space:nowrap;}

@media (max-width: 992px){
	.right-actions{margin-left:0;}
	.search-shell{max-width:none;}
}
</style>


<div class="smnav-header">
	<span style="display:flex;gap:10px">

		<div class="search-shell">
			<input id="search" type="text" class="form-control form-control-sm" placeholder="Search Item" autocomplete="off" aria-label="Search item">	
			<i class="fa-sharp fa-solid fa-magnifying-glass search-magnifying"></i>
			<i class="fa-solid fa-circle-xmark search-xmark" onclick="clearSearch()" title="Clear search"></i>
		</div>
	</span>

	<div class="pr-info">
		<div class="pr-number">
			<i class="fa-solid fa-file-lines"></i>
			<span>PR #</span>
			<strong><?= $prnumber ?></strong>
		</div>

		<div class="pr-destination">
			<i class="fa-solid fa-location-dot"></i>
			<span>Destination</span>
			<strong><?= $destination ?></strong>
		</div>
	</div>

	<?php if ($status == 'pending' || $status == 'returned'):?>
	<button type="button" class="btn btn-secondary btn-sm" onclick="reviseitems('<?= $prnumber ?>','<?= $status?>')">
	    <i class="fa-solid fa-edit"></i> Revise items
	</button>
	<?php endif; ?>

	<div class="right-actions">
		<button class="btn btn-primary btn-sm" onclick="bactomain()">
			<i class="fa fa-arrow-left"></i> Back to Main
		</button>
	</div>
</div>


<div class="tableFixHead" id="smnavdata">Loading... <i class="fa fa-spinner fa-spin"></i></div>



<script>

function reviseitems(prnumber, status)
{
    if (status !== 'pending' && status !== 'returned') {
        swal("Not Allowed", "Only pending PR can be revised.", "warning");
        return;
    }

   	$.post("./Modules/Branch_Ordering_System/includes/purchase_request_add.php", { prnumber: prnumber, status: status },
	function(data) {		
		$('#contents').html(data);

	});

}


function bactomain(){

	$('#contents').load('./Modules/Branch_Ordering_System/includes/purchase_request.php');
}


$(function()
{
	$('#search').on('input', function()
	{
		applySearchFilter();
	});
	applySearchFilter();
	load_data();
});

function applySearchFilter()
{
	let filter = $('#search').val().toLowerCase().trim();
	$('.search-xmark').toggleClass('is-visible', filter.length > 0);
	$('#itemsTable tbody tr').each(function() {
		let text = $(this).find('td:nth-child(2), td:nth-child(1)').text().toLowerCase();
		$(this).toggle(text.includes(filter));
	});
}

function clearSearch()
{
	$('#search').val('');
	applySearchFilter();
	$('#search').focus();
}
function load_data()
{
	var prnumber = '<?= $prnumber?>';
	var status = '<?= $status?>';
	var limit = $('#limit').val();
	$.post("./Modules/Branch_Ordering_System/apps/purchase_request_view_form.php", { limit: limit, status: status, prnumber: prnumber },
	function(data) {
		$('#smnavdata').html(data);
		applySearchFilter();
	});
}
</script>