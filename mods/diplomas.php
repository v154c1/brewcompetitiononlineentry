<h2>Diplomová umístění</h2>

<?php
$stylesTable = $prefix . "styles";
$brewingTable = $prefix . "brewing";
$scoresTable = $prefix . "judging_scores";
$brewersTables = $prefix . "brewer";

$style_sql = strtr('SELECT DISTINCT brewStyle FROM {brewingTable}',
    array(
        '{brewingTable}' => $brewingTable
    )
);

$ssql = mysqli_query($connection, $style_sql) or die (mysqli_error($connection));
$row_ssql = mysqli_fetch_assoc($ssql);
$totalRows_ssql = mysqli_num_rows($ssql);

if ($totalRows_ssql > 0) {
    do {

        $style = $row_ssql['brewStyle'];
        $query_sql = strtr('SELECT * FROM {brewingTable} LEFT JOIN {scoresTable} ON {brewingTable}.id = {scoresTable}.eid LEFT JOIN {brewersTable} ON {brewingTable}.brewBrewerID = {brewersTable}.id  WHERE `{brewingTable}`.`brewStyle` = \'{style}\'  AND {scoresTable}.scoreEntry > 35 ORDER BY {brewingTable}.brewCategorySort, {scoresTable}.scoreEntry DESC',
            array(
                '{brewingTable}' => $brewingTable,
                '{scoresTable}' => $scoresTable,
                '{brewersTable}' => $brewersTables,
                '{style}' => $style
            )
        );

        $sql = mysqli_query($connection, $query_sql) or die (mysqli_error($connection));
        $row_sql = mysqli_fetch_assoc($sql);
        $num_fields = mysqli_num_fields($sql);
        $totalRows_sql = mysqli_num_rows($sql);


        if ($totalRows_sql > 0) {
            ?>
            <h3><?php echo $style; ?></h3>


            <table class="table table-responsive table-striped table-bordered dataTable no-footer"
                   id="sortableB82158682917810"
                   role="grid">
                <thead>
                <tr role="row">
                    <th nowrap="" class="sorting_disabled" tabindex="0" aria-controls="sortableB82158682917810"
                        rowspan="1"
                        colspan="1" aria-label="Místo: activate to sort column descending" style="width: 46px;">Diplom
                    </th>
                    <th class="sorting_disabled" tabindex="0" aria-controls="sortableB82158682917810" rowspan="1"
                        colspan="1"
                        aria-label="Sládek: activate to sort column descending" style="width: 194px;">Sládek
                    </th>
                    <th class="sorting_disabled" tabindex="0" aria-controls="sortableB82158682917810" rowspan="1"
                        colspan="1"
                        aria-label="Vzorek Jméno: activate to sort column descending" style="width: 131px;"><span
                                class="hidden-xs hidden-sm hidden-md">Vzorek </span>Jméno
                    </th>
                    <th class="sorting_disabled" tabindex="0" aria-controls="sortableB82158682917810" rowspan="1"
                        colspan="1"
                        aria-label="Styl: activate to sort column descending" style="width: 194px;">Score
                    </th>

                    <th class="sorting_disabled" tabindex="0" aria-controls="sortableB82158682917810" rowspan="1"
                        colspan="1"
                        aria-label="Styl: activate to sort column descending" style="width: 194px;">Styl
                    </th>
                    <th class="sorting_disabled" tabindex="0" aria-controls="sortableB82158682917810" rowspan="1"
                        colspan="1"
                        aria-label="Klub: activate to sort column descending" style="width: 195px;">Klub
                    </th>
                </tr>
                </thead>
                <tbody<?php
                do {
                    $score = '';
                    $diplomClass = '';

                    $scoreEntry = $row_sql['scoreEntry'];
                    if ($scoreEntry) {
                        $score = $scoreEntry * 2;
                        if ($score >= 90) {
                            $diplomClass = "goldenDiplom";
                            $trophyClass="text-gold";
                        } else if ($score > 80) {
                            $diplomClass = "silverDiplom";
                            $trophyClass="text-silver";
                        } else if ($score > 70) {
                            $diplomClass = "bronzeDiplom";
                            $trophyClass="text-bronze";
                        }
                    }

                ?>
                <tr role="row" class="odd">
                    <td width="1%" nowrap=""><span class="fa fa-lg fa-trophy <?php echo $trophyClass; ?> "></span></td>
                    <td width="25%"><?php
                        echo $row_sql['brewBrewerFirstName'] . " " . $row_sql['brewBrewerLastName'];
                        if ($row_sql['brewCoBrewer']) {
                            echo '<br>'.$label_cobrewer.':&nbsp;'.$row_sql['brewCoBrewer'];
                        }
                        ?></td>
                    <td><?php echo $row_sql['brewName'];?></td>
                    <td><?php echo $score;?></td>
                    <td width="25%"><?php echo $row_sql['brewStyle']; ?></td>
                    <td width="25%"><?php echo $row_sql['brewerClubs'];?></td>
                </tr>

                <?php

 } while ($row_sql = mysqli_fetch_assoc($sql));
                ?>
                </tbody>
            </table>


            <?php
        }
    } while ($row_ssql = mysqli_fetch_assoc($ssql));
}
?>

