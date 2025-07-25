<?php

namespace FASTAPI\CustomTime;

use \DateTimeImmutable;
use \DateTime;
use \DateTimezone;
use \DateInterval;

/**
 * Class CustomTime
 * Represents a custom time object with additional functionalities.
 */
class CustomTime extends DateTimeImmutable{
    /**
     * @var int The timestamp representing the time associated with this object.
     */
    private $time;

    /**
     * @var DateTime The DateTime object representing the date and time associated with this object.
     */
    private $date;

    /**
     * @var DateTimeZone The timezone associated with this object.
     */
    private $timezone;

    /**
     * @var string The default format used for date formatting.
     */
    private $format = DateTimeImmutable::RFC7231;

    /**
     * Constructor.
     *
     * @param string $date A date/time string or DateTimeImmutable object. Defaults to 'now'.
     */
    public function __construct($date = 'now') {
        $this->timezone = new DateTimezone($_ENV['TIMEZONE'] ?? 'UTC');
        parent::__construct($date ?? 'now', $this->timezone);
        $this->date = $this;
        $this->time = $this->getTimestamp();
    }

    /**
     * Gets the formatted date based on the specified format.
     *
     * @param string|null $format The format to use for formatting the date. Defaults to the class default format.
     * @return string The formatted date string.
     */
    public function get_date($format = null) {
        return $this->format($format ?? $this->format);
    }

    /**
     * Gets the formatted date based on the specified format.
     *
     * @param string|null $format The format to use for formatting the date. Defaults to the class default format.
     * @return DateTimeImmutable The formatted date string.
     */
    public function get_date_instance() {
        return $this;
    }

    /**
     * Gets the formatted time based on the specified format.
     *
     * @param string $format The format to use for formatting the time. Defaults to 'H:i:s'.
     * @return string The formatted time string.
     */
    public function get_formated_time($format = 'H:i:s') {
        return $this->format($format);
    }

    /**
     * Gets the formatted time based on the specified format.
     * @return string The formatted time string.
     */
    public function get_time() {
        return $this->getTimestamp();
    }
    /**
     * Gets the formatted time based on the specified format.
     * @return string The formatted/unfomatted time string.
     */
    public static function now($format = '') {
        $obj = new self();
        if($format){
            return $obj->get_formated_time($format);
        }
        return $obj->get_time();
    }

    /**
     * Gets the formatted UTC time based on the specified format.
     *
     * @param string $format The format to use for formatting the time. Defaults to 'H:i:s'.
     * @return string The formatted UTC time string.
     */
    public function get_utc_time($format = 'H:i:s') {
        $utcDate = $this->setTimezone(new DateTimeZone('UTC'));
        return $utcDate->format($format);
    }

    /**
     * Gets the timestamp associated with this object.
     *
     * @return int The timestamp.
     */
    public function get_timestamp() {
        return $this->getTimestamp();
    }

    /**
     * Extends the date and time by the specified duration.
     *
     * @param int $days The number of days to add.
     * @param int $hours The number of hours to add.
     * @param int $minutes The number of minutes to add.
     * @param int $seconds The number of seconds to add.
     * @return CustomTime The modified CustomTime object.
     */
    public function extend_date($days = 0, $hours = 0, $minutes = 0, $seconds = 0) {
        $interval = sprintf("P%dDT%dH%dM%dS", $days, $hours, $minutes, $seconds);
        $newDate = $this->add(new DateInterval($interval));
        return new self($newDate->format('Y-m-d H:i:s'));
    }

    /**
     * Checks if this time is before another time.
     *
     * @param CustomTime $otherTime The other time to compare.
     * @return bool True if this time is before the other time, false otherwise.
     */
    public function isBefore($otherTime) {
        return $this < $otherTime;
    }
    
    /**
     * Checks if this time is after another time.
     *
     * @param CustomTime $otherTime The other time to compare.
     * @return bool True if this time is after the other time, false otherwise.
     */
    public function isAfter($otherTime) {
        return $this > $otherTime;
    }
    
    /**
     * Checks if this time is equal to another time.
     *
     * @param CustomTime $otherTime The other time to compare.
     * @return bool True if this time is equal to the other time, false otherwise.
     */
    public function equals($otherTime) {
        return $this == $otherTime;
    }

    /**
     * Sets the timezone for this object.
     *
     * @param string|DateTimeZone $timezone The timezone identifier or DateTimeZone object.
     * @return CustomTime The modified CustomTime object.
     * @throws \InvalidArgumentException If an invalid timezone argument is provided.
     */
    public function set_timezone($timezone) {
        if ($timezone instanceof DateTimeZone) {
            $this->timezone = $timezone;
        } elseif (is_string($timezone)) {
            $this->timezone = new DateTimeZone($timezone);
        } else {
            throw new \InvalidArgumentException('Invalid timezone argument. Expected string or DateTimeZone object.');
        }
        $newDate = $this->setTimezone($this->timezone);
        return new self($newDate->format('Y-m-d H:i:s'));
    }

    /**
     * Sets the default format used for date formatting.
     *
     * @param string $format The default format to set.
     * @return void
     */
    public function set_format($format) {
        $this->format = $format;
    }

    /**
     * Formats the date and time using the specified format.
     *
     * @param string|null $format The format to use for formatting. Defaults to the class default format.
     * @return string The formatted date and time string.
     */
    public function formatCustom($format = null) {
        return $this->format($format ?? $this->format);
    }

    /**
     * Calculates the difference in days between this time and another time.
     *
     * @param CustomTime $otherTime The other time to calculate the difference from.
     * @return int The difference in days.
     */
    public function diffInDays($otherTime) {
        $diff = $this->diff($otherTime);
        return $diff->days;
    }

    /**
     * Adds the specified number of days to this time.
     *
     * @param int $days The number of days to add.
     * @return CustomTime The modified CustomTime object.
     */
    public function add_days($days) {
        $newDate = $this->modify("+{$days} days");
        return new self($newDate->format('Y-m-d H:i:s'));
    }
    
    /**
     * Subtracts the specified number of days from this time.
     *
     * @param int $days The number of days to subtract.
     * @return CustomTime The modified CustomTime object.
     */
    public function subtract_days($days) {
        $newDate = $this->modify("-{$days} days");
        return new self($newDate->format('Y-m-d H:i:s'));
    }

    /**
     * Adds the specified number of weeks to this time.
     *
     * @param int $weeks The number of weeks to add.
     * @return CustomTime The modified CustomTime object.
     */
    public function add_weeks($weeks) {
        $newDate = $this->modify("+{$weeks} weeks");
        return new self($newDate->format('Y-m-d H:i:s'));
    }
    
    /**
     * Subtracts the specified number of weeks from this time.
     *
     * @param int $weeks The number of weeks to subtract.
     * @return CustomTime The modified CustomTime object.
     */
    public function subtract_weeks($weeks) {
        $newDate = $this->modify("-{$weeks} weeks");
        return new self($newDate->format('Y-m-d H:i:s'));
    }

    /**
     * Adds the specified number of months to this time.
     *
     * @param int $months The number of months to add.
     * @return CustomTime The modified CustomTime object.
     */
    public function add_months($months) {
        $newDate = $this->modify("+{$months} months");
        return new self($newDate->format('Y-m-d H:i:s'));
    }
    
    /**
     * Subtracts the specified number of months from this time.
     *
     * @param int $months The number of months to subtract.
     * @return CustomTime The modified CustomTime object.
     */
    public function subtract_months($months) {
        $newDate = $this->modify("-{$months} months");
        return new self($newDate->format('Y-m-d H:i:s'));
    }

    /**
     * Adds the specified number of years to this time.
     *
     * @param int $years The number of years to add.
     * @return CustomTime The modified CustomTime object.
     */
    public function add_years($years) {
        $newDate = $this->modify("+{$years} years");
        return new self($newDate->format('Y-m-d H:i:s'));
    }
    
    /**
     * Subtracts the specified number of years from this time.
     *
     * @param int $years The number of years to subtract.
     * @return CustomTime The modified CustomTime object.
     */
    public function subtract_years($years) {
        $newDate = $this->modify("-{$years} years");
        return new self($newDate->format('Y-m-d H:i:s'));
    }

    /**
     * Adds the specified number of minutes to this time.
     *
     * @param int $minutes The number of minutes to add.
     * @return CustomTime The modified CustomTime object.
     * @throws \InvalidArgumentException If the number of minutes is not an integer.
     */
    public function add_minutes($minutes) {
        if (!is_int($minutes)) {
            throw new \InvalidArgumentException('Number of minutes must be an integer.');
        }

        $newDate = $this->modify("+{$minutes} minutes");
        return new self($newDate->format('Y-m-d H:i:s'));
    }

    /**
     * Adds the specified number of hours to this time.
     *
     * @param int $hours The number of hours to add.
     * @return CustomTime The modified CustomTime object.
     * @throws \InvalidArgumentException If the number of hours is not an integer.
     */
    public function add_hours($hours) {
        if (!is_int($hours)) {
            throw new \InvalidArgumentException('Number of hours must be an integer.');
        }

        $newDate = $this->modify("+{$hours} hours");
        return new self($newDate->format('Y-m-d H:i:s'));
    }

    /**
     * Adds the specified number of seconds to this time.
     *
     * @param int $seconds The number of seconds to add.
     * @return CustomTime The modified CustomTime object.
     * @throws \InvalidArgumentException If the number of seconds is not an integer.
     */
    public function add_seconds($seconds) {
        if (!is_int($seconds)) {
            throw new \InvalidArgumentException('Number of seconds must be an integer.');
        }

        $newDate = $this->modify("+{$seconds} seconds");
        return new self($newDate->format('Y-m-d H:i:s'));
    }

    /**
     * Subtracts the specified number of minutes from this time.
     *
     * @param int $minutes The number of minutes to subtract.
     * @return CustomTime The modified CustomTime object.
     * @throws \InvalidArgumentException If the number of minutes is not an integer.
     */
    public function subtract_minutes($minutes) {
        if (!is_int($minutes)) {
            throw new \InvalidArgumentException('Number of minutes must be an integer.');
        }

        $newDate = $this->modify("-{$minutes} minutes");
        return new self($newDate->format('Y-m-d H:i:s'));
    }

    /**
     * Subtracts the specified number of hours from this time.
     *
     * @param int $hours The number of hours to subtract.
     * @return CustomTime The modified CustomTime object.
     * @throws \InvalidArgumentException If the number of hours is not an integer.
     */
    public function subtract_hours($hours) {
        if (!is_int($hours)) {
            throw new \InvalidArgumentException('Number of hours must be an integer.');
        }

        $newDate = $this->modify("-{$hours} hours");
        return new self($newDate->format('Y-m-d H:i:s'));
    }

    /**
     * Subtracts the specified number of seconds from this time.
     *
     * @param int $seconds The number of seconds to subtract.
     * @return CustomTime The modified CustomTime object.
     * @throws \InvalidArgumentException If the number of seconds is not an integer.
     */
    public function subtract_seconds($seconds) {
        if (!is_int($seconds)) {
            throw new \InvalidArgumentException('Number of seconds must be an integer.');
        }

        $newDate = $this->modify("-{$seconds} seconds");
        return new self($newDate->format('Y-m-d H:i:s'));
    }

    /**
     * Serializes the CustomTime object to a JSON representation.
     *
     * @return string The JSON representation of the object.
     */
    public function serialize() {
        return json_encode([
            'date' => $this->format('Y-m-d H:i:s'),
            'timezone' => $this->getTimezone()->getName()
        ]);
    }

    /**
     * Deserializes a JSON representation into a CustomTime object.
     *
     * @param string $data The JSON data to deserialize.
     * @return CustomTime The deserialized CustomTime object.
     * @throws \InvalidArgumentException If the serialized data is invalid or cannot be deserialized.
     */
    public static function deserialize($data) {
        $decodedData = json_decode($data, true);

        if ($decodedData === null || !isset($decodedData['date'], $decodedData['timezone'])) {
            throw new \InvalidArgumentException('Invalid serialized data');
        }

        $timezone = new DateTimeZone($decodedData['timezone']);
        $dateTime = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $decodedData['date'], $timezone);

        if ($dateTime === false) {
            throw new \InvalidArgumentException('Invalid date format in serialized data');
        }

        return new CustomTime($dateTime->format('Y-m-d H:i:s'));
    }

    /**
     * Adds a DateInterval to the CustomTime object.
     *
     * @param DateInterval $interval The interval to add.
     * @return CustomTime The modified CustomTime object.
     */
    public function __add($interval) {
        if ($interval instanceof DateInterval) {
            $newDate = $this->add($interval);
            return new self($newDate->format('Y-m-d H:i:s'));
        }
        return $this;
    }
    
    /**
     * Subtracts a DateInterval from the CustomTime object.
     *
     * @param DateInterval $interval The interval to subtract.
     * @return CustomTime The modified CustomTime object.
     */
    public function __sub($interval) {
        if ($interval instanceof DateInterval) {
            $newDate = $this->sub($interval);
            return new self($newDate->format('Y-m-d H:i:s'));
        }
        return $this;
    }
    
    /**
     * Converts the CustomTime object to a string representation.
     *
     * @return string The string representation of the CustomTime object.
     */
    public function __toString() {
        return $this->format(DateTimeImmutable::RFC7231);
    }
}