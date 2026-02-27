<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);	
require $_SERVER['DOCUMENT_ROOT']."/Modules/Branch_Ordering_System/class/Class.functions.php";
$function = new WMSFunctions;
$date_from = date("Y-m-01");
$function = new WMSFunctions;
$category = '';

$branch = $_SESSION['branch_branch'] ?? '';

if(isset($_SESSION['BRANCH_SHOW_LIMIT']))
{
	$show_limit = $_SESSION['BRANCH_SHOW_LIMIT'];
} else {
	$show_limit = '50';
}

$loadStatus = [
    'pending',
    'approved',
    'rejected',
    'for_canvassing',
    'canvassing_reviewed',
    'canvassing_approved',
    'partial_conversion',
    'converted',
    'convert_rejected'
];

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

.search-shell{
	position:relative;
	min-width:300px;
	max-width:420px;
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

.filter-group,
.reload-data{
	display:flex;
	align-items:center;
	gap:8px;
}

.filter-label,
.show-label{
	font-size:13px;
	font-weight:600;
	color:var(--bs-secondary-color, #6c757d);
	margin:0;
	white-space:nowrap;
}

.branch-chip{
	display:flex;
	align-items:center;
	gap:6px;
	padding:6px 10px;
	border:1px solid var(--bs-border-color, #dee2e6);
	border-radius:8px;
	background:var(--bs-tertiary-bg, #f8f9fa);
	font-size:12px;
	color:var(--bs-secondary-color, #6c757d);
}

.branch-chip i{color:var(--bs-primary, #0d6efd);font-size:12px;}
.branch-chip strong{color:var(--bs-emphasis-color, #212529);font-size:13px;font-weight:600;}

#statusFilter{width:260px;min-width:220px;}

.reload-data{
	margin-left:auto;
}

.tableFixHead {
	margin-top:15px;
	background:#fff;
	overflow:auto;
	height:calc(100vh - 222px);
	width:100%;
	border:1px solid var(--bs-border-color, #dee2e6);
	border-radius:10px;
}

.tableFixHead thead th { position: sticky; top: 0; z-index: 1; background:#0cccae; color:#fff }
.tableFixHead table  { border-collapse: collapse;}
.tableFixHead th, .tableFixHead td { font-size:14px; white-space:nowrap }

@media (max-width: 992px){
	.reload-data{margin-left:0;}
	.search-shell{max-width:none;}
	#statusFilter{width:100%;min-width:190px;}
}
</style>

<div class="smnav-header">
	<span style="display:flex;gap:10px">

		<div class="search-shell">
			<input id="search" type="text" class="form-control form-control-sm" placeholder="Search purchase request" autocomplete="off" aria-label="Search purchase request">	
			<i class="fa-sharp fa-solid fa-magnifying-glass search-magnifying"></i>
			<i class="fa-solid fa-circle-xmark search-xmark" onclick="clearSearch()" title="Clear search"></i>

		</div>
	</span>
	
	<div class="filter-group">
		<span class="filter-label">Status</span>
		<select id="statusFilter" class="form-control form-control-sm" onchange="load_data()">
	    <option value="pending" selected>Pending</option>
	    <?php foreach ($loadStatus as $status): ?>
	        <?php if ($status !== 'pending'): ?>
	        <option value="<?php echo $status; ?>">
	            <?php echo ucwords(str_replace('_', ' ', $status)); ?>
	        </option>
	        <?php endif; ?>
	    <?php endforeach; ?>
		</select>
	</div>

	<div class="branch-chip" title="Current branch">
		<i class="fa-solid fa-code-branch"></i>
		<span>Branch</span>
		<strong><?= htmlspecialchars(trim((string)$branch) !== '' ? $branch : 'N/A') ?></strong>
	</div>
	
	<button class="btn btn-success btn-sm" onclick="addpurchaserequest()"><i class="fa fa-plus"></i> Add Items</button>
	<span class="reload-data">
		<span class="show-label">Show</span>
		<select id="limit" style="width:70px" class="form-control form-control-sm" onchange="load_data()">
			<?php echo $function->GetRowLimit($show_limit); ?>
		</select>
	</span>
</div>
<div class="tableFixHead" id="smnavdata">Loading... <i class="fa fa-spinner fa-spin"></i></div>


<script>

function addpurchaserequest()
{
	$.post("./Modules/Branch_Ordering_System/includes/purchase_request_add.php", { },
	function(data) {		
		$('#contents').html(data);

	});
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
	$('#purchaserequesttable tbody tr').each(function() {
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
	var limit = $('#limit').val();
    var status = $('#statusFilter').val();
    
	$.post("./Modules/Branch_Ordering_System/includes/purchase_request_data.php", { limit: limit, status: status },
	function(data) {
		$('#smnavdata').html(data);
		applySearchFilter();
	});
}
</script>
