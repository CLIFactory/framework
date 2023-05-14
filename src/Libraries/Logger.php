<?php

namespace CLIFactory\Libraries;

// Import LogLevel Class
use Throwable;
use DomainException;
use Psr\Log\LogLevel;
use RuntimeException;
use DateTimeImmutable;
use Psr\Log\LoggerInterface;

/**
 * ----------------------------------------------------------------------------
 * The Logger Library Class is a utility class that assists in the
 * logging application of activity.
 *
 * This package follows PSR-3 and requires PHP >=7.4
 *
 * @link      https://github.com/markrogoyski/simplelog-php/blob/master/src/Logger.php
 * @link      https://www.php-fig.org/psr/psr-3/
 * ----------------------------------------------------------------------------
 * @copyright MIT License
 * @version   1.0.1
 * @since     1.0.0
 * @author    Jason Napolitano <jnapolitanoit@gmail.com>
 * @author    markrogoyski
 */
class Logger implements LoggerInterface
{
    /**
     * Log fields separated by tabs to form a TSV (CSV with tabs).
     */
    const TAB = "\t";
    /**
     * Special minimum log level which will not log any log levels.
     */
    const LOG_LEVEL_NONE = 'none';
    /**
     * Log level hierarchy
     */
    const LEVELS = [
        self::LOG_LEVEL_NONE => -1,
        LogLevel::DEBUG      => 0,
        LogLevel::INFO       => 1,
        LogLevel::NOTICE     => 2,
        LogLevel::WARNING    => 3,
        LogLevel::ERROR      => 4,
        LogLevel::CRITICAL   => 5,
        LogLevel::ALERT      => 6,
        LogLevel::EMERGENCY  => 7,
    ];
    /**
     * File name and path of log file.
     *
     * @var string
     */
    private string $file;
    /**
     * Log channel--namespace for log lines.
     * Used to identify and correlate groups of similar log lines.
     *
     * @var string
     */
    private string $channel;
    /**
     * Lowest log level to log.
     *
     * @var int
     */
    private int $level;
    /**
     * Whether to log to standard out.
     *
     * @var bool
     */
    private bool $stdout;

    /**
     * Logger constructor
     *
     * @param string $file    File name and path of log file.
     * @param string $channel Logger channel associated with this logger.
     * @param string $level   (optional) Lowest log level to log.
     */
    public function __construct(string $file, string $channel = 'event', string $level = LogLevel::DEBUG)
    {
        $this->file = $file;
        $this->channel ??= $channel;
        $this->stdout = false;
        $this->setLevel($level);
    }

    /**
     * Set the lowest log level to log.
     *
     * @param string $level
     */
    public function setLevel(string $level)
    {
        if (!array_key_exists($level, self::LEVELS)) {
            throw new DomainException(
                "Log level $level is not a valid log level. Must be one of (" . implode(
                    ', ',
                    array_keys(self::LEVELS)
                ) . ')'
            );
        }

        $this->level = self::LEVELS[$level];
    }

    /**
     * Set the log channel which identifies the log line.
     *
     * @param string $channel
     */
    public function setChannel(string $channel)
    {
        $this->channel = $channel;
    }

    /**
     * Set the standard out option on or off.
     * If set to true, log lines will also be printed to standard out.
     *
     * @param bool $stdout
     */
    public function setOutput(bool $stdout)
    {
        $this->stdout = $stdout;
    }

    /**
     * Log a debug message.
     * Fine-grained informational events that are most useful to debug an application.
     *
     * @param string $message Content of log event.
     * @param array  $data    Associative array of contextual support data that goes with the log event.
     *
     * @throws RuntimeException
     */
    public function debug($message = '', array $data = null): void
    {
        if ($this->logAtThisLevel(LogLevel::DEBUG)) {
            $this->log(LogLevel::DEBUG, $message, $data);
        }
    }

    /**
     * Determine if the logger should log at a certain log level.
     *
     * @param string $level
     *
     * @return bool True if we log at this level; false otherwise.
     */
    private function logAtThisLevel($level): bool
    {
        return self::LEVELS[$level] >= $this->level;
    }

    /**
     * Generic log routine that all severity levels use to log an event.
     *
     * @param string $level   Log level
     * @param string $message Content of log event.
     * @param array  $data    Potentially multidimensional associative array of support data that goes with the log
     *                        event.
     *
     * @throws RuntimeException when log file cannot be opened for writing.
     */
    public function log($level, $message = '', array $data = null): void
    {
        // Build log line
        $pid = getmypid();
        [$exception, $data] = $this->handleException($data);
        $data = $data ? json_encode($data, JSON_UNESCAPED_SLASHES) : '{}';
        $data = $data ? : '{}'; // Fail-safe in case json_encode fails.
        $log_line = $this->formatLogLine($level, $pid, $message, $data, $exception);

        // Log to file
        try {
            $fh = fopen($this->file, 'a');
            fwrite($fh, $log_line);
            fclose($fh);
        } catch (Throwable $e) {
            throw new RuntimeException(
                "Could not open log file {$this->file} for writing to \\Logger channel {$this->channel}!",
                0,
                $e
            );
        }

        // Log to stdout if option set to do so.
        if ($this->stdout) {
            print($log_line);
        }
    }

    /**
     * Handle an exception in the data context array.
     * If an exception is included in the data context array, extract it.
     *
     * @param array $data
     *
     * @return array  [exception, data (without exception)]
     */
    private function handleException(array $data = null): array
    {
        if (isset($data['exception']) && $data['exception'] instanceof Throwable) {
            $exception = $data['exception'];
            $exception_data = $this->buildExceptionData($exception);
            unset($data['exception']);
        } else {
            $exception_data = '{}';
        }

        return [$exception_data, $data];
    }

    /**
     * Build the exception log data.
     *
     * @param Throwable $e
     *
     * @return string JSON {message, code, file, line, trace}
     */
    private function buildExceptionData(Throwable $e): string
    {
        $exceptionData = json_encode(
            [
                'message' => $e->getMessage(),
                'code'    => $e->getCode(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'trace'   => $e->getTrace(),
            ],
            JSON_UNESCAPED_SLASHES
        );

        // Fail-safe in case json_encode failed
        return $exceptionData ? : '{"message":"' . $e->getMessage() . '"}';
    }

    /**
     * Format the log line.
     * YYYY-mm-dd HH:ii:ss.uuuuuu  [loglevel]  [channel]  [pid:##]  Log message content  {"Optional":"JSON
     * Contextual Support Data"}  {"Optional":"Exception Data"}
     *
     *
     * @param string $level
     * @param int    $pid
     * @param string $message
     * @param string $data
     * @param string $exception_data
     *
     * @return string
     */
    private function formatLogLine(
        string $level,
        int $pid,
        string $message,
        string $data,
        string $exception_data
    ): string {
        return
            $this->getTime() . self::TAB .
            '[' . strtoupper($level) . ']' . self::TAB .
            "[{$this->channel}]" . self::TAB .
            "[pid:$pid]" . self::TAB .
            str_replace(PHP_EOL, '   ', trim($message)) . self::TAB .
            str_replace(PHP_EOL, '   ', $data) . self::TAB .
            str_replace(PHP_EOL, '   ', $exception_data) . PHP_EOL;
    }

    /**
     * Get current date time.
     * Format: YYYY-mm-dd HH:ii:ss.uuuuuu
     * Microsecond precision for PHP 7.1 and greater
     *
     * @return string Date time
     */
    private function getTime(): string
    {
        return (new DateTimeImmutable('now'))->format('Y-m-d H:i:s.u');
    }

    /**
     * Log an info message.
     * Interesting events and informational messages that highlight the progress of the application at
     * coarse-grained level.
     *
     * @param string $message Content of log event.
     * @param array  $data    Associative array of contextual support data that goes with the log event.
     *
     * @throws RuntimeException
     */
    public function info($message = '', array $data = null): void
    {
        if ($this->logAtThisLevel(LogLevel::INFO)) {
            $this->log(LogLevel::INFO, $message, $data);
        }
    }

    /**
     * Log an notice message.
     * Normal but significant events.
     *
     * @param string $message Content of log event.
     * @param array  $data    Associative array of contextual support data that goes with the log event.
     *
     * @throws RuntimeException
     */
    public function notice($message = '', array $data = null): void
    {
        if ($this->logAtThisLevel(LogLevel::NOTICE)) {
            $this->log(LogLevel::NOTICE, $message, $data);
        }
    }

    /**
     * Log a warning message.
     * Exceptional occurrences that are not errors--undesirable things that are not necessarily wrong.
     * Potentially harmful situations which still allow the application to continue running.
     *
     * @param string $message Content of log event.
     * @param array  $data    Associative array of contextual support data that goes with the log event.
     *
     * @throws RuntimeException
     */
    public function warning($message = '', array $data = null): void
    {
        if ($this->logAtThisLevel(LogLevel::WARNING)) {
            $this->log(LogLevel::WARNING, $message, $data);
        }
    }

    /**
     * Log an error message.
     * Error events that might still allow the application to continue running.
     * Runtime errors that do not require immediate action but should typically be logged and monitored.
     *
     * @param string $message Content of log event.
     * @param array  $data    Associative array of contextual support data that goes with the log event.
     *
     * @throws RuntimeException
     */
    public function error($message = '', array $data = null): void
    {
        if ($this->logAtThisLevel(LogLevel::ERROR)) {
            $this->log(LogLevel::ERROR, $message, $data);
        }
    }

    /**
     * Log a critical condition.
     * Application components being unavailable, unexpected exceptions, etc.
     *
     * @param string $message Content of log event.
     * @param array  $data    Associative array of contextual support data that goes with the log event.
     *
     * @throws RuntimeException
     */
    public function critical($message = '', array $data = null): void
    {
        if ($this->logAtThisLevel(LogLevel::CRITICAL)) {
            $this->log(LogLevel::CRITICAL, $message, $data);
        }
    }

    /**
     * Log an alert.
     * This should trigger an email or SMS alert and wake you up.
     * Example: Entire site down, database unavailable, etc.
     *
     * @param string $message Content of log event.
     * @param array  $data    Associative array of contextual support data that goes with the log event.
     *
     * @throws RuntimeException
     */
    public function alert($message = '', array $data = null): void
    {
        if ($this->logAtThisLevel(LogLevel::ALERT)) {
            $this->log(LogLevel::ALERT, $message, $data);
        }
    }

    /**
     * Log an emergency
     *
     * @param string $message Content of log event.
     * @param array  $data    Associative array of contextual support data that goes with the log event.
     *
     * @throws RuntimeException
     */
    public function emergency($message = '', array $data = null): void
    {
        if ($this->logAtThisLevel(LogLevel::EMERGENCY)) {
            $this->log(LogLevel::EMERGENCY, $message, $data);
        }
    }
}
