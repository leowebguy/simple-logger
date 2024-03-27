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
use Monolog\Logger;

class SimpleHandler extends AbstractProcessingHandler
{
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
                //"level" => $record["level"],
                "sourcetype" => "Exception " . $record["level"],
                "level_name" => $record["level_name"],
                "message" => stripslashes(substr($record["message"], 0, strpos($record["message"], "\nStack")))
            ]
        );
    }
}
