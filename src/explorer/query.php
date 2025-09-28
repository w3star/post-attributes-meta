<?php

namespace OutdoorWww\Explorer;

use OutdoorWww\Config\Fields;
use OutdoorWww\Config\MetaHelper;

class Query
{
    /** Mappt GET → normalisierte Filterwerte (inkl. Clamps/Defaults) */
    public static function readFilters(): array
    {
        // --- Rating / Exklusivität ---
        $min_rating = isset($_GET['min_rating']) ? max(0, (int) $_GET['min_rating']) : 0;

        // Unterstütze sowohl neuen als auch alten Parameternamen
        if (isset($_GET['min_exclusivity'])) {
            $min_excl = max(0, (int) $_GET['min_exclusivity']);
        } elseif (isset($_GET['min_beauty'])) {
            $min_excl = max(0, (int) $_GET['min_beauty']); // Backcompat
        } else {
            $min_excl = 0;
        }

        // --- Dauer (Minuten) ---
        $dur_from = isset($_GET['dur_from']) ? max(0, (int) $_GET['dur_from']) : 0;
        $dur_to   = isset($_GET['dur_to'])   ? max(0, (int) $_GET['dur_to'])   : 720;
        if ($dur_from > $dur_to) { $t=$dur_from; $dur_from=$dur_to; $dur_to=$t; }

        // --- Kategorien ---
        $cats_raw = isset($_GET['cats']) ? (array) $_GET['cats'] : [];
        $cats = array_filter(array_map('intval', $cats_raw));

        // --- Sortierung ---
        $sort = isset($_GET['star_sort']) ? sanitize_text_field($_GET['star_sort']) : 'date_desc';

        // --- Schwierigkeit (wertebasiert, dynamisch aus Meta::defaults) ---
        $diff_values_all = MetaHelper::optionValues(Fields::difficulty()); // z.B. ['', 'T1','T2','T3','T4','T5']
        $diff_values = $diff_values_all;

        // optionalen leeren Placeholder am Anfang wegschneiden
        if ($diff_values && $diff_values[0] === '') {
            array_shift($diff_values);
        }
        $max_idx = count($diff_values); // Anzahl realer Stufen

        // Slider-Indices (1..N)
        $d_from_i = isset($_GET['diff_from_i']) ? (int) $_GET['diff_from_i'] : 1;
        $d_to_i   = isset($_GET['diff_to_i'])   ? (int) $_GET['diff_to_i']   : $max_idx;
        $d_from_i = max(1, min($max_idx, $d_from_i));
        $d_to_i   = max(1, min($max_idx, $d_to_i));
        if ($d_from_i > $d_to_i) { $t=$d_from_i; $d_from_i=$d_to_i; $d_to_i=$t; }

        // Erlaubte VALUES aus Optionsfenster
        $diff_allowed = $max_idx > 0
            ? array_slice($diff_values, $d_from_i - 1, $d_to_i - $d_from_i + 1)
            : [];

        return [
            'min_rating' => $min_rating,
            'min_excl'   => $min_excl,
            'dur_from'   => $dur_from,
            'dur_to'     => $dur_to,
            'cats'       => $cats,
            'sort'       => $sort,

            // für die UI (kannst du im Renderer ausgeben)
            'd_from_i'   => $d_from_i,
            'd_to_i'     => $d_to_i,

            // für die Query:
            'diff_allowed' => $diff_allowed,
            'diff_total'   => $max_idx,
        ];
    }


    /** Liefert [args, meta_key, paged] für WP_Query + Order */
    public static function buildArgs(array $f, int $per_page = 24): array
    {
        $orderby = ['date' => 'DESC'];
        $meta_key = '';

        // Sort-Schlüssel unterstützen beide Benennungen (Backcompat für "beauty_*")
        switch ($f['sort']) {
            case 'title_asc':  $orderby=['title'=>'ASC'];  break;
            case 'title_desc': $orderby=['title'=>'DESC']; break;
            case 'date_asc':   $orderby=['date'=>'ASC'];   break;

            case 'rating_desc': $meta_key = Fields::rating();      $orderby=['meta_value_num'=>'DESC','date'=>'DESC']; break;
            case 'rating_asc':  $meta_key = Fields::rating();      $orderby=['meta_value_num'=>'ASC','date'=>'DESC'];  break;

            case 'exclusivity_desc':
            case 'beauty_desc': $meta_key = Fields::exclusivity(); $orderby=['meta_value_num'=>'DESC','date'=>'DESC']; break;

            case 'exclusivity_asc':
            case 'beauty_asc':  $meta_key = Fields::exclusivity(); $orderby=['meta_value_num'=>'ASC','date'=>'DESC'];  break;

            case 'dur_asc':     $meta_key = Fields::duration();    $orderby=['meta_value_num'=>'ASC','date'=>'DESC'];  break;
            case 'dur_desc':    $meta_key = Fields::duration();    $orderby=['meta_value_num'=>'DESC','date'=>'DESC']; break;

            default:            $orderby=['date'=>'DESC'];
        }

        // Meta-Filter
        $meta_query = [
            'relation' => 'AND',

            // Rating (>=)
            [
                'key'     => Fields::rating(),
                'value'   => $f['min_rating'],
                'compare' => '>=',
                'type'    => 'NUMERIC',
            ],

            // Exklusivität (>=)
            [
                'key'     => Fields::exclusivity(),
                'value'   => $f['min_excl'],
                'compare' => '>=',
                'type'    => 'NUMERIC',
            ],

            // Dauer (BETWEEN)
            [
                'key'     => Fields::duration(),
                'value'   => [ $f['dur_from'], $f['dur_to'] ],
                'compare' => 'BETWEEN',
                'type'    => 'NUMERIC',
            ],
        ];

        // Schwierigkeit (IN) – nur filtern, wenn nicht "alle Stufen"
        if (!empty($f['diff_allowed']) && $f['diff_total'] > 0 && count($f['diff_allowed']) < $f['diff_total']) {
            $meta_query[] = [
                'key'     => Fields::difficulty(),
                'value'   => array_values($f['diff_allowed']),
                'compare' => 'IN',
            ];
        }

        $paged = max(1, (int) ($_GET['star_page'] ?? 1));

        $args = [
            'post_type'      => 'post',
            'posts_per_page' => $per_page,
            'paged'          => $paged,
            'meta_query'     => $meta_query,
            'orderby'        => $orderby,
        ];
        if ($meta_key) $args['meta_key'] = $meta_key;
        if (!empty($f['cats'])) $args['category__in'] = $f['cats'];

        return [$args, $meta_key, $paged];
    }
}
