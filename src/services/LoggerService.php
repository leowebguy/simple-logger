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

namespace leowebguy\simplelogger\services;

use Craft;
use Exception;
use Monolog\Formatter\LineFormatter;
use Monolog\Logger;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use craft\base\Component;
use craft\elements\User;
use craft\helpers\App;
use craft\helpers\Json;
use craft\helpers\Queue;
use leowebguy\simplelogger\handlers\SimpleHandler;
use leowebguy\simplelogger\jobs\EmailJob;
use yii\base\Exception as BaseException;
use yii\base\InvalidConfigException;

/**
 * @property-read array $adminEmails
 */
class LoggerService extends Component
{
    // Public Methods
    // =========================================================================

    /**
     * @param $exception
     * @return void
     */
    public function handleException($exception): void
    {
        $handler = new SimpleHandler(Logger::ERROR, true, $exception->getCode());
        $handler->setFormatter(new LineFormatter('Y-m-d H:i:s'));
        $local = new Logger(Craft::$app->sites->currentSite->name);
        $local->pushHandler($handler);
        $local->error($exception);
    }

    /**
     * @param $data
     * @return void
     * @throws BaseException
     */
    public function writeException($data): void
    {
        $path = Craft::$app->path->getLogPath();

        $logfile = '/simplelogger.json';

        if (!@file_exists($path . $logfile)) {
            @file_put_contents($path . $logfile, '[]');
        }

        $json = @file_get_contents($path . $logfile);

        $array = Json::decode($json);
        $array[] = $data;
        @file_put_contents($path . $logfile, Json::encode($array));
    }

    /**
     * @return void
     */
    public function sendReport(): void
    {
        $to = [];
        $rec = explode(',', preg_replace('/\s+/', '', App::env('LOGGER_EMAIL') ?? ''));

        // array(1) { ["aaa@gmail.com"]=> string(0) "" }
        if (count($rec) == 1 && !empty($rec[0])) {
            $to[$rec[0]] = '';
        }

        // array(1) { ["aaa@gmail.com"]=> string(0) "" }
        if (empty($rec[0])) {
            $to = $this->getAdminEmails();
        }

        // array(2) { ["aaa@gmail.com"]=> string(0) "" ["bbb@gmail.com"]=> string(0) "" }
        if (count($rec) > 1) {
            foreach ($rec as $r) {
                $to[$r] = '';
            }
        }

        try {
            Queue::push(new EmailJob([
                'to' => $to
            ]));
        } catch (Exception $e) {
            Craft::error($e->getMessage(), __METHOD__);
            return; // << do not throw, prevent loop
        }
    }

    // Private Methods
    // =========================================================================

    /**
     * @param $to
     * @return void
     * @throws BaseException
     * @throws InvalidConfigException
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function sendMail($to): void
    {
        $logfile = Craft::$app->path->getLogPath() . '/simplelogger.json';

        if (!@file_exists($logfile)) {
            Craft::error('Can\'t find ' . $logfile, __METHOD__);
            die();
        }

        $json = @file_get_contents($logfile);
        $logs = Json::decode($json);

        // Set subject
        $subject = 'Simple Logger Report | ' . date("m-d");

        // Template for send Email Log
        $html = Craft::$app->getView()->renderTemplate('_simplelogger/email', ['logs' => $logs]);

        $mailer = Craft::$app->getMailer()->compose()
            ->setTo($to)
            ->setSubject($subject)
            ->setHtmlBody($html);
            //->attach($logfile);

        // Clear log
        if ($mailer->send()) {
            @file_put_contents($logfile, '[]');
        }
    }

    /**
     * @return array
     */
    private function getAdminEmails(): array
    {
        $admins = User::find()
            ->admin()
            ->all();

        $to = [];
        foreach ($admins as $admin) {
            $to[$admin->email] = '';
        }
        return $to;
    }
}
