<?php
/**
 * Collect brief logs from exception handlers and report
 *
 * @author     Leo Leoncio
 * @see        https://github.com/leowebguy
 * @copyright  Copyright (c) 2022, leowebguy
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
use craft\helpers\FileHelper;
use craft\helpers\Json;
use leowebguy\simplelogger\handlers\SimpleHandler;
use yii\base\ErrorException;
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
     * @throws ErrorException
     */
    public function writeException($data): void
    {
        $path = Craft::$app->path->getLogPath();

        if (!FileHelper::isWritable($path)) {
            Craft::error('Can\'t write into getLogPath()' . $path, __METHOD__);
            die();
        }

        $logfile = 'simplelogger.log';

        if (!file_exists($path . $logfile)) {
            FileHelper::writeToFile($path . $logfile, '');
        }

        $json = file_get_contents($path . $logfile);
        $array = Json::decode($json);
        $array[] = $data;

        FileHelper::writeToFile($path . $logfile, Json::encode($array), ['append']);
    }

    /**
     * @return void
     * @throws BaseException
     */
    public function sendReport(): void
    {
        $logfile = Craft::$app->path->getLogPath() . '/simplelogger.log';

        if (!file_exists($logfile)) {
            Craft::error('Can\'t find ' . $logfile, __METHOD__);
            die();
        }

        $json = file_get_contents($logfile);
        $data = Json::decode($json);

        try {
            $this->createEmail($data);
        } catch (Exception $e) {
            Craft::error($e->getMessage(), __METHOD__);
            return; // << do not throw, prevent loop
        }
    }

    // Private Methods
    // =========================================================================

    /**
     * @param $data
     * @return void
     * @throws BaseException
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    private function createEmail($data): void
    {
        $recipients = explode(',', preg_replace('/\s+/', '', App::env('LOGGER_EMAIL'))) ?? $this->getAdminEmails();
        $subject = 'Simple Logger Report';

        // get the html email template
        $html = Craft::$app->getView()->renderTemplate('_healthfirstrefer/email', ['data' => $data]); //, 'pdf' => $pdf

        foreach ($recipients as $recipient) {
            try {
                $this->sendMail($html, $subject, $recipient);
            } catch (Exception $e) {
                Craft::error($e->getMessage(), __METHOD__);
                return; // << do not throw, prevent loop
            }
        }
    }

    /**
     * @param string $html
     * @param string $subject
     * @param $mail
     * @return void
     * @throws InvalidConfigException
     */
    private function sendMail(string $html, string $subject, $mail): void
    {
        $mailer = Craft::$app->getMailer()->compose()
            ->setTo($mail)
            ->setSubject($subject)
            ->setHtmlBody($html);

        $mailer->send();
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
