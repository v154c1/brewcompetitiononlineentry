<?php

define('CECH_CONFIG_PATH', __DIR__ . '/.cech_config.json');

function cech_config_load(): array {
    $data = [];
    if (file_exists(CECH_CONFIG_PATH)) {
        $data = json_decode(file_get_contents(CECH_CONFIG_PATH), true);
        if (!is_array($data)) $data = [];
    }

    if (!isset($data['score_thresholds'])) {
        $data['score_thresholds'] = [
            'gold' => 45,
            'silver' => 41,
            'bronze' => 36
        ];
    }

    return $data;
}

function cech_config_save(array $config): bool {
    return file_put_contents(
        CECH_CONFIG_PATH,
        json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
    ) !== false;
}

$cech_config = cech_config_load();
