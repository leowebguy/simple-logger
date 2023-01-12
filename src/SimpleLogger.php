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
use craft\events\RegisterTemplateRootsEvent;
use craft\helpers\App;
use craft\events\ExceptionEvent;
use craft\web\ErrorHandler;
use DateTime;
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
         * Exception handler
         */
        if (App::env('LOGGER_ON')) {
            Event::on(
                ErrorHandler::class,
                ErrorHandler::EVENT_BEFORE_HANDLE_EXCEPTION,
                function(ExceptionEvent $event) {
                    $this->loggerService->handleException($event->exception);

                    // if between 6am and 7am
                    if (in_array((int)date('H'), [6, 7])) {
                        $this->loggerService->sendReport();
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
