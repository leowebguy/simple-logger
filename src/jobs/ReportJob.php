<?php
/**
 * Collect brief logs from exception handlers and report
 *
 * @author     Leo Leoncio
 * @see        https://github.com/leowebguy
 * @copyright  Copyright (c) 2022, leowebguy
 * @license    MIT
 */

namespace leowebguy\simplelogger\jobs;

use craft\queue\BaseJob;
use leowebguy\simplelogger\SimpleLogger;

class ReportJob extends BaseJob
{
    public mixed $data;

    /**
     * @param $args
     */
    public function __construct($args)
    {
        $this->data = $args['data'];
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @return string
     */
    public function defaultDescription(): string
    {
        return 'Sending Exception to HF Logs';
    }

    /**
     * @param $queue
     * @return void
     */
    public function execute($queue): void
    {
        SimpleLogger::getInstance()->loggerService->sendToSplunk($this->getData());
    }
}
