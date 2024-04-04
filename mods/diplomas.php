<?php

if (judging_winner_display($_SESSION['prefsWinnerDelay'])) {
    ?>
    <h2>Diplomová umístění</h2>

    <?php
    $stylesTable = $prefix . "styles";
    $brewingTable = $prefix . "brewing";
    $scoresTable = $prefix . "judging_scores";
    $brewersTables = $prefix . "brewer";


    function get_styles()
    {
        global $brewingTable;
        global $connection;
        $db = new MysqliDb($connection);
        return $db->get($brewingTable, null, "DISTINCT brewStyle");

    }

    function get_entries($style)
    {
        global $brewingTable;
        global $scoresTable;
        global $brewersTables;
        global $connection;

        $db = new MysqliDb($connection);
        $db->join("$scoresTable score", "score.eid=brewing.id", "LEFT");
        $db->join("$brewersTables brewers", "brewers.id=brewing.brewBrewerID", "LEFT");
        $db->where('brewStyle', $style);
        $db->where('scoreEntry > 35');
        $db->orderBy("score.scoreEntry", "desc");
        return $db->get("$brewingTable brewing", null, "brewing.id as brewId, brewBrewerLastName, brewBrewerFirstName, brewName, brewCoBrewer, brewStyle, brewJudgingNumber, brewPaid, brewReceived, score.scoreEntry, brewerClubs");
    }

    $styles = get_styles();

    if (count($styles) > 0) {
        foreach ($styles as $stylearray) {
            $style = $stylearray['brewStyle'];


            $entries = get_entries($style);

            if (count($entries) > 0) {
                ?>
                <h3><?php echo $style; ?></h3>


                <table class="table table-responsive table-striped table-bordered dataTable no-footer"
                       id="sortableB82158682917810"
                       role="grid">
                    <thead>
                    <tr role="row">
                        <th nowrap="" class="sorting_disabled" tabindex="0" aria-controls="sortableB82158682917810"
                            rowspan="1"
                            colspan="1" aria-label="Místo: activate to sort column descending" style="width: 46px;">
                            Diplom
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
                    foreach ($entries as $row_sql) {
                        $score = '';
                        $diplomClass = '';

                        $scoreEntry = $row_sql['scoreEntry'];
                        if ($scoreEntry) {
                            $score = $scoreEntry * 2;
                            if ($score >= 90) {
                                $diplomClass = "goldenDiplom";
                                $trophyClass = "text-gold";
                            } else if ($score > 80) {
                                $diplomClass = "silverDiplom";
                                $trophyClass = "text-silver";
                            } else if ($score > 70) {
                                $diplomClass = "bronzeDiplom";
                                $trophyClass = "text-bronze";
                            }
                        }

                        ?>
                        <tr role="row" class="odd">
                            <td width="1%" nowrap=""><span
                                        class="fa fa-lg fa-trophy <?php echo $trophyClass; ?> "></span>
                            </td>
                            <td width="25%"><?php
                                echo $row_sql['brewBrewerFirstName'] . " " . $row_sql['brewBrewerLastName'];
                                if ($row_sql['brewCoBrewer']) {
                                    echo '<br>' . $label_cobrewer . ':&nbsp;' . $row_sql['brewCoBrewer'];
                                }
                                ?></td>
                            <td><?php echo $row_sql['brewName']; ?></td>
                            <td><?php echo $score; ?></td>
                            <td width="25%"><?php echo $row_sql['brewStyle']; ?></td>
                            <td width="25%"><?php echo $row_sql['brewerClubs']; ?></td>
                        </tr>

                        <?php

                    }
                    ?>
                    </tbody>
                </table>


                <?php
            }
        }
    }
}
?>

