<!--<h1>HOLOUBEK</h1>-->
<?php

$aroma_points = 8;
$appearance_points = 2;
$flavor_points = 10;
$mouthfeel_points = 10;
$overall_points = 10;

$style_correctness_points = 10;


$overall_score_processed = 0;
if ($action == "edit") {
    $overall_score_processed = $row_eval['evalOverallScore'] - $row_eval['evalStyleAccuracy'];
    if (!is_numeric($overall_score_processed) || $overall_score_processed < 0) {
        $overall_score_processed = 0;
    }
}

asort($flaws);
?>

<input type="hidden" name="evalFormType" value="3">

<!-- Appearance -->
<h3 class="section-heading"><?php echo $label_appearance; ?></h3>
<h4>barva, pěna, čirost, držení, struktura, jiné</h4>
<!-- Appearance Score -->
<div class="form-group">
    <div class="row">
        <div class="col-md-3 col-sm-12 col-xs-12">
            <label for="evalAppearanceScore"><?php echo $label_score; ?>
                (<?php echo $appearance_points; ?> <?php echo strtolower($label_possible_points); ?>)</label>
        </div>
        <div class="col-md-9 col-sm-12 col-xs-12">
            <select class="form-control selectpicker score-choose" name="evalAppearanceScore" id="type" data-size="10"
                    required>
                <option value=""></option>
                <?php for ($i = $appearance_points; $i >= 0; $i--) {
                    if (($action == "edit") && ($i == $row_eval['evalAppearanceScore'])) $selected = "selected";
                    else $selected = "";
                    ?>
                    <option value="<?php echo $i; ?>" <?php echo $selected; ?>><?php echo $i; ?></option>
                <?php } ?>
            </select>
            <div class="help-block small with-errors"></div>
        </div>
    </div>
</div>

<!-- Aroma -->
<h3 class="section-heading"><?php echo $label_aroma; ?></h3>
<h4>slad, chmel, kvašení, jiné</h4>

<div class="form-group">
    <div class="row">
        <div class="col-md-3 col-sm-12 col-xs-12">
            <label for="evalAromaScore"><?php echo $label_score; ?>
                (<?php echo $aroma_points; ?> <?php echo strtolower($label_possible_points); ?>)</label>
        </div>
        <div class="col-md-9 col-sm-12 col-xs-12">
            <select class="form-control selectpicker score-choose" name="evalAromaScore" id="type" data-size="10"
                    required>
                <option value=""></option>
                <?php
                for ($i = $aroma_points; $i >= 0; $i--) {
                    if (($action == "edit") && ($i == $row_eval['evalAromaScore'])) $selected = "selected";
                    else $selected = "";
                    ?>
                    <option value="<?php echo $i; ?>" <?php echo $selected; ?>><?php echo $i; ?></option>
                <?php } ?>
            </select>
            <div class="help-block small with-errors"></div>
        </div>
    </div>
</div>


<!-- Flavor -->
<h3 class="section-heading"><?php echo $label_flavor; ?></h3>
<h4>slad, chmel, hořkost, kvašení, vyvážnost, dochuť, jiné</h4>
<!-- Flavor Score -->
<div class="form-group">
    <div class="row">
        <div class="col-md-3 col-sm-12 col-xs-12">
            <label for="evalFlavorScore"><?php echo $label_score; ?>
                (<?php echo $flavor_points; ?> <?php echo strtolower($label_possible_points); ?>)</label>
        </div>
        <div class="col-md-9 col-sm-12 col-xs-12">
            <select class="form-control selectpicker score-choose" name="evalFlavorScore" id="type" data-size="10"
                    required>
                <option value=""></option>
                <?php for ($i = $flavor_points; $i >= 0; $i--) {
                    if (($action == "edit") && ($i == $row_eval['evalFlavorScore'])) $selected = "selected";
                    else $selected = "";
                    ?>
                    <option value="<?php echo $i; ?>" <?php echo $selected; ?>><?php echo $i; ?></option>
                <?php } ?>
            </select>
            <div class="help-block small with-errors"></div>
        </div>
    </div>
</div>

<!-- Mouthfeel -->
<h3 class="section-heading"><?php echo $label_mouthfeel; ?></h3>
<h4>tělo, sycení, hřejivost, sametovost, svíravost, jiné</h4>
<!-- Mouthfeel Score -->
<div class="form-group">
    <div class="row">
        <div class="col-md-3 col-sm-12 col-xs-12">
            <label for="evalMouthfeelScore"><?php echo $label_score; ?>
                (<?php echo $mouthfeel_points; ?> <?php echo strtolower($label_possible_points); ?>)</label>
        </div>
        <div class="col-md-9 col-sm-12 col-xs-12">
            <select class="form-control selectpicker score-choose" name="evalMouthfeelScore" id="type" data-size="10"
                    required>
                <option value=""></option>
                <?php for ($i = $mouthfeel_points; $i >= 0; $i--) {
                    if (($action == "edit") && ($i == $row_eval['evalMouthfeelScore'])) $selected = "selected";
                    else $selected = "";
                    ?>
                    <option value="<?php echo $i; ?>" <?php echo $selected; ?>><?php echo $i; ?></option>
                <?php } ?>
            </select>
            <div class="help-block small with-errors"></div>
        </div>
    </div>
</div>

<!-- Overall Impression -->
<h3 class="section-heading"><?php echo $label_overall_impression; ?></h3>
<h4>daný styl, vady, požitek</h4>
<div class="form-group">
    <div class="row">
        <div class="col-md-3 col-sm-12 col-xs-12">
            <label for="evalOverallScore"><?php echo $label_score; ?>
                (<?php echo $overall_points; ?> <?php echo strtolower($label_possible_points); ?>)</label>
        </div>
        <div class="col-md-9 col-sm-12 col-xs-12">
            <select class="form-control selectpicker score-choose" name="evalOverallScore" id="type" data-size="10"
                    required>
                <option value=""></option>
                <?php for ($i = $overall_points; $i >= 0; $i--) {
                    if (($action == "edit") && ($i == $overall_score_processed)) $selected = "selected";
                    else $selected = "";
                    ?>
                    <option value="<?php echo $i; ?>" <?php echo $selected; ?>><?php echo $i; ?></option>
                <?php } ?>
            </select>
            <div class="help-block small with-errors"></div>
        </div>
    </div>
</div>

<h3 class="section-heading"><?php echo $label_style_accuracy; ?></h3>
<h6>musí korespondovat se zadáním stylu</h6>

<div class="form-group">
    <div class="row">
        <div class="col-md-3 col-sm-12 col-xs-12">
            <label for="evalStyleAccuracy"><?php echo $label_score; ?>
                (<?php echo $style_correctness_points; ?> <?php echo strtolower($label_possible_points); ?>)</label>
        </div>
        <div class="col-md-9 col-sm-12 col-xs-12">
            <select class="form-control selectpicker score-choose" name="evalStyleAccuracy" id="type" data-size="10"
                    required>
                <option value=""></option>
                <?php for ($i = $style_correctness_points; $i >= 0; $i-=10) {
                    if (($action == "edit") && ($i == $row_eval['evalStyleAccuracy'])) $selected = "selected";
                    else $selected = "";
                    ?>
                    <option value="<?php echo $i; ?>" <?php echo $selected; ?>><?php echo $i; ?></option>
                <?php } ?>
            </select>
            <div class="help-block small with-errors"></div>
        </div>
    </div>
</div>

<!-- Style Accuracy -->
<!--<div class="form-group">-->
<!--    <div class="row">-->
<!--        <div class="col-md-3 col-sm-12 col-xs-12">-->
<!--            <label for="evalStyleAccuracy">--><?php //echo $label_style_accuracy; ?><!--</label>-->
<!--        </div>-->
<!--        <div class="col-md-9 col-sm-12 col-xs-12 small">-->
<!--            <div style="margin-left: 10px">-->
<!--                <input class="form-control score-choose" type="text" name="evalStyleAccuracy" data-provide="slider"-->
<!--                       data-slider-ticks="[0,10]"-->
<!--                       data-slider-ticks-labels='["--><?php //echo $label_not_style; ?><!--", "--><?php //echo $label_classic_example; ?><!--"]'-->
<!--                       data-slider-min="0" data-slider-max="10" data-slider-step="10"-->
<!--                       data-slider-value="--><?php //if ($action == "edit") echo $row_eval['evalStyleAccuracy']; else echo "0"; ?><!--"-->
<!--                       data-slider-tooltip="hide">-->
<!--            </div>-->
<!--        </div>-->
<!--    </div>-->
<!--    <div class="help-block small with-errors"></div>-->
<!--</div>-->

<div class="form-group">
    <label for="evalOverallComments"><?php echo sprintf("%s: %s", $label_overall_impression, $label_comments); ?></label>
    <textarea class="form-control" id="evalOverallComments" name="evalOverallComments" rows="6" placeholder=""
              data-error="<?php echo $evaluation_info_061; ?>"
              required><?php if ($action == "edit") echo htmlentities($row_eval['evalOverallComments']); ?></textarea>
    <div class="help-block small with-errors"></div>
    <div class="help-block small" id="evalOverallComments-words"></div>
</div>


<!-- Flaws -->
<!--
<h3 class="section-heading"><?php echo $label_flaws; ?></h3>
<?php foreach ($flaws as $flaw) {
    $flaw_none = FALSE;
    $flaw_low = FALSE;
    $flaw_med = FALSE;
    $flaw_high = FALSE;
    if ($action == "edit") {
        if (strpos($row_eval['evalFlaws'], $flaw . " $label_low") !== false) $flaw_low = TRUE;
        elseif (strpos($row_eval['evalFlaws'], $flaw . " $label_medium") !== false) $flaw_med = TRUE;
        elseif (strpos($row_eval['evalFlaws'], $flaw . " $label_high") !== false) $flaw_high = TRUE;
        else $flaw_none = TRUE;
    }
    ?>
    <div class="form-group">
        <div class="row">
            <div class="col-md-3 col-sm-12 col-xs-12">
                <label for="evalFlaws"><?php echo $flaw; ?></label>
            </div>
            <div class="col-md-9 col-sm-12 col-xs-12 small">
                <label class="radio-inline">
                    <input type="radio" name="evalFlaws<?php echo $flaw; ?>"
                           value="" <?php if (($action == "add") || ($flaw_none)) echo "checked"; ?>><?php echo $label_na; ?>
                </label>
                <label class="radio-inline">
                    <input type="radio" name="evalFlaws<?php echo $flaw; ?>"
                           value="<?php echo $flaw . " " . $label_low; ?>" <?php if ($flaw_low) echo "checked"; ?>><?php echo $label_low; ?>
                </label>
                <label class="checkbox-inline">
                    <input type="radio" name="evalFlaws<?php echo $flaw; ?>"
                           value="<?php echo $flaw . " " . $label_medium; ?>" <?php if ($flaw_med) echo "checked"; ?>> <?php echo $label_med; ?>
                </label>
                <label class="radio-inline">
                    <input type="radio" name="evalFlaws<?php echo $flaw; ?>"
                           value="<?php echo $flaw . " " . $label_high; ?>" <?php if ($flaw_high) echo "checked"; ?>> <?php echo $label_high; ?>
                </label>
            </div>
        </div>

    </div>
    <?php } ?>
-->

<?php
?>

<!--<h1>Holoubek end</h1>-->