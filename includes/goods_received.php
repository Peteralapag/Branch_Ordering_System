<?php
include '../../../init.php';

// Available statuses
$loadStatus = ['approved','received','cancelled'];

// Show limit options
$show_limit = $_SESSION['BRANCH_SHOW_LIMIT'] ?? 50;
$branch = trim((string)($_SESSION['branch_branch'] ?? ''));
require $_SERVER['DOCUMENT_ROOT']."/Modules/Branch_Ordering_System/class/Class.functions.php";
$function = new WMSFunctions();
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

#statusFilter{width:250px;min-width:200px;}

.reload-data {margin-left:auto;}

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
		    <option value="">All</option>
		    <?php foreach ($loadStatus as $status): ?>
		        <option value="<?php echo $status; ?>">
		            <?php echo ucwords(str_replace('_', ' ', $status)); ?>
		        </option>
		    <?php endforeach; ?>
		</select>
	</div>

	<div class="branch-chip" title="Current branch">
		<i class="fa-solid fa-code-branch"></i>
		<span>Branch</span>
		<strong><?= htmlspecialchars($branch !== '' ? $branch : 'N/A') ?></strong>
	</div>

    <span class="reload-data">
		<span class="show-label">Show</span>
		<select id="limit" style="width:70px" class="form-control form-control-sm" onchange="load_data()">
			<?php echo $function->GetRowLimit($show_limit); ?>
		</select>
	</span>
    
</div>

<div class="tableFixHead" id="smnavdata">
    Loading... <i class="fa fa-spinner fa-spin"></i>
</div>

<script>

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
	$('#smnavdata tbody tr').each(function() {
		let text = $(this).find('td:nth-child(2), td:nth-child(3), td:nth-child(4)').text().toLowerCase();
		$(this).toggle(text.includes(filter));
	});
}

function clearSearch()
{
	$('#search').val('');
	applySearchFilter();
	$('#search').focus();
}


function load_data() {
    let status = $('#statusFilter').val();
    let limit  = $('#limit').val();

    $.post("./Modules/Branch_Ordering_System/includes/goods_received_data.php",
        { status: status, limit: limit },
        function(data) {
            $('#smnavdata').html(data);
			applySearchFilter();
        }
    );
}
</script>
