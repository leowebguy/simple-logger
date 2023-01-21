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
    public string $recipient;

    /**
     * @return string
     */
    public function defaultDescription(): string
    {
        return 'Sending Simple Logger Reports';
    }

    /**
     * @param $queue
     * @return void
     */
    public function execute($queue): void
    {
        SimpleLogger::getInstance()->loggerService->sendMail($this->recipient);
    }
}
