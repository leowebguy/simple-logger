<?php
/**
 * Collect brief exceptions and send daily reports
 *
 * @author     Leo Leoncio
 * @see        https://github.com/leowebguy
 * @copyright  Copyright (c) 2024, leowebguy
 */

namespace leowebguy\simplelogger\jobs;

use craft\queue\BaseJob;
use leowebguy\simplelogger\SimpleLogger;

class EmailJob extends BaseJob
{
    public array $to;

    public function defaultDescription(): string
    {
        return 'Sending Simple Logger Report';
    }

    public function execute($queue): void
    {
        SimpleLogger::getInstance()->loggerService->sendMail($this->to);
    }
}
