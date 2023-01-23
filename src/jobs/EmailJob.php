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

class EmailJob extends BaseJob
{
    public mixed $html;
    public mixed $subject;
    public mixed $mail;

    /**
     * @param $args
     */
    public function __construct($args)
    {

        $this->html = $args['html'];
        $this->subject = $args['subject'];
        $this->mail = $args['mail'];

    }


    /**
     * @return mixed
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * @return mixed
     */
    public function getHtml()
    {
        return $this->html;
    }

    /**
     * @return mixed
     */
    public function getMail()
    {
        return $this->mail;
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
        SimpleLogger::getInstance()->loggerService->sendMail($this->html, $this->subject, $this->mail);


    }
}
