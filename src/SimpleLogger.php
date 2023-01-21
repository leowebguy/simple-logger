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

class SimpleLogger extends Plugin
{
    // Properties
    // =========================================================================

    public bool $hasCpSection = false;

    public bool $hasCpSettings = false;

    // Public Methods
    // =========================================================================

    public function init()
    {
        parent::init();

        if (!$this->isInstalled) {
            return;
        }

        $this->setComponents([
            'loggerService' => LoggerService::class
        ]);

        if (App::env('LOGGER_ON')) {
            Event::on(
                ErrorHandler::class,
                ErrorHandler::EVENT_BEFORE_HANDLE_EXCEPTION,
                function(ExceptionEvent $event) {

                    // Check if status code !== (400, 404)
                    if (preg_match("/(404)/i", $event->exception->getCode())) {
                        return;
                    }

                    // Write Log Exception
                    $this->loggerService->handleException($event->exception);

                    // Test only
                    //$this->loggerService->sendReport();

                    // Write text for email one time a day
                    $logfile = Craft::$app->path->getLogPath() . '/simplelogger';

                    if (!@file_exists($logfile)) {
                        @file_put_contents($logfile, '.');
                    }

                    $date = date("Y-m-d H:i:s");
                    $dayLog = date('d', filemtime($logfile));
                    $currentDay = date("d", strtotime($date));
                    $hourDay = ltrim(date("h", strtotime($date)), '0');;

                    // Check if there's new data, if next day and after 8am
                    if (@file_exists($logfile) && ($currentDay > $dayLog && $hourDay > 8)) {
                        $this->loggerService->sendReport();
                        @file_put_contents($logfile, '.');
                    }
                }
            );
        }

        /**
         * Plugin templates
         */
        Event::on(
            View::class,
            View::EVENT_REGISTER_CP_TEMPLATE_ROOTS,
            static function(RegisterTemplateRootsEvent $event) {
                $event->roots['_simplelogger'] = __DIR__ . '/templates';
            }
        );

        /**
         * Log info
         */
        Craft::info(
            'Simple Logger plugin loaded',
            __METHOD__
        );
    }
}
