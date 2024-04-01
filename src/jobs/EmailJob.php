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
use yii\queue\RetryableJobInterface;

class EmailJob extends BaseJob implements RetryableJobInterface
{
    public array $to;

    public function defaultDescription(): string
    {
        return 'Sending Simple Logger Report';
    }

    public function getTtr(): float|int
    {
        return 15 * 60;
    }

    public function canRetry($attempt, $error): bool
    {
        return ($attempt < 2) && ($error instanceof TemporaryException);
    }

    public function execute($queue): void
    {
        SimpleLogger::getInstance()->loggerService->sendMail($this->to);
    }
}
