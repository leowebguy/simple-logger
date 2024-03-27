<?php
/**
 * Collect brief exceptions and send daily reports
 *
 * @author     Leo Leoncio
 * @see        https://github.com/leowebguy
 * @copyright  Copyright (c) 2024, leowebguy
 */

namespace leowebguy\simplelogger\services;

use Craft;
use craft\base\Component;
use craft\elements\User;
use craft\helpers\App;
use craft\helpers\Json;
use craft\helpers\Queue;
use Exception;
use leowebguy\simplelogger\handlers\SimpleHandler;
use leowebguy\simplelogger\jobs\EmailJob;
use Monolog\Formatter\LineFormatter;
use Monolog\Logger;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use yii\base\Exception as BaseException;
use yii\base\InvalidConfigException;

/**
 * @property-read array $adminEmails
 */
class LoggerService extends Component
{
    /**
     * @param $exception
     * @return void
     */
    public function handleException($exception): void
    {
        $handler = new SimpleHandler(Logger::ERROR, true);
        $handler->setFormatter(new LineFormatter('Y-m-d H:i:s'));
        $local = new Logger(Craft::$app->sites->currentSite->name);
        $local->pushHandler($handler);
        $local->error($exception);
    }

    /**
     * @param $data
     * @throws BaseException
     * @return void
     */
    public function writeException($data): void
    {
        $logfile = Craft::$app->path->getLogPath() . '/simplelogger.json';

        if (!@file_exists($logfile)) {
            @file_put_contents($logfile, '[]');
        }

        $json = @file_get_contents($logfile);

        $array = Json::decode($json);
        $array[] = $data;
        @file_put_contents($logfile, Json::encode($array));
    }

    /**
     * @throws BaseException
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

    /**
     * @param $to
     * @throws BaseException
     * @throws InvalidConfigException
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     * @return void
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
            //->attach($logfile)
            ->setHtmlBody($html);

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
