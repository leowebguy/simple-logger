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
use Monolog\Level;
use Monolog\Logger;
use Throwable;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use yii\base\Exception as BaseException;
use yii\base\InvalidConfigException;

/**
 * @property-read array<string, string> $adminEmails
 */
class LoggerService extends Component
{
    /**
     * @param mixed $exception
     * @return void
     */
    public function handleException(mixed $exception): void
    {
        $handler = new SimpleHandler(Level::Error, true);
        $handler->setFormatter(new LineFormatter(null, 'Y-m-d H:i:s'));
        $siteName = Craft::$app->getSites()->getCurrentSite()->name;
        $local = new Logger($siteName);
        $local->pushHandler($handler);

        if ($exception instanceof Throwable) {
            $local->error((string)$exception);
        } elseif (is_string($exception)) {
            $local->error($exception);
        } else {
            $local->error((string)json_encode($exception));
        }
    }

    /**
     * @param array<string, mixed> $data
     * @throws BaseException
     * @return void
     */
    public function writeException(array $data): void
    {
        $logfile = Craft::$app->getPath()->getLogPath() . '/simplelogger.json';

        if (!@file_exists($logfile)) {
            @file_put_contents($logfile, '[]');
        }

        $json = @file_get_contents($logfile);
        if ($json === false) {
            $json = '[]';
        }

        /** @var mixed $array */
        $array = Json::decode($json);
        if (!is_array($array)) {
            $array = [];
        }
        $array[] = $data;
        @file_put_contents($logfile, Json::encode($array));
    }

    /**
     * @throws BaseException
     * @return void
     */
    public function sendReport(): void
    {
        /** @var array<string, string> $to */
        $to = [];
        $envEmail = App::env('LOGGER_EMAIL');
        $cleanEmail = is_string($envEmail) ? preg_replace('/\s+/', '', $envEmail) : '';
        $rec = !empty($cleanEmail) ? explode(',', $cleanEmail) : [];

        // array(1) { ["aaa@gmail.com"]=> string(0) "" }
        if (count($rec) === 1 && !empty($rec[0])) {
            $to[$rec[0]] = '';
        }

        // array(1) { ["aaa@gmail.com"]=> string(0) "" }
        if (empty($rec)) {
            $to = $this->getAdminEmails();
        }

        // array(2) { ["aaa@gmail.com"]=> string(0) "" ["bbb@gmail.com"]=> string(0) "" }
        if (count($rec) > 1) {
            foreach ($rec as $r) {
                if (!empty($r)) {
                    $to[$r] = '';
                }
            }
        }

        try {
            Queue::push(new EmailJob([
                'to' => $to,
            ]));
        } catch (Exception $e) {
            Craft::error($e->getMessage(), __METHOD__);
            return; // << do not throw, prevent loop
        }
    }

    /**
     * @param array<string, string> $to
     * @throws BaseException
     * @throws InvalidConfigException
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     * @return void
     */
    public function sendMail(array $to): void
    {
        $logfile = Craft::$app->getPath()->getLogPath() . '/simplelogger.json';

        if (!@file_exists($logfile)) {
            Craft::error('Can\'t find ' . $logfile, __METHOD__);
            return;
        }

        $json = @file_get_contents($logfile);
        if ($json === false) {
            $json = '[]';
        }
        $logs = Json::decode($json);

        // Set subject
        $subject = 'Simple Logger Report | ' . date('m-d');

        // Template for send Email Log
        $html = Craft::$app->getView()->renderTemplate('_simplelogger/email', ['logs' => $logs]);

        $mailer = Craft::$app->getMailer()->compose()
            ->setTo($to)
            ->setSubject($subject)
            ->setHtmlBody($html);

        // Clear log
        if ($mailer->send()) {
            @file_put_contents($logfile, '[]');
        }
    }

    /**
     * @return array<string, string>
     */
    private function getAdminEmails(): array
    {
        /** @var User[] $admins */
        $admins = User::find()
            ->admin()
            ->all();

        $to = [];
        foreach ($admins as $admin) {
            if ($admin->email) {
                $to[$admin->email] = '';
            }
        }

        return $to;
    }
}
