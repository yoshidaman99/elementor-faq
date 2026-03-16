<?php

namespace Elementor_FAQ\Core;

if (!defined('ABSPATH')) {
    exit;
}

class Debug_Logger
{
    private bool $enabled;

    public function __construct()
    {
        $this->enabled = defined('WP_DEBUG') && WP_DEBUG;
    }

    public function log(string $message, array $context = [], string $level = 'info'): void
    {
        if (!$this->enabled) {
            return;
        }

        $formatted_message = sprintf(
            '[Elementor FAQ %s] %s',
            strtoupper($level),
            $message
        );

        if (!empty($context)) {
            $formatted_message .= ' | Context: ' . wp_json_encode($context, JSON_PRETTY_PRINT);
        }

        error_log($formatted_message);
    }

    public function error(string $message, array $context = []): void
    {
        $this->log($message, $context, 'error');
    }

    public function warning(string $message, array $context = []): void
    {
        $this->log($message, $context, 'warning');
    }

    public function info(string $message, array $context = []): void
    {
        $this->log($message, $context, 'info');
    }

    public function debug(string $message, array $context = []): void
    {
        if ($this->enabled && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
            $this->log($message, $context, 'debug');
        }
    }
}
