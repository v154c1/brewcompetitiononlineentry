<?php


$aroma_possible = 8;
$appearance_possible = 2;
$flavor_possible = 10;
$mouthfeel_possible = 10;
$overall_possible = 10;

$style_correctness_possible = 10;

//if (is_numeric($row_eval['evalStyleAccuracy'])) {
//    $score = $score+ $row_eval['evalStyleAccuracy'];
//}

$overall_score_processed = $row_eval['evalOverallScore'] - $row_eval['evalStyleAccuracy'];

$score2=$score *2;
asort($flaws);

// Build Flaws Table
$cols_display = 2;
$html_output = array();

if (!empty($row_eval['evalFlaws'])) {
    $entry_flaws = str_replace(", ", ",", $row_eval['evalFlaws']); // remove spaces between flaws
    $entry_flaws = explode(",", $entry_flaws); // convert flaws string to array
}

foreach ($flaws as $flaw) {

    $flaw_level = "";
    $flaw_needle_low = $flaw." ".$label_low;
    $flaw_needle_med = $flaw." ".$label_med;
    $flaw_needle_high = $flaw." ".$label_high;

    if (is_array($entry_flaws)) {
        if (in_array($flaw_needle_low, $entry_flaws)) $flaw_level = $label_low;
        if (in_array($flaw_needle_med, $entry_flaws)) $flaw_level = $label_med;
        if (in_array($flaw_needle_high, $entry_flaws)) $flaw_level = $label_high;
    }

    $html_output[] = sprintf("<td width=\"35%%\">%s</td><td width=\"15%%\">%s</td>",$flaw,$flaw_level);

}

$cols_difference = count($html_output) % $cols_display;

if ($cols_difference) {
    while($cols_difference < $cols_display) {
        $html_output[] = "<td></td><td></td>";
        $cols_difference++;
    }
}

$html_output = array_chunk($html_output, $cols_display);

$flaws_table .= "<table width=\"70%\" class=\"table-condensed table-bordered\">";

foreach ($html_output as $current_row) {
    $flaws_table .= "<tr>".implode("", $current_row)."</tr>";
}

$flaws_table .= "</table>";
?>
<!-- Appearance -->
<h5 class="header-h5 header-bdr-bottom"><?php echo $label_appearance; ?><span class="pull-right"><span class="judge-score"><?php echo $row_eval['evalAppearanceScore']; ?></span>/<?php echo $appearance_possible; ?></span></h5>


<!-- Aroma -->
<h5 class="header-h5 header-bdr-bottom"><?php echo $label_aroma; ?><span class="pull-right"><span class="judge-score"><?php echo $row_eval['evalAromaScore']; ?></span>/<?php echo $aroma_possible; ?></span></h5>

<!-- Flavor -->
<h5 class="header-h5 header-bdr-bottom"><?php echo $label_flavor; ?><span class="pull-right"><span class="judge-score"><?php echo $row_eval['evalFlavorScore']; ?></span>/<?php echo $flavor_possible; ?></span></h5>

<!-- Mouthfeel (Beer only) -->
<h5 class="header-h5 header-bdr-bottom"><?php echo $label_mouthfeel ?><span class="pull-right"><span class="judge-score"><?php echo $row_eval['evalMouthfeelScore']; ?></span>/<?php echo $mouthfeel_possible; ?></span></h5>

<!-- Overall Impression -->
<h5 class="header-h5 header-bdr-bottom"><?php echo $label_overall_impression; ?><span class="pull-right"><span class="judge-score"><?php echo $overall_score_processed; ?></span>/<?php echo $overall_possible; ?></span></h5>

<!-- Style accuracy -->
<h5 class="header-h5 header-bdr-bottom"><?php echo $label_style_accuracy; ?><span class="pull-right"><span class="judge-score"><?php echo $row_eval['evalStyleAccuracy']; ?></span>/<?php echo $style_correctness_possible; ?></span></h5>

<h5><?php echo sprintf("%s: %s",$label_overall_impression,$label_comments); ?></h5>
<p><?php echo htmlentities($row_eval['evalOverallComments']); ?></p>

<!-- Total -->
<h5 class="header-h5 header-bdr-bottom"><?php echo $label_flaws; ?></h5>
<div style="margin-top: 10px;">
    <?php echo $flaws_table; ?>
</div>

<h5 class="header-h5 header-bdr-bottom"><?php echo $label_total; ?><span class="pull-right"><span class="judge-score"><?php echo $score; ?>&nbsp;*&nbsp;2&nbsp;=&nbsp;<?php echo $score2; ?></span>/100</span></h5>