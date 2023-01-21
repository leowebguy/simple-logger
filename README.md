Simple Logger plugin for Craft CMS
===

Plugin for collecting exception handlers logs and reporting over email.
A simple solution for those who can't or won't pay for corporate solution loggers like: New Relic, Dynatrace, Datadog, Splunk and others.

### Install

```bash
composer require leowebguy/simple-logger && php craft plugin/install simple-logger
```

### Usage

Set these two `.env` parameters to make sure Simple Logger is active

```dotenv
# Simple Logger
LOGGER_ON=1
LOGGER_EMAIL=johndoe@myemail.com
```

The plugin will use the built-in craft event `EVENT_BEFORE_HANDLE_EXCEPTION` to handle
exceptions, saving into a custom log file `storage/logs/simplelogger.json`

```log
[
    {
        "time": "2023-01-21 11:57:23",
        "sourcetype": "Exception 400",
        "level_name": "ERROR",
        "message": "yiiwebNotFoundHttpException: Template not found: kkk in /var/www/html/vendor/craftcms/cms/src/controllers/TemplatesController.php:97"
    }
]
```

Using the same event above, Simple Logger will once a day (after 8pm) collect the report and send an email to `LOGGER_EMAIL` defined recipient

- Simple Logger won't collect logs from 400, 404 errors.

- If `LOGGER_EMAIL` is not set, plugin will send report to all admins

### Feeling creative?

PR into https://github.com/leowebguy/simple-logger
