<?php

namespace App\Models;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendanceSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'title',
        'open_time',
        'close_time',
        'late_tolerance_minutes',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'open_time' => 'datetime',
            'close_time' => 'datetime',
            'late_tolerance_minutes' => 'integer',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function isOpen(): bool
    {
        return $this->status === 'OPEN';
    }

    public function isWithinWindow(?CarbonInterface $now = null): bool
    {
        $now = $now ?: Carbon::now();

        return $this->isOpen() && $now->between($this->open_time, $this->close_time);
    }

    public function isLate(?CarbonInterface $time = null): bool
    {
        $time = $time ?: Carbon::now();
        $lateThreshold = $this->open_time->copy()->addMinutes($this->late_tolerance_minutes);

        return $time->greaterThan($lateThreshold);
    }
}
