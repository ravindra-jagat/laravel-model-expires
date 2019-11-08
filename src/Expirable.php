<?php

namespace Mvdnbrk\ModelExpires;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\InteractsWithTime;

/**
 * @method \Illuminate\Support\Carbon freshTimestamp()
 * @property array $attributes
 * @property array $dates
 */
trait Expirable
{
    use InteractsWithTime;

    /**
     * Initialize the expires trait for an instance.
     *
     * @return void
     */
    public function initializeExpirable()
    {
        $this->dates[] = $this->getExpiresAtColumn();
    }

    /**
     * Set the "expires at" column for an instance.
     *
     * @param  \DateTimeInterface|\DateInterval|int|null  $ttl
     * @return void
     */
    public function setExpiresAtAttribute($ttl)
    {
        $seconds = $this->getSeconds($ttl);

        $this->attributes[$this->getExpiresAtColumn()] = $seconds ? Carbon::now()->addSeconds($seconds) : null;
    }

    /**
     * Determine if the model instance has expired.
     *
     * @return bool
     */
    public function expired()
    {
        $expiresAt = $this->{$this->getExpiresAtColumn()};

        return $expiresAt && $expiresAt->isPast();
    }

    /**
     * Determine if the model instance will expire.
     *
     * @return bool
     */
    public function willExpire()
    {
        $expiresAt = $this->{$this->getExpiresAtColumn()};

        return $expiresAt && $expiresAt->isFuture();
    }

    /**
     * Scope a query to only include expired models.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOnlyExpired(Builder $query)
    {
        return $query->where($this->getQualifiedExpiresAtColumn(), '<=', $this->freshTimestamp());
    }

    /**
     * Scope a query to only include models expiring in the future.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeExpiring(Builder $query)
    {
        $column = $this->getQualifiedExpiresAtColumn();

        return $query->whereNotNull($column)->where($column, '>', $this->freshTimestamp());
    }

    /**
     * Scope a query to only include models expiring in the future.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeNotExpiring(Builder $query)
    {
        return $query->whereNull($this->getQualifiedExpiresAtColumn());
    }

    /**
     * Scope a query to only include expired models.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithoutExpired(Builder $query)
    {
        $column = $this->getQualifiedExpiresAtColumn();

        return $query->where($column, '>', $this->freshTimestamp())->orWhereNull($column);
    }

    /**
     * Get the name of the "expires at" column.
     *
     * @return string
     */
    public function getExpiresAtColumn()
    {
        return defined('static::EXPIRES_AT') ? static::EXPIRES_AT : 'expires_at';
    }

    /**
     * Get the fully qualified "expires at" column.
     *
     * @return string
     */
    public function getQualifiedExpiresAtColumn()
    {
        return $this->qualifyColumn($this->getExpiresAtColumn());
    }

    /**
     * Calculate the number of seconds for the given TTL.
     *
     * @param  \DateTimeInterface|\DateInterval|int|null  $ttl
     * @return int
     */
    protected function getSeconds($ttl)
    {
        $duration = $ttl ? $this->parseDateInterval($ttl) : 0;

        if ($duration instanceof DateTimeInterface) {
            $duration = Carbon::now()->diffInRealSeconds($duration, false);
        }

        return (int) $duration > 0 ? $duration : 0;
    }
}
