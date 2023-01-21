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
LOGGER_EMAIL=john@email.com
```

`LOGGER_EMAIL` accept multiple emails `LOGGER_EMAIL=john@email.com,jane@@email.com`

The plugin will use the built-in craft event `EVENT_BEFORE_HANDLE_EXCEPTION` to handle
exceptions, saving into a custom log file `storage/logs/simplelogger.json`

```log
[
    {
        "time": "2023-01-21 17:37:21",
        "code": "0",
        "sourcetype": "Exception 400",
        "level_name": "ERROR",
        "message": "TwigErrorRuntimeError: Calling unknown method: craftwebtwigvariablesCraftVariable::deviceDetect1() in /var/www/html/templates/index.twig:10"
    }
]
```

Using the same event above, Simple Logger will once a day (after 8pm) collect the report and send an email to `LOGGER_EMAIL` defined recipient

![report](readme/report.png)

Simple Logger won't collect 404 errors

### Feeling creative?

PR into https://github.com/leowebguy/simple-logger
