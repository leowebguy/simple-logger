<?php
/**
 * Collect brief exception handlers and send daily reports
 *
 * @author     Leo Leoncio
 * @author     Ivan Pinheiro
 * @see        https://github.com/leowebguy
 * @copyright  Copyright (c) 2023, leowebguy
 * @license    MIT
 */

namespace leowebguy\simplelogger\jobs;

use craft\queue\BaseJob;
use leowebguy\simplelogger\SimpleLogger;

class EmailJob extends BaseJob
{
    public array $to;

    /**
     * @return string
     */
    public function defaultDescription(): string
    {
        return 'Sending Simple Logger Report';
    }

    /**
     * @param $queue
     * @return void
     */
    public function execute($queue): void
    {
        SimpleLogger::getInstance()->loggerService->sendMail($this->to);
    }
}
