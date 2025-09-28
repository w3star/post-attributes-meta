<?php

namespace OutdoorWww\Explorer;

use OutdoorWww\Config\Fields;

class Query
{
    /** Mappt GET → normalisierte Filterwerte (inkl. Clamps/Defaults) */
    public static function readFilters(): array
    {
        $min_rating = isset($_GET['min_rating']) ? max(0, (int)$_GET['min_rating']) : 0;
        $min_exclusivity = isset($_GET['min_exclusivity']) ? max(0, (int)$_GET['min_exclusivity']) : 0;

        $d_from_i = isset($_GET['diff_from_i']) ? max(1, min(3, (int)$_GET['diff_from_i'])) : 1;
        $d_to_i   = isset($_GET['diff_to_i'])   ? max(1, min(3, (int)$_GET['diff_to_i']))   : 3;
        if ($d_from_i > $d_to_i) {
            $t = $d_from_i;
            $d_from_i = $d_to_i;
            $d_to_i = $t;
        }

        $idx2lab = [1 => 'easy', 2 => 'medium', 3 => 'hard'];
        $diff_vals = [];
        for ($i = $d_from_i; $i <= $d_to_i; $i++) $diff_vals[] = $idx2lab[$i];

        $dur_from = isset($_GET['dur_from']) ? max(0, min(4000, (int)$_GET['dur_from'])) : 0;
        $dur_to   = isset($_GET['dur_to'])   ? max(0, min(4000, (int)$_GET['dur_to']))   : 4000;
        if ($dur_from > $dur_to) {
            $t = $dur_from;
            $dur_from = $dur_to;
            $dur_to = $t;
        }

        $cats_raw = isset($_GET['cats']) ? (array)$_GET['cats'] : [];
        $cats = array_filter(array_map('intval', $cats_raw));

        $sort = isset($_GET['owww_sort']) ? sanitize_text_field($_GET['owww_sort']) : 'date_desc';

        return compact('min_rating', 'min_exclusivity', 'd_from_i', 'd_to_i', 'diff_vals', 'dur_from', 'dur_to', 'cats', 'sort');
    }

    /** Liefert [args, meta_key] für WP_Query + Order */
    public static function buildArgs(array $filters, int $per_page = 24): array
    {
        $orderby = ['date' => 'DESC'];
        $meta_key = '';

        switch ($filters['sort']) {
            case 'title_asc':
                $orderby = ['title' => 'ASC'];
                break;
            case 'title_desc':
                $orderby = ['title' => 'DESC'];
                break;
            case 'date_asc':
                $orderby = ['date' => 'ASC'];
                break;
            case 'rating_desc':
            case 'rating_asc':
                $meta_key = Fields::rating();
                break;
            case 'beauty_desc':
            case 'beauty_asc':
                $meta_key = Fields::exclusivity();
                break;
            case 'dur_asc':
            case 'dur_desc':
                $meta_key = Fields::duration();
                break;
            case 'exclusivity_desc':
                $meta_key = Fields::exclusivity();
                $orderby = ['meta_value_num' => 'DESC', 'date' => 'DESC'];
                break;
            case 'exclusivity_asc':
                $meta_key = Fields::exclusivity();
                $orderby = ['meta_value_num' => 'ASC', 'date' => 'DESC'];
                break;
            default:
                $orderby = ['date' => 'DESC'];
        }

        $meta_query = [
            'relation' => 'AND',
            ['key' => Fields::rating(),      'value' => $filters['min_rating'], 'compare' => '>=', 'type' => 'NUMERIC'],
            ['key' => Fields::exclusivity(), 'value' => $filters['min_beauty'], 'compare' => '>=', 'type' => 'NUMERIC'],
            ['key' => Fields::duration(),    'value' => [$filters['dur_from'], $filters['dur_to']], 'compare' => 'BETWEEN', 'type' => 'NUMERIC'],
        ];

        if (count($filters['diff_vals']) < 3) {
            $meta_query[] = [
                'key'     => Fields::difficulty(),
                'value'   => $filters['diff_vals'], // z.B. ['easy','medium'] – siehe dein Mapping
                'compare' => 'IN',
            ];
        }


        
        $paged = max(1, (int)($_GET['owww_page'] ?? 1));

        $args = [
            'post_type'      => 'post',
            'posts_per_page' => $per_page,
            'paged'          => $paged,
            'meta_query'     => $meta_query,
            'orderby'        => $orderby,
        ];
        if ($meta_key) $args['meta_key'] = $meta_key;
        if (!empty($filters['cats'])) $args['category__in'] = $filters['cats'];

        return [$args, $meta_key, $paged];
    }
}
