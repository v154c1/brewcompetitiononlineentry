<?php

/**
 * Replaces placeholders in strings.
 */
function resolve_template($template, $vars)
{
    foreach ($vars as $key => $val) {
        $template = str_replace('${' . $key . '}', $val, $template);
    }
    return $template;
}

/**
 * Checks if a template matches an entry's results.
 */
function matches_template($t, $score_place, $score_entry)
{
    $place = $t['place'] ?? '';
    $color = $t['color'] ?? '';

    $place_ok = true;
    if ($place === '1')        $place_ok = (string)$score_place === '1';
    elseif ($place === '2')    $place_ok = (string)$score_place === '2';
    elseif ($place === '3')    $place_ok = (string)$score_place === '3';
    elseif ($place === 'any')  $place_ok = !empty($score_place);
    elseif ($place === 'none') $place_ok = empty($score_place);

    $s = (float)$score_entry;
    $color_ok = true;

    global $cech_config;
    $thresholds = $cech_config['score_thresholds'] ?? ['gold' => 45, 'silver' => 41, 'bronze' => 36];

    if ($color === 'gold')         $color_ok = $s >= $thresholds['gold'];
    elseif ($color === 'silver')   $color_ok = $s >= $thresholds['silver'] && $s < $thresholds['gold'];
    elseif ($color === 'bronze')   $color_ok = $s >= $thresholds['bronze'] && $s < $thresholds['silver'];

    return $place_ok && $color_ok;
}

/**
 * Finds the first matching template.
 */
function find_matching_template($templates, $score_place, $score_entry)
{
    foreach ($templates as $t) {
        if (matches_template($t, $score_place, $score_entry)) return $t;
    }
    return null;
}

/**
 * Renders HTML for a single diploma.
 * 
 * @param array  $entry          Entry data from database
 * @param array  $diploma_config Diploma configuration from cech_config
 * @param string $base_url       Base URL for background images
 * @return string                HTML content or empty string if no template matches
 */
function render_diploma($entry, $diploma_config, $base_url)
{
    $templates      = $diploma_config['templates']      ?? [];
    $category_names = $diploma_config['category_names'] ?? [];

    $tmpl = find_matching_template($templates, $entry['scorePlace'], $entry['scoreEntry']);
    if (!$tmpl) return '';

    $boxes      = $tmpl['boxes']      ?? [];
    $bg_file    = $tmpl['background'] ?? '';
    $bg_url     = $bg_file ? $base_url . 'user_images/' . rawurlencode($bg_file) : '';

    $brewer = html_entity_decode($entry['brewBrewerFirstName']) . ' ' . html_entity_decode($entry['brewBrewerLastName']);
    if (!empty($entry['brewCoBrewer'])) {
        $brewer .= ', ' . html_entity_decode($entry['brewCoBrewer']);
    }

    $vars = [
        'brewer'   => $brewer,
        'name'     => html_entity_decode($entry['brewName']),
        'place'    => $entry['scorePlace'],
        'score'    => $entry['scoreEntry'] !== null ? (int)$entry['scoreEntry'] * 2 : '',
        'category' => $category_names[$entry['brewStyle']] ?? html_entity_decode($entry['brewStyle']),
    ];

    return render_diploma_html($bg_url, $boxes, $vars);
}

/**
 * Renders the CSS for diplomas.
 */
function render_diploma_css()
{
    return '
        .diploma {
            position: relative;
            width: 210mm;
            height: 297mm;
            background-color: #fff !important;
            background-size: 100% 100% !important;
            background-position: center !important;
            background-repeat: no-repeat !important;
            overflow: hidden;
            font-family: Arial, sans-serif;
            color: #000;
            line-height: 1.2;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        .diploma-box {
            position: absolute;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            white-space: pre-wrap;
            overflow-wrap: break-word;
            line-height: 1.2;
        }
    ';
}

/**
 * Renders the HTML structure for a diploma using provided data.
 */
function render_diploma_html($bg_url, $boxes, $vars)
{
    $html = '<div class="diploma" style="' . ($bg_url ? 'background-image:url(' . htmlspecialchars($bg_url) . ') !important;' : '') . '">';
    foreach ($boxes as $box) {
        $text  = resolve_template($box['template'] ?? '', $vars);

        $valign = $box['valign'] ?? 'middle';
        $align  = $box['align']  ?? 'center';
        
        $justify = 'center';
        if ($valign === 'top')    $justify = 'flex-start';
        if ($valign === 'bottom') $justify = 'flex-end';
        
        $items = 'center';
        if ($align === 'left')    $items = 'flex-start';
        if ($align === 'right')   $items = 'flex-end';

        $style = sprintf(
            'left:%.4f%%;top:%.4f%%;width:%.4f%%;height:%.4f%%;font-size:%dpt;color:%s !important;text-align:%s;justify-content:%s;align-items:%s;',
            (float)($box['x']        ?? 0),
            (float)($box['y']        ?? 0),
            (float)($box['width']    ?? 60),
            (float)($box['height']   ?? 5),
            (int)  ($box['fontSize'] ?? 18),
            htmlspecialchars($box['color'] ?? '#000000'),
            htmlspecialchars($align),
            $justify,
            $items
        );
        $html .= '<div class="diploma-box" style="' . $style . '">';
        $html .= htmlspecialchars($text);
        $html .= '</div>';
    }
    $html .= '</div>';

    return $html;
}
