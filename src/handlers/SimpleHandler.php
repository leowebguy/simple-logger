<?php

/**
 * Collect brief exceptions and send daily reports
 *
 * @author     Leo Leoncio
 * @see        https://github.com/leowebguy
 * @copyright  Copyright (c) 2024, leowebguy
 */

namespace leowebguy\simplelogger\handlers;

use leowebguy\simplelogger\SimpleLogger;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Level;
use Monolog\Logger;
use Monolog\LogRecord;

class SimpleHandler extends AbstractProcessingHandler
{
    /**
     * @param Level|int|string $level
     * @param bool $bubble
     * @phpstan-param Level|value-of<Level::VALUES>|'ALERT'|'alert'|'CRITICAL'|'critical'|'DEBUG'|'debug'|'EMERGENCY'|'emergency'|'ERROR'|'error'|'INFO'|'info'|'NOTICE'|'notice'|'WARNING'|'warning' $level
     */
    public function __construct(Level|int|string $level = Level::Error, bool $bubble = true)
    {
        parent::__construct(Logger::toMonologLevel($level), $bubble);
    }

    /**
     * @param LogRecord $record
     * @return void
     */
    protected function write(LogRecord $record): void
    {
        $plugin = SimpleLogger::getInstance();
        if ($plugin === null) {
            return;
        }

        $message = $record->message;
        $stackPos = strpos($message, "\nStack");
        if ($stackPos !== false) {
            $message = substr($message, 0, $stackPos);
        }

        $plugin->loggerService->writeException([
            'time' => $record->datetime->format('Y-m-d H:i:s'),
            'sourcetype' => 'Exception ' . $record->level->value,
            'level_name' => $record->level->getName(),
            'message' => stripslashes($message),
        ]);
    }
}
