<?php

/**
 * Collect brief exceptions and send daily reports
 *
 * @author     Leo Leoncio
 * @see        https://github.com/leowebguy
 * @copyright  Copyright (c) 2024, leowebguy
 */

namespace leowebguy\simplelogger;

use Craft;
use craft\base\Plugin;
use craft\events\ExceptionEvent;
use craft\events\RegisterTemplateRootsEvent;
use craft\helpers\App;
use craft\web\ErrorHandler;
use craft\web\View;
use leowebguy\simplelogger\services\LoggerService;
use yii\base\Event;

/**
 * @method static SimpleLogger|null getInstance()
 * @property LoggerService $loggerService
 */
class SimpleLogger extends Plugin
{
    public bool $hasCpSection = false;

    public bool $hasCpSettings = false;

    public function init(): void
    {
        parent::init();

        if (!$this->isInstalled) {
            return;
        }

        $this->setComponents([
            'loggerService' => LoggerService::class,
        ]);

        Event::on(
            ErrorHandler::class,
            ErrorHandler::EVENT_BEFORE_HANDLE_EXCEPTION,
            function (ExceptionEvent $event): void {

                // Only if active
                if (!App::env('LOGGER_ON')) {
                    return;
                }

                // Skip non-critical exceptions
                if (preg_match('/(NotFoundHttpException)/i', (string)$event->exception)) {
                    return;
                }

                // Write Log Exception
                $this->loggerService->handleException($event->exception);

                // Write text for email one time a day
                $logfile = Craft::$app->getPath()->getLogPath() . '/simplelogger';

                if (!@file_exists($logfile)) {
                    @file_put_contents($logfile, '.');
                }

                $now = time();
                $mtime = @filemtime($logfile);
                $dayLog = $mtime !== false ? date('d', $mtime) : '0';
                $currentDay = date('d', $now);
                $hourDay = (int)ltrim(date('h', $now), '0');

                // Check if there's new data, if next day and after 8am
                if (@file_exists($logfile) && ($currentDay > $dayLog && $hourDay > 8)) {
                    $this->loggerService->sendReport();
                    @file_put_contents($logfile, '.');
                }
            }
        );

        Event::on(
            View::class,
            View::EVENT_REGISTER_CP_TEMPLATE_ROOTS,
            static function (RegisterTemplateRootsEvent $event): void {
                $event->roots['_simplelogger'] = __DIR__ . '/templates';
            }
        );

        Craft::info(
            'Simple Logger plugin loaded',
            __METHOD__
        );
    }
}
