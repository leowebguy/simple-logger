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
use Throwable;
use yii\queue\RetryableJobInterface;

class EmailJob extends BaseJob implements RetryableJobInterface
{
    /**
     * @var array<string, string>
     */
    public array $to = [];

    public function defaultDescription(): string
    {
        return 'Sending Simple Logger Report';
    }

    public function getTtr(): int
    {
        return 15 * 60;
    }

    /**
     * @param int $attempt
     * @param \Exception|Throwable $error
     * @return bool
     */
    public function canRetry($attempt, $error): bool
    {
        return ($attempt < 2) && ($error instanceof Throwable);
    }

    /**
     * @param \yii\queue\Queue $queue
     * @return void
     */
    public function execute($queue): void
    {
        $plugin = SimpleLogger::getInstance();
        if ($plugin !== null) {
            $plugin->loggerService->sendMail($this->to);
        }
    }
}
