<?php

namespace App\Services;

/**
 * แปลง raw JSON จาก IPD API (HOSxP) เป็นตารางสำหรับแสดงผล
 */
class HosxpPayloadParser
{
    /**
     * @return array{merged: list<array<string, mixed>>, endpoints: array<string, list<array<string, mixed>>>}
     */
    public function parse(array $payload): array
    {
        $endpoints = $payload['endpoints'] ?? [];
        if (! is_array($endpoints)) {
            return ['merged' => [], 'endpoints' => []];
        }

        $merged = [];

        foreach ($this->itemsFromEndpoint($endpoints, 'current-patients') as $item) {
            $key = $this->wardKey($item);
            $merged[$key] = $this->baseRow($item);
            $merged[$key]['patient_count'] = (int) ($item['count_an'] ?? 0);
        }

        foreach ($this->itemsFromEndpoint($endpoints, 'admissions-today') as $item) {
            $key = $this->wardKey($item);
            if (! isset($merged[$key])) {
                $merged[$key] = $this->baseRow($item);
            }
            $merged[$key]['admissions_today'] = (int) ($item['total_admissions'] ?? 0);
        }

        foreach ($this->itemsFromEndpoint($endpoints, 'discharges-today') as $item) {
            $key = $this->wardKey($item);
            if (! isset($merged[$key])) {
                $merged[$key] = $this->baseRow($item);
            }
            $merged[$key]['discharges_today'] = (int) ($item['total_discharges'] ?? 0);
            $merged[$key]['deaths_today'] = (int) ($item['count_dead'] ?? 0);
        }

        foreach ($this->itemsFromEndpoint($endpoints, 'bed-moves-today') as $item) {
            $key = $this->wardKey($item);
            if (! isset($merged[$key])) {
                $merged[$key] = $this->baseRow($item);
            }
            $merged[$key]['moves_in_today'] = (int) ($item['count_receive'] ?? 0);
            $merged[$key]['moves_out_today'] = (int) ($item['count_move'] ?? 0);
        }

        $rows = array_values($merged);
        usort($rows, static fn ($a, $b) => [$a['ward'], $a['ward_name']] <=> [$b['ward'], $b['ward_name']]);

        $endpointTables = [];
        foreach (['current-patients', 'admissions-today', 'discharges-today', 'bed-moves-today'] as $name) {
            $endpointTables[$name] = $this->flattenEndpointRows($name, $this->itemsFromEndpoint($endpoints, $name));
        }

        return [
            'merged'     => $rows,
            'endpoints'  => $endpointTables,
        ];
    }

    /**
     * @param array<string, mixed> $endpoints
     * @return list<array<string, mixed>>
     */
    private function itemsFromEndpoint(array $endpoints, string $name): array
    {
        $ep = $endpoints[$name] ?? null;
        if (! is_array($ep)) {
            return [];
        }
        if (isset($ep['data']['items']) && is_array($ep['data']['items'])) {
            return $ep['data']['items'];
        }
        if (isset($ep['items']) && is_array($ep['items'])) {
            return $ep['items'];
        }

        return [];
    }

    /**
     * @param array<string, mixed> $item
     */
    private function wardKey(array $item): string
    {
        $code = (string) ($item['ward'] ?? $item['nward'] ?? '');
        $name = trim((string) ($item['ward_name'] ?? ''));

        return $code . '|' . $name;
    }

    /**
     * @param array<string, mixed> $item
     * @return array<string, mixed>
     */
    private function baseRow(array $item): array
    {
        return [
            'ward'            => (string) ($item['ward'] ?? $item['nward'] ?? ''),
            'ward_name'       => (string) ($item['ward_name'] ?? ''),
            'ward_name_ward'  => (string) ($item['ward_name_ward'] ?? ''),
            'patient_count'   => 0,
            'admissions_today' => 0,
            'discharges_today' => 0,
            'deaths_today'    => 0,
            'moves_in_today'  => 0,
            'moves_out_today' => 0,
        ];
    }

    /**
     * @param list<array<string, mixed>> $items
     * @return list<array<string, mixed>>
     */
    private function flattenEndpointRows(string $endpoint, array $items): array
    {
        $rows = [];
        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }
            $row = [
                'ward'           => (string) ($item['ward'] ?? $item['nward'] ?? ''),
                'ward_name'      => (string) ($item['ward_name'] ?? ''),
                'ward_name_ward' => (string) ($item['ward_name_ward'] ?? ''),
            ];
            switch ($endpoint) {
                case 'current-patients':
                    $row['value'] = (int) ($item['count_an'] ?? 0);
                    $row['value_label'] = 'จำนวนผู้ป่วย';
                    break;
                case 'admissions-today':
                    $row['value'] = (int) ($item['total_admissions'] ?? 0);
                    $row['value_label'] = 'รับใหม่วันนี้';
                    break;
                case 'discharges-today':
                    $row['discharges'] = (int) ($item['total_discharges'] ?? 0);
                    $row['deaths'] = (int) ($item['count_dead'] ?? 0);
                    break;
                case 'bed-moves-today':
                    $row['moves_in'] = (int) ($item['count_receive'] ?? 0);
                    $row['moves_out'] = (int) ($item['count_move'] ?? 0);
                    break;
            }
            $rows[] = $row;
        }

        usort($rows, static fn ($a, $b) => [$a['ward'], $a['ward_name']] <=> [$b['ward'], $b['ward_name']]);

        return $rows;
    }
}
