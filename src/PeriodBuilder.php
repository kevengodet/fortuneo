<?php

declare(strict_types=1);

namespace Keven\Fortuneo;

/**
 * DatePeriod builder with a fluid interface
 *
 * Usage:
 *
 *      $period = PeriodBuilder::from('3 months ago')->untilNow();
 *      $period = PeriodBuilder::from('2010-01-01')->monthly()->to('2015-01-02');
 */
final class PeriodBuilder
{
    private \DateTimeInterface $from;
    private \DateInterval $interval;

    /** @throws \Exception */
    public static function from($startedAt): PeriodBuilder
    {
        $period = new self;
        $period->from = $period->normalizeDateTime($startedAt);

        // Monthly by default
        $period->monthly();

        return $period;
    }

    public function monthly(): self
    {
        $this->interval = new \DateInterval('P1M');

        return $this;
    }

    /** @throws \Exception */
    private function normalizeDateTime($dateTime): \DateTimeImmutable
    {
        if ($dateTime instanceof \DateTimeImmutable) {
            return $dateTime;
        }

        if ($dateTime instanceof \DateTime) {
            return \DateTimeImmutable::createFromMutable($dateTime);
        }

        if (is_string($dateTime)) {
            return new \DateTimeImmutable($dateTime);
        }

        return new \DateTimeImmutable();
    }

    public function to($endedAt = null): \DatePeriod
    {
        return new \DatePeriod($this->from, $this->interval, $this->normalizeDateTime($endedAt));
    }

    public function untilNow(): \DatePeriod
    {
        return $this->to();
    }
}
