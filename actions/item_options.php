<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

define("MODULE_NAME", "Branch_Ordering_System");
require_once($_SERVER['DOCUMENT_ROOT']."/Modules/".MODULE_NAME."/class/Class.functions.php");

$recipient = $_POST['recipient'];
$moduleCode = 'Branch_Ordering_System';

if(isset($_POST['search']) && !empty($_POST['search'])) {
    $search = $_POST['search'];
    $sqlQuery = "SELECT * FROM wms_itemlist wi
                 WHERE (wi.item_description LIKE ? OR wi.item_code LIKE ?)
                 AND wi.recipient = ?
                 AND wi.active = 1
                 AND (
                    NOT EXISTS (
                        SELECT 1 FROM wms_item_module_visibility mv0
                        WHERE mv0.item_id = wi.id AND mv0.active=1
                    )
                    OR EXISTS (
                        SELECT 1 FROM wms_item_module_visibility mv1
                        WHERE mv1.item_id = wi.id AND mv1.module_code = ? AND mv1.active=1
                    )
                 )";
} else {
    $sqlQuery = "SELECT * FROM wms_itemlist wi
                 WHERE wi.recipient = ?
                 AND wi.active=1
                 AND (
                    NOT EXISTS (
                        SELECT 1 FROM wms_item_module_visibility mv0
                        WHERE mv0.item_id = wi.id AND mv0.active=1
                    )
                    OR EXISTS (
                        SELECT 1 FROM wms_item_module_visibility mv1
                        WHERE mv1.item_id = wi.id AND mv1.module_code = ? AND mv1.active=1
                    )
                 )";
}
$stmt = $db->prepare($sqlQuery);

if (isset($search) && !empty($search)) {
    $searchTerm = "%" . $search . "%";
    $stmt->bind_param("ssss", $searchTerm, $searchTerm, $recipient, $moduleCode);
} else {
    $stmt->bind_param("ss", $recipient, $moduleCode);
}

$stmt->execute();
$results = $stmt->get_result();

echo '<ul class="searchlist">';
if ($results->num_rows > 0) {
    while ($ITEMSROW = $results->fetch_assoc()) {
        $item_code = $ITEMSROW['item_code'];
        $item = $ITEMSROW['item_description'];
        $uom = $ITEMSROW['uom'];
        $unitprice = $ITEMSROW['unit_price'];
        ?>
        <li onclick="addToForm('<?php echo htmlspecialchars($item_code, ENT_QUOTES, 'UTF-8'); ?>')"><?php echo htmlspecialchars($item, ENT_QUOTES, 'UTF-8'); ?></li>
        <?php
    }
} else {
    echo "<li>No Record.</li>";
}
$stmt->close();
$db->close();
?>
