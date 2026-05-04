<?php

namespace App\Service;

use App\Entity\Farm;
use App\Entity\Field;

class FarmFieldInsightService
{
    /**
     * @return array{
     *     farmArea: ?float,
     *     usedArea: float,
     *     unusedArea: ?float,
     *     usagePercent: ?float,
     *     fieldCount: int,
     *     oversizedFields: array<int, array{field: Field, area: float, sharePercent: ?float}>,
     *     insights: array<int, array{type: string, title: string, message: string}>
     * }
     */
    public function analyze(Farm $farm): array
    {
        $fields = $farm->getFields()->toArray();
        $farmArea = $farm->getArea() !== null ? (float) $farm->getArea() : null;
        $usedArea = array_reduce($fields, static function (float $carry, Field $field): float {
            return $carry + (float) $field->getArea();
        }, 0.0);

        $unusedArea = $farmArea !== null ? max(0.0, $farmArea - $usedArea) : null;
        $usagePercent = $farmArea !== null && $farmArea > 0
            ? min(100.0, round(($usedArea / $farmArea) * 100, 1))
            : null;

        $oversizedFields = [];
        foreach ($fields as $field) {
            $fieldArea = (float) $field->getArea();
            $sharePercent = $farmArea !== null && $farmArea > 0
                ? round(($fieldArea / $farmArea) * 100, 1)
                : null;

            if (
                ($sharePercent !== null && $sharePercent >= 45.0)
                || ($fieldArea >= 12.0 && ($sharePercent === null || $sharePercent >= 30.0))
            ) {
                $oversizedFields[] = [
                    'field' => $field,
                    'area' => $fieldArea,
                    'sharePercent' => $sharePercent,
                ];
            }
        }

        $insights = [];

        if ($farmArea === null || $farmArea <= 0) {
            $insights[] = [
                'type' => 'info',
                'title' => 'Add the farm area first',
                'message' => 'Set the total farm area to unlock usage insights and better field planning recommendations.',
            ];
        } elseif ($fields === []) {
            $insights[] = [
                'type' => 'warning',
                'title' => 'No fields are defined yet',
                'message' => sprintf('This farm has %.2f ha available. Add fields to start organizing and tracking the used area.', $farmArea),
            ];
        } elseif ($usagePercent !== null && $usagePercent < 45.0) {
            $insights[] = [
                'type' => 'warning',
                'title' => 'Large part of the farm is still unused',
                'message' => sprintf('Only %.1f%% of the farm area is covered by fields. Consider adding more fields to organize the remaining %.2f ha.', $usagePercent, $unusedArea ?? 0.0),
            ];
        } elseif ($usagePercent !== null && $usagePercent < 75.0) {
            $insights[] = [
                'type' => 'info',
                'title' => 'There is room to structure more land',
                'message' => sprintf('About %.1f%% of the farm is currently in use. You still have %.2f ha that could be turned into new managed fields.', $usagePercent, $unusedArea ?? 0.0),
            ];
        } elseif ($usagePercent !== null) {
            $insights[] = [
                'type' => 'success',
                'title' => 'Farm area is being used efficiently',
                'message' => sprintf('Around %.1f%% of the farm area is already assigned to fields, which gives you a well-structured layout.', $usagePercent),
            ];
        }

        if ($oversizedFields !== []) {
            $fieldNames = array_map(static fn (array $item): string => $item['field']->getName(), $oversizedFields);
            $insights[] = [
                'type' => 'warning',
                'title' => 'One or more fields may be too large',
                'message' => sprintf('%s look oversized for easier management. Splitting them into smaller fields could improve planning, monitoring, and crop rotation.', implode(', ', $fieldNames)),
            ];
        } elseif ($fields !== [] && count($fields) >= 2) {
            $insights[] = [
                'type' => 'success',
                'title' => 'Field sizes look balanced',
                'message' => 'The current fields are reasonably distributed, which is good for day-to-day supervision and task planning.',
            ];
        }

        return [
            'farmArea' => $farmArea,
            'usedArea' => round($usedArea, 2),
            'unusedArea' => $unusedArea !== null ? round($unusedArea, 2) : null,
            'usagePercent' => $usagePercent,
            'fieldCount' => count($fields),
            'oversizedFields' => $oversizedFields,
            'insights' => $insights,
        ];
    }
}
