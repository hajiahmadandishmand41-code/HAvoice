<?php
/**
 * HAvoice — Vercel PHP serverless entrypoint for the dynamic robots.txt.
 * Mirrors the root robots.php so that /robots.txt stays dynamic on Vercel too.
 */

define('HA_ROOT', dirname(__DIR__));

require HA_ROOT . '/robots.php';
