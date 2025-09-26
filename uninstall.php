<?php
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}
require_once __DIR__ . '/src/Core/Lifecycle.php';
OutdoorWww\Core\Lifecycle::uninstall();
