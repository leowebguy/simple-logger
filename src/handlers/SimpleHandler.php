<?php
/**
 * Collect brief logs from exception handlers and report
 *
 * @author     Leo Leoncio
 * @see        https://github.com/leowebguy
 * @copyright  Copyright (c) 2022, leowebguy
 * @license    MIT
 */

namespace leowebguy\simplelogger\handlers;

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;
use leowebguy\simplelogger\SimpleLogger;

class SimpleHandler extends AbstractProcessingHandler
{
    // Public Methods
    // =========================================================================

    /**
     * @param int $level
     * @param bool $bubble
     */
    public function __construct(int $level = Logger::ERROR, bool $bubble = true)
    {
        parent::__construct($level, $bubble);
    }

    /**
     * @param array $record
     * @return void
     */
    public function write(array $record): void
    {
        SimpleLogger::getInstance()->loggerService->writeException(
            [
                "time" => $record["datetime"]->format('Y-m-d H:i:s'),
                //"host" => preg_replace('/https?:\/\//', '', $record["channel"]),
                //"source" => "Exception",
                "sourcetype" => "Exception " . $record["level"],
                "level_name" => $record["level_name"],
                "message" => stripslashes(substr($record["message"], 0, strpos($record["message"], "\nStack")))
            ]
        );
    }
}
