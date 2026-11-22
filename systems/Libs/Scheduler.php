<?php

namespace Flame;

use DateTimeImmutable;
use InvalidArgumentException;
use RuntimeException;

class Scheduler
{
    private $tasks = [];
    private $lockDirectory;

    public function __construct($lockDirectory = null)
    {
        $this->lockDirectory = $lockDirectory ?: APP . 'tmp' . DS . 'cron';
    }

    public function add($id, $expression, callable $callback)
    {
        if (!is_string($id) || !preg_match('/^[A-Za-z0-9_.-]+$/D', $id)) {
            throw new InvalidArgumentException('Task IDs may contain only letters, numbers, dots, underscores, and hyphens.');
        }
        if (isset($this->tasks[$id])) {
            throw new InvalidArgumentException("Scheduled task '{$id}' is already registered.");
        }
        CronExpression::validate($expression);
        $this->tasks[$id] = ['expression' => $expression, 'callback' => $callback];

        return $this;
    }

    public function everyMinute($id, callable $callback)
    {
        return $this->add($id, '* * * * *', $callback);
    }

    public function hourly($id, callable $callback, $minute = 0)
    {
        $minute = filter_var($minute, FILTER_VALIDATE_INT);
        if ($minute === false || $minute < 0 || $minute > 59) {
            throw new InvalidArgumentException('Hourly task minute must be an integer from 0 to 59.');
        }
        return $this->add($id, $minute . ' * * * *', $callback);
    }

    public function dailyAt($id, $time, callable $callback)
    {
        if (!is_string($time) || !preg_match('/^([01]\d|2[0-3]):([0-5]\d)$/D', $time, $matches)) {
            throw new InvalidArgumentException('Daily task time must use HH:MM in 24-hour format.');
        }

        return $this->add($id, $matches[2] . ' ' . $matches[1] . ' * * *', $callback);
    }

    public function run(DateTimeImmutable $now = null)
    {
        $now = $now ?: new DateTimeImmutable();
        if (!is_dir($this->lockDirectory) && !mkdir($this->lockDirectory, 0700, true) && !is_dir($this->lockDirectory)) {
            throw new RuntimeException("Unable to create scheduler lock directory '{$this->lockDirectory}'.");
        }

        foreach ($this->tasks as $id => $task) {
            if (!CronExpression::isDue($task['expression'], $now)) {
                continue;
            }

            $lockPath = $this->lockDirectory . DS . $id . '.lock';
            $lock = fopen($lockPath, 'c+');
            if ($lock === false) {
                throw new RuntimeException("Unable to open scheduler lock for '{$id}'.");
            }

            try {
                if (!flock($lock, LOCK_EX | LOCK_NB)) {
                    continue;
                }

                $minute = $now->format('YmdHi');
                rewind($lock);
                if (trim((string) stream_get_contents($lock)) === $minute) {
                    continue;
                }

                call_user_func($task['callback']);
                rewind($lock);
                if (!ftruncate($lock, 0) || fwrite($lock, $minute) === false || !fflush($lock)) {
                    throw new RuntimeException("Unable to record the last run for scheduled task '{$id}'.");
                }
            } finally {
                flock($lock, LOCK_UN);
                fclose($lock);
            }
        }
    }
}

class CronExpression
{
    private static $ranges = [
        [0, 59],
        [0, 23],
        [1, 31],
        [1, 12],
        [0, 7],
    ];

    public static function validate($expression)
    {
        self::parse($expression);
    }

    public static function isDue($expression, DateTimeImmutable $date)
    {
        $fields = self::parse($expression);
        $values = [
            (int) $date->format('i'),
            (int) $date->format('G'),
            (int) $date->format('j'),
            (int) $date->format('n'),
            (int) $date->format('w'),
        ];

        foreach ([0, 1, 3] as $index) {
            if (!in_array($values[$index], $fields[$index], true)) {
                return false;
            }
        }

        $dayMatches = in_array($values[2], $fields[2], true);
        $weekdayMatches = in_array($values[4], $fields[4], true)
            || ($values[4] === 0 && in_array(7, $fields[4], true));
        $dayWildcard = $fields[2]['wildcard'];
        $weekdayWildcard = $fields[4]['wildcard'];

        if (!$dayWildcard && !$weekdayWildcard) {
            return $dayMatches || $weekdayMatches;
        }
        return $dayMatches && $weekdayMatches;
    }

    private static function parse($expression)
    {
        if (!is_string($expression)) {
            throw new InvalidArgumentException('Cron expression must be a string.');
        }

        $parts = preg_split('/\s+/', trim($expression));
        if (count($parts) !== 5) {
            throw new InvalidArgumentException('Cron expressions must contain five fields: minute hour day month weekday.');
        }

        $parsed = [];
        foreach ($parts as $index => $part) {
            $parsed[$index] = self::parseField($part, self::$ranges[$index]);
        }

        return $parsed;
    }

    private static function parseField($field, array $range)
    {
        $values = [];
        foreach (explode(',', $field) as $segment) {
            if ($segment === '') {
                throw new InvalidArgumentException('Invalid empty value in cron expression.');
            }

            $stepParts = explode('/', $segment);
            if (count($stepParts) > 2 || (isset($stepParts[1]) && (!ctype_digit($stepParts[1]) || (int) $stepParts[1] < 1))) {
                throw new InvalidArgumentException("Invalid cron step '{$segment}'.");
            }
            $step = isset($stepParts[1]) ? (int) $stepParts[1] : 1;
            $base = $stepParts[0];

            if ($base === '*') {
                $start = $range[0];
                $end = $range[1];
            } elseif (preg_match('/^(\d+)-(\d+)$/D', $base, $matches)) {
                $start = (int) $matches[1];
                $end = (int) $matches[2];
            } elseif (ctype_digit($base)) {
                $start = (int) $base;
                $end = isset($stepParts[1]) ? $range[1] : $start;
            } else {
                throw new InvalidArgumentException("Invalid cron field '{$field}'.");
            }

            if ($start < $range[0] || $end > $range[1] || $start > $end) {
                throw new InvalidArgumentException("Cron value '{$base}' is outside its allowed range.");
            }
            for ($value = $start; $value <= $end; $value += $step) {
                $values[$value] = $value;
            }
        }

        $values = array_values($values);
        $values['wildcard'] = $field === '*' || (bool) preg_match('/^\*\/\d+$/D', $field);
        return $values;
    }
}
