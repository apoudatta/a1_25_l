<?php

if (!function_exists('write_log')) {
    /**
     * Write a custom log message to a file in writable/logs/
     *
     * @param string $message   The message content
     * @param string $filename  The log file name (default: 'custom.log')
     * @param bool   $includeRequestInfo Whether to include IP, URI, user-agent
     */
    function write_log(string $message, string $filename = 'custom.log', bool $includeRequestInfo = true)
    {
        $timestamp = date('[d-M-Y:H:i:s O]');
        $logLine = $timestamp;

        if ($includeRequestInfo) {
            $remoteAddr   = $_SERVER['REMOTE_ADDR'] ?? '-';
            $requestUri   = ($_SERVER['REQUEST_METHOD'] ?? '-') . ' ' . ($_SERVER['REQUEST_URI'] ?? '-') . ' ' . ($_SERVER['SERVER_PROTOCOL'] ?? '-');
            $userAgent    = $_SERVER['HTTP_USER_AGENT'] ?? '-';

            $logLine .= " - - - {$remoteAddr} \"{$requestUri}\" \"{$userAgent}\"";
        }

        $logLine .= " {$message}" . PHP_EOL;

        $filePath = WRITEPATH . 'logs/' . $filename;
        file_put_contents($filePath, $logLine, FILE_APPEND);
    }
}
