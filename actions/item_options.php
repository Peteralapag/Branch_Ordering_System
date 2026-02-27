<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

define("MODULE_NAME", "Branch_Ordering_System");
require_once($_SERVER['DOCUMENT_ROOT']."/Modules/".MODULE_NAME."/class/Class.functions.php");

$recipient = $_POST['recipient'];

if(isset($_POST['search']) && !empty($_POST['search'])) {
    $search = $_POST['search'];
    $sqlQuery = "SELECT * FROM wms_itemlist WHERE (item_description LIKE ? OR item_code LIKE ?) AND recipient = ? AND active= ? AND active=1";
} else {
    $sqlQuery = "SELECT * FROM wms_itemlist WHERE recipient = ? AND active=1";
}
$stmt = $db->prepare($sqlQuery);

if (isset($search) && !empty($search)) {
    $searchTerm = "%" . $search . "%";
    $item_status = 1;
    $stmt->bind_param("sssi", $searchTerm, $searchTerm, $recipient, $item_status);
} else {
    $stmt->bind_param("s", $recipient);
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
        $supplier_id = $ITEMSROW['supplier_id'];
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
