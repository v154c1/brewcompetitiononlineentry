<?php


$appearance_points = 3;
$aroma_points = 12;
$flavor_points = 20;
$mouthfeel_points = 5;
$overall_points = 10;
?>
<style>
    .score-button-group {
        display: flex;
        flex-direction: row;
        flex-wrap: wrap;
        width: 100%;
    }

    .score-button {
        flex: 4.8% 1 0;
        padding: 6px 6px;
    }


</style>

<script>
    function set_score(event) {
        console.log(event);
        const element = event.target;
        const inp = element.getAttribute('data-score-input');
        const val = element.getAttribute('data-score-value');
        if (!inp || !val) {
            return;
        }
        const q = `input[name="${inp}"]`;
        $(q).val(val);
        // // console.log(q, $(q).val());
        score_button_classes();
    }


    function score_button_classes() {
        const btns = $('button[data-score-input]');
        // console.log(btns);
        for (const btn of btns) {
            // console.log(btn);
            const inp = btn.getAttribute('data-score-input');
            const val = btn.getAttribute('data-score-value');
            if (!inp || !val) {
                continue;
            }
            const inp_value = $(`input[name="${inp}"]`).val();
            if (val == inp_value) {
                btn.classList.remove('btn-secondary');
                if (val == 0) {
                    btn.classList.add('btn-danger');
                } else {
                    btn.classList.add('btn-primary');
                }
            } else {
                btn.classList.add('btn-secondary');
                btn.classList.remove('btn-primary');
                btn.classList.remove('btn-danger');
            }
        }
        calculateSum(0);

    }

    function prepare_score_buttons() {
        const btns = $('button[data-score-input]');
        // console.log(btns);
        for (const btn of btns) {
            // console.log(btn);
            const inp = btn.getAttribute('data-score-input');
            const val = btn.getAttribute('data-score-value');
            if (!inp || !val) {
                continue;
            }
            btn.addEventListener('click', set_score);
        }
    }


    $(document).ready(() => {
        prepare_score_buttons();
        score_button_classes();
    })
</script>

<?php

function score_input($points, $input_name, $label, $initial_value, $notes, $hints)
{

    ?>
    <h3 class="section-heading"><?php echo $label; ?></h3>
    <h4><?php echo $notes; ?></h4>
    <h5><?php echo $hints; ?></h5>
    <div class="form-group">
        <div class="row">

            <div class="col-md-3 col-sm-12 col-xs-12">
                <label for="<?php echo $input_name; ?>">
                    <?php echo $label; ?>
                </label>
            </div>
            <div class="col-md-9 col-sm-12 col-xs-12 container">
                <input type="number" min="0" max="<?php echo $points ?>" name="<?php echo $input_name; ?>"
                       value="<?php echo $initial_value; ?>" class="form-control score-choose row" placeholder="" required
                       onblur="score_button_classes()">
                <div class="btn-group row score-button-group" role="group">
                    <?php for ($i = 0; $i <= $points; $i++) { ?>
                        <button type="button" class="btn score-button  "
                                data-score-value="<?php echo $i; ?>"
                                data-score-input="<?php echo $input_name; ?>"
                        ><?php echo $i; ?></button>

                    <?php } ?>
                </div>
                <div class="help-block small with-errors"></div>
            </div>
        </div>
    </div>

    <?php
}

?>


<?php

score_input($appearance_points, "evalAppearanceScore", "Vzhled", $row_eval['evalAppearanceScore'], "barva, pěna, čirost, trvanlivost, struktura, jiné", "0 = nepěkné, 3 = velice hezké");

score_input($aroma_points, "evalAromaScore", "Aroma / vůně", $row_eval['evalAromaScore'], "slad, chmel, kvašení, jiné", "0 = puch, 12 = čistá, příjemná, ve style");



score_input($flavor_points, "evalFlavorScore", "Chuť", $row_eval['evalFlavorScore'], "slad, chmel, hořkost, kvašení, vyvážnost, dochuť, jiné", "0 = odporné, 20 = vynikající");

score_input($mouthfeel_points, "evalMouthfeelScore", "Pocit po napití", $row_eval['evalMouthfeelScore'], "tělo, sycení, hřejivost, sametovost, svíravost, jiné", "0 = nepitelné, 5 = příjemné bez vad");

score_input($overall_points, "evalOverallScore", "Celkový charakter", $row_eval['evalOverallScore'], "daný styl, vady, požitek", "0 = nepitelné, 10 = báječné, ve stylu");


?>

<div class="form-group">
    <label for="evalOverallComments"><?php echo sprintf("%s: %s", $label_overall_impression, $label_comments); ?></label>
    <textarea class="form-control" id="evalOverallComments" name="evalOverallComments" rows="6" placeholder=""
              data-error="<?php echo $evaluation_info_061; ?>"
              required><?php if ($action == "edit") echo htmlentities($row_eval['evalOverallComments']); ?></textarea>
    <div class="help-block small with-errors"></div>
    <div class="help-block small" id="evalOverallComments-words"></div>
</div>
