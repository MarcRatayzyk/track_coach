<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CoachStatsDashboardItem extends Model
{
    public const TYPE_BUILTIN = 'builtin';

    public const TYPE_CUSTOM = 'custom';

    public const BUILTIN_VOLUME_WEEKLY = 'volume_weekly';

    public const BUILTIN_TOPSET_E1RM = 'topset_e1rm';

    public const BUILTIN_VOLUME_DISTRIBUTION = 'volume_distribution';

    public const BUILTIN_AVG_LOAD_WEEKLY = 'avg_load_weekly';

    protected $fillable = [
        'coach_id',
        'item_type',
        'builtin_key',
        'template_id',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    /**
     * @return list<string>
     */
    public static function builtinKeys(): array
    {
        return [
            self::BUILTIN_VOLUME_WEEKLY,
            self::BUILTIN_TOPSET_E1RM,
            self::BUILTIN_VOLUME_DISTRIBUTION,
            self::BUILTIN_AVG_LOAD_WEEKLY,
        ];
    }

    public function coach(): BelongsTo
    {
        return $this->belongsTo(User::class, 'coach_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(CoachChartTemplate::class, 'template_id');
    }
}
