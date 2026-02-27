<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
define("MODULE_NAME", "Branch_Ordering_System");
require_once($_SERVER['DOCUMENT_ROOT']."/Modules/".MODULE_NAME."/class/Class.functions.php");
$recipient = $_POST['recipient'];
?>
<style>
.item-description {padding: 8px 5px 8px 5px;border-bottom:2px solid #f1f1f1;margin-bottom: 5px;text-align:center;font-weight: 600;color: #fff}
.item-options {height: 200px;max-height: 300px;overflow: auto;padding:2px;}
.item-search {padding:2px;margin-bottom: 2px;position:relative}
.search-mglass {position:absolute;top:5px;margin-left:5px;}
.search-xmark {position:absolute;top: 3px;right: 5px;font-size:20px;cursor: pointer;}
.search-xmark:hover {color: red;}
.item-search input {
	padding: 5px 30px 5px 25px;
	background: #f2f9f2
}


.item-options ul {list-style-type: none;margin: 0;padding: 0;font-size: 14px;}
.item-options li {
	border-bottom: 1px solid #cecece;
	padding: 5px 5px 5px 10px;
}

</style>
<div class="item-description bg-primary">
	ITEM DESCRIPTION
</div>
<div class="item-search">
	<input id="searchitems" type="text" class="form-control form-control-sm" placeholder="Search Item / Item code Here" autocomplete="off">
	<i class="fa-solid fa-magnifying-glass search-mglass"></i>
	<i class="fa-solid fa-circle-xmark search-xmark" onclick="clearSearch()"></i>
</div>
<div class="item-options">
	<ul id="itemoptionsdata"></ul>
</div>
<div style="padding:5px;text-align:center;background: silver">
	<button class="btn btn-danger btn-sm" onclick="closeMarket()">Close</button>
</div>
<script>
function addToForm(itemcode)
{
	var mode = 'getiteminformation';
	var module = '<?php echo MODULE_NAME; ?>';
	$.post("./Modules/" + module + "/actions/actions.php", { mode: mode, item_code: itemcode },
	function(data) {		
		$('#inforesults').html(data);
		$('.shop-overlay').fadeOut();
	});
}
$(document).ready(function()
{
	$('#searchitems').keyup(function()
	{
		var module = '<?php echo MODULE_NAME; ?>';
		var search = $('#searchitems').val();
		var recipient ='<?php echo $recipient; ?>';
		$.post("./Modules/" + module + "/actions/item_options.php", { search: search, recipient: recipient },
		function(data) {		
			$('#itemoptionsdata').html(data);
		});
	});
	loadOptionData();
});
function loadOptionData()
{
	var module = '<?php echo MODULE_NAME; ?>';
	var recipient ='<?php echo $recipient; ?>';
	$.post("./Modules/" + module + "/actions/item_options.php", { recipient: recipient },
	function(data) {		
		$('#itemoptionsdata').html(data);
	});
}
</script>