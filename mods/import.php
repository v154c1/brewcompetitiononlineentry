<?php
if ($go == "judging_scores" && $action == "default") {

    $stylesTable = $prefix . "styles";
    $brewingTable = $prefix . "brewing";
    $scoresTable = $prefix . "judging_scores";
    $tablesTable = $prefix . "judging_tables";
    $brewersTables = $prefix . "brewer";


    function get_tables()
    {
        global $tablesTable;
        global $connection;
        $db = new MysqliDb($connection);
        $db->orderBy("tableNumber", "asc");
        return $db->get($tablesTable, null, "id as tableId, tableName, tableStyles");

    }

    ?>
    <script>
        function import_score_for_table(tableId) {
            jQuery.ajax({
                url: ajax_url + "import_scores.ajax.php?filter=" + tableId, success: (data) => {
                    console.log(data);
                    location.reload();
                }, error: (err) => alert(err)
            });
        }


    </script>
    <h2>IMPORT</h2>

    <?php
    foreach (get_tables() as $table) {
        ?>
        <button class="btn btn-primary" onclick="import_score_for_table(<?php echo $table['tableId'] ?>)">Import scores
            for table <?php echo $table['tableName'] ?></button><br><br>

        <?php

    }

    ?> 

    <button class="btn btn-primary" onclick="import_score_for_table(0)">Import scores without table</button><br><br>
    <?php
}
?>
