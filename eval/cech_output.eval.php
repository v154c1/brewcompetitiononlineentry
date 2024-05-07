<?php

$aroma_possible = 12;
$appearance_possible = 3;
$flavor_possible = 20;
$mouthfeel_possible = 5;
$overall_possible = 10;

$score2 = $score * 2;

if ($score == 0 && $row_eval['evalFinalScore'] == 13) {
    $score2 = $row_eval['evalFinalScore'] * 2;
    ?>

    <h2>Pivo nebylo ohodnoceno</h2>
    <h5><?php echo sprintf("%s: %s", $label_overall_impression, $label_comments); ?></h5>
    <p><?php echo htmlentities($row_eval['evalOverallComments']); ?></p>
    <h5 class="header-h5 header-bdr-bottom"><?php echo $label_total; ?><span class="pull-right"><span
                    class="judge-score"><?php echo $row_eval['evalFinalScore']; ?>&nbsp;*&nbsp;2&nbsp;=&nbsp;<?php echo $score2; ?></span>/100</span>
    </h5>
    <?php
} else {

    ?>

    <!-- Appearance -->
    <h5 class="header-h5 header-bdr-bottom"><?php echo $label_appearance; ?><span class="pull-right"><span
                    class="judge-score"><?php echo $row_eval['evalAppearanceScore']; ?></span>/<?php echo $appearance_possible; ?></span>
    </h5><h6>barva, pěna, čirost, držení, struktura, jiné</h6>


    <!-- Aroma -->
    <h5 class="header-h5 header-bdr-bottom"><?php echo $label_aroma; ?><span class="pull-right"><span
                    class="judge-score"><?php echo $row_eval['evalAromaScore']; ?></span>/<?php echo $aroma_possible; ?></span>
    </h5><h6>slad, chmel, kvašení, jiné</h6>

    <!-- Flavor -->
    <h5 class="header-h5 header-bdr-bottom"><?php echo $label_flavor; ?><span class="pull-right"><span
                    class="judge-score"><?php echo $row_eval['evalFlavorScore']; ?></span>/<?php echo $flavor_possible; ?></span>
    </h5><h6>slad, chmel, hořkost, kvašení, vyvážnost, dochuť, jiné</h6>

    <!-- Mouthfeel (Beer only) -->
    <h5 class="header-h5 header-bdr-bottom"><?php echo $label_mouthfeel ?><span class="pull-right"><span
                    class="judge-score"><?php echo $row_eval['evalMouthfeelScore']; ?></span>/<?php echo $mouthfeel_possible; ?></span>
    </h5><h6>tělo, sycení, hřejivost, sametovost, svíravost, jiné</h6>

    <!-- Overall Impression -->
    <h5 class="header-h5 header-bdr-bottom"><?php echo $label_overall_impression; ?><span class="pull-right"><span
                    class="judge-score"><?php echo $row_eval['evalOverallScore']; ?></span>/<?php echo $overall_possible; ?></span>
    </h5><h6>daný styl, vady, požitek</h6>


    <h5><?php echo sprintf("%s: %s", $label_overall_impression, $label_comments); ?></h5>
    <p><?php echo htmlentities($row_eval['evalOverallComments']); ?></p>


    <!-- Total -->
    <!--<h5 class="header-h5 header-bdr-bottom">--><?php //echo $label_flaws; ?><!--</h5>-->
    <!--<div style="margin-top: 10px;">-->
    <!--    --><?php //echo $flaws_table; ?>
    <!--</div>-->

    <h5 class="header-h5 header-bdr-bottom"><?php echo $label_total; ?><span class="pull-right"><span
                    class="judge-score"><?php echo $score; ?>&nbsp;*&nbsp;2&nbsp;=&nbsp;<?php echo $score2; ?></span>/100</span>
    </h5>

    <?php
}
?>