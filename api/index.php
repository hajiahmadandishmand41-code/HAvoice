<?php
/**
 * HAvoice — Vercel PHP entrypoint.
 * Keeps the application root one level above this serverless function.
 */

define('HA_ROOT', dirname(__DIR__));

require HA_ROOT . '/includes/bootstrap.php';
