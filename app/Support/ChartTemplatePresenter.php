<?php

namespace App\Support;

use App\Models\CoachChartTemplate;
use App\Models\CoachStatsDashboardItem;

class ChartTemplatePresenter
{
    /**
     * @return list<array<string, mixed>>
     */
    public static function listTemplatesForCoach(int $coachId): array
    {
        return CoachChartTemplate::query()
            ->where('coach_id', $coachId)
            ->orderBy('name')
            ->get()
            ->map(fn (CoachChartTemplate $template) => self::templateToArray($template))
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function listDashboardItemsForCoach(int $coachId): array
    {
        return CoachStatsDashboardItem::query()
            ->where('coach_id', $coachId)
            ->with('template')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (CoachStatsDashboardItem $item) => self::dashboardItemToArray($item))
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public static function templateToArray(CoachChartTemplate $template): array
    {
        return [
            'id' => $template->id,
            'name' => $template->name,
            'config' => $template->config ?? ChartTemplateSupport::defaultConfig(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function dashboardItemToArray(CoachStatsDashboardItem $item): array
    {
        $payload = [
            'id' => $item->id,
            'item_type' => $item->item_type,
            'builtin_key' => $item->builtin_key,
            'sort_order' => $item->sort_order,
        ];

        if ($item->item_type === CoachStatsDashboardItem::TYPE_CUSTOM && $item->template !== null) {
            $payload['template'] = self::templateToArray($item->template);
        }

        return $payload;
    }
}
