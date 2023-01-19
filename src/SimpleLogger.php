<?php
/**
 * Collect brief logs from exception handlers and report
 *
 * @author     Leo Leoncio
 * @see        https://github.com/leowebguy
 * @copyright  Copyright (c) 2022, leowebguy
 * @license    MIT
 */

namespace leowebguy\simplelogger;

use Craft;
use craft\base\Plugin;
use craft\console\Application as ConsoleApplication;
use craft\events\RegisterTemplateRootsEvent;
use craft\helpers\App;
use craft\events\ExceptionEvent;
use craft\helpers\FileHelper;
use craft\web\ErrorHandler;
use yii\base\Event;
use craft\web\View;

class SimpleLogger extends Plugin
{
    // Static Properties
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

        /**
         * ConsoleApplication
         */
        if (Craft::$app instanceof ConsoleApplication) {
            $this->controllerNamespace = 'leowebguy\simplelogger\console\controllers';
        }else {
            $this->controllerNamespace = 'leowebguy\simplelogger\controllers';
        }

        /**
         * Exception handler
         */
        if (App::env('LOGGER_ON')) {
            Event::on(
                ErrorHandler::class,
                ErrorHandler::EVENT_BEFORE_HANDLE_EXCEPTION,
                function(ExceptionEvent $event) {
                    
                    $statusCode = $event->exception->statusCode;
                    $statusCode = 500;
                    //Check if status code !== (400, 404)
                    if(!preg_match("/(404|400)/i", $statusCode)){
                        //Write Log Exception
                        $this->loggerService->handleException($event->exception);

                        //Write text for email one time for day
                        $logfile = Craft::$app->path->getLogPath() . '/execute/executionlogger.log';
                        $date = date("Y-m-d H:i:s");
                        if (!file_exists($logfile)) {
                            FileHelper::writeToFile($logfile, $date);
                        }
                        
                        $dayLog = date('d', filemtime($logfile)); 
                        $currentDay = date("d",strtotime($date));
                        $hourDay = ltrim(date("h",strtotime($date)), '0');;
                        // check 8am - 9am
                        if (@file_exists($logfile) && ( $currentDay > $dayLog  &&  in_array($hourDay, array('8', '9') ))) {
                            
                            $this->loggerService->sendReport();
                            @file_put_contents($logfile, $date);
                        }
                    }
                }
            );
        }

        /**
         * Plugin templates
         */
        Event::on(
            View::class,
            View::EVENT_REGISTER_SITE_TEMPLATE_ROOTS,
            static function(RegisterTemplateRootsEvent $event) {
                $event->roots['_simple-logger'] = __DIR__ . '/templates';
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
