<?php

define('CECH_CONFIG_PATH', __DIR__ . '/.cech_config.json');

function cech_config_load(): array {
    if (!file_exists(CECH_CONFIG_PATH)) return [];
    $data = json_decode(file_get_contents(CECH_CONFIG_PATH), true);
    return is_array($data) ? $data : [];
}

function cech_config_save(array $config): bool {
    return file_put_contents(
        CECH_CONFIG_PATH,
        json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
    ) !== false;
}

$cech_config = cech_config_load();
