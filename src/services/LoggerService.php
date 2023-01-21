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
use Monolog\Formatter\NormalizerFormatter;
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
        $handler = new SimpleHandler(Logger::ERROR, true);
        $handler->setFormatter(new NormalizerFormatter('Y-m-d H:i:s'));
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
        $recipients = explode(',', preg_replace('/\s+/', '', App::env('LOGGER_EMAIL')));

        if (isset($recipients[0]) && $recipients[0] == '') {
            $recipients = $this->getAdminEmails();
        }

        foreach ($recipients as $recipient) {
            try {
                Queue::push(new EmailJob([
                    'recipient' => $recipient
                ]));
            } catch (Exception $e) {
                Craft::error($e->getMessage(), __METHOD__);
                return; // << do not throw, prevent loop
            }
        }
    }

    // Private Methods
    // =========================================================================

    /**
     * @param $mail
     * @return void
     * @throws BaseException
     * @throws InvalidConfigException
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function sendMail($mail): void
    {
        $logfile = Craft::$app->path->getLogPath() . '/simplelogger.json';

        if (!@file_exists($logfile)) {
            Craft::error('Can\'t find ' . $logfile, __METHOD__);
            die();
        }

        // Set subject
        $subject = 'Simple Logger Report | ' . date("m-d");

        // Template for send Email Log
        $html = Craft::$app->getView()->renderTemplate('_simplelogger/email');

        $mailer = Craft::$app->getMailer()->compose()
            ->setTo($mail)
            ->setSubject($subject)
            ->setHtmlBody($html)
            ->attach($logfile);

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

        $emails = [];
        foreach ($admins as $admin) {
            $emails[] = $admin->email;
        }
        return $emails;
    }
}
