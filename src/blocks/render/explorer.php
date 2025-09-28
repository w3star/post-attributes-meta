<?php

namespace OutdoorWww\Blocks\Render;

use OutdoorWww\Support\Html;
use OutdoorWww\Support\Icons;
use OutdoorWww\Explorer\Query as ExplorerQuery;
use OutdoorWww\Support\RenderUtils;

use OutdoorWww\Config\Fields;
use OutdoorWww\Config\MetaHelper;

class Explorer
{
    private static function build_url(array $args = []): string
    {
        $url  = (is_ssl() ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        $base = remove_query_arg(array_keys($_GET), $url);
        $new  = add_query_arg(array_merge($_GET, $args), $base);
        return esc_url($new);
    }

    private static function idxLabel(int $i): string
    {
        return $i === 1 ? 'T1' : ($i === 2 ? 'T2' : ($i === 3 ? 'T3' : ($i === 4 ? 'T4' : ($i === 5 ? 'T5' : 'T6'))));
    }

    public static function render(array $attributes, string $content, $block): string
    {
        wp_enqueue_style('owww-explorer-style');
        // view.js kommt über block.json::viewScript → keine doppelte Enqueue hier!

        // 1) Options-Werte laden (in Options-Reihenfolge)
        $diffValues = MetaHelper::optionValues(Fields::difficulty()); // z.B. ['', 'T1','T2','T3','T4','T5'] oder ['','easy','...']
        if ($diffValues) {
            // 2) Leeren Placeholder am Anfang entfernen
            $hasEmptyFirst = ($diffValues[0] === '');
            if ($hasEmptyFirst) array_shift($diffValues);
            $maxIdx = count($diffValues); // 1..N

            // 3) Eingaben normalisieren
            $from = isset($_GET['diff_from_i']) ? max(1, min($maxIdx, (int)$_GET['diff_from_i'])) : 1;
            $to   = isset($_GET['diff_to_i'])   ? max(1, min($maxIdx, (int)$_GET['diff_to_i']))   : $maxIdx;
            if ($from > $to) {
                $t = $from;
                $from = $to;
                $to = $t;
            }

            // 4) Values im Bereich [from..to] sammeln
            $diffAllowed = array_slice($diffValues, $from - 1, $to - $from + 1);

            // 5) meta_query ergänzen (string-Vergleich)
            if (count($diffAllowed) < $maxIdx) {
                $meta_query[] = [
                    'key'     => Fields::difficulty(),
                    'value'   => $diffAllowed,
                    'compare' => 'IN',
                ];
            }
        }


        $f = ExplorerQuery::readFilters();
        [$args, $meta_key, $paged] = ExplorerQuery::buildArgs($f);

        $q = new \WP_Query($args);

        $all_cats = get_categories(['hide_empty' => false]);
        $options = '';
        foreach ($all_cats as $c) {
            $sel = in_array((int)$c->term_id, $f['cats'], true) ? ' selected' : '';
            $options .= '<option value="' . esc_attr($c->term_id) . '"' . $sel . '>' . esc_html($c->name) . '</option>';
        }

        // Sortier-Links toggeln
        $sort = $f['sort'];
        $toggle = function ($key) use ($sort) {
            $pairs = [
                'title'            => ['title_asc', 'title_desc'],
                'date'             => ['date_asc', 'date_desc'],
                'owww_rating'      => ['rating_asc', 'rating_desc'],
                'owww_exclusivity' => ['exclusivity_asc', 'exclusivity_desc'],
                'dur'              => ['dur_asc', 'dur_desc'],
            ];
            $pair = $pairs[$key] ?? ['date_asc', 'date_desc'];
            $next = ($sort === $pair[0]) ? $pair[1] : $pair[0];
            return esc_url(add_query_arg(['owww_sort' => $next], remove_query_arg(null)));
        };

        // Paginator
        $total_pages = max(1, (int)$q->max_num_pages);
        $pager_html = '';
        if ($total_pages > 1) {
            $pager_html .= '<nav class="owww-pager" role="navigation" aria-label="Seitennummerierung"><ul>';
            for ($p = 1; $p <= $total_pages; $p++) {
                $cls = $p === $paged ? ' class="is-active"' : '';
                $url = esc_url(Url::build_url(['owww_page' => $p]));
                $pager_html .= "<li$cls><a href=\"$url\">$p</a></li>";
            }
            $pager_html .= '</ul></nav>';
        }

        // Sort-Auswahl
        $sort_options = [
            'date_desc'   => __('Neueste zuerst', 'outdoor-www'),
            'date_asc'    => __('Älteste zuerst', 'outdoor-www'),
            'rating_desc' => __('Rating absteigend', 'outdoor-www'),
            'rating_asc'  => __('Rating aufsteigend', 'outdoor-www'),
            'exclusivity_desc' => __('Exklusivität absteigend', 'outdoor-www'),
            'exclusivity_asc'  => __('Exklusivität aufsteigend', 'outdoor-www'),
            'dur_asc'     => __('Dauer: kürzeste zuerst', 'outdoor-www'),
            'dur_desc'    => __('Dauer: längste zuerst', 'outdoor-www'),
        ];
        $sort_html = '<select name="owww_sort">';
        foreach ($sort_options as $value => $label) {
            $sel = ($f['sort'] === $value) ? ' selected' : '';
            $sort_html .= '<option value="' . esc_attr($value) . '"' . $sel . '>' . esc_html($label) . '</option>';
        }
        $sort_html .= '</select>';

        ob_start(); ?>
        <div class="owww-explorer owww-explorer--sliders" data-minmax>
            <form class="owww-explorer__filters" method="get" data-minmax-form>
                <label>Min. Rating
                    <input type="range" name="min_rating" min="0" max="5" step="1" value="<?php echo esc_attr($f['min_rating']); ?>" aria-label="Minimales Rating (0 bis 5)" />
                    <output data-out="min_rating"><?php echo esc_html($f['min_rating']); ?></output>
                </label>

                <label>Min. Exklusivität
                    <input type="range"
                        name="min_exclusivity"
                        min="0" max="5" step="1"
                        value="<?php echo esc_attr($f['min_excl'] ?? 0); ?>"
                        aria-label="Minimale Exklusivität (0 bis 5)">
                    <output data-out="min_exclusivity" >
                        <?php echo esc_html($f['min_excl'] ?? 0); ?>
                    </output>
                </label>


                <label>Schwierigkeit (von/bis)
                    <div class="minmax" data-field="owww_difficulty_hiking" data-min="1" data-max="6" data-step="1">
                        <input type="range" name="diff_from_i" min="1" max="6" step="1" value="<?php echo esc_attr($f['d_from_i']); ?>" aria-label="Schwierigkeit von (1=Wandern, 6=schwieriges Alpinwandern)" />
                        <input type="range" name="diff_to_i" min="1" max="6" step="1" value="<?php echo esc_attr($f['d_to_i']); ?>" aria-label="Schwierigkeit bis (1=Wandern, 6=schwieriges Alpinwandern)" />
                        <div class="track" aria-hidden="true">
                            <div class="range"></div>
                        </div>
                    </div>
                    <div class="minmax__labels">
                        <output data-out="diff_from_i"><?php echo esc_html(self::idxLabel($f['d_from_i'])); ?></output>
                        –
                        <output data-out="diff_to_i"><?php echo esc_html(self::idxLabel($f['d_to_i'])); ?></output>
                    </div>
                </label>

                <label>Dauer (von/bis)
                    <div class="minmax" data-field="duration" data-min="0" data-max="4000" data-step="10">
                        <input type="range" name="dur_from" min="0" max="4000" step="1" value="<?php echo esc_attr($f['dur_from']); ?>" aria-label="Dauer von (0 bis 4000 Minuten)" />
                        <input type="range" name="dur_to" min="0" max="4000" step="1" value="<?php echo esc_attr($f['dur_to']); ?>" aria-label="Dauer bis (0 bis 4000 Minuten)" />
                        <div class="track" aria-hidden="true">
                            <div class="range"></div>
                        </div>
                    </div>
                    <div class="minmax__labels">
                        <output data-out="dur_from"><?php echo esc_html(RenderUtils::duration_text($f['dur_from'])); ?></output>
                        –
                        <output data-out="dur_to"><?php echo esc_html(RenderUtils::duration_text($f['dur_to'])); ?></output>
                    </div>
                </label>

                <label>Sortierung
                    <?php echo $sort_html; ?>
                </label>

                <label>Kategorien (Regionen)
                    <select name="cats[]" multiple size="6"><?php echo $options; ?></select>
                </label>

                <button type="submit" class="owww-explorer__apply">Anwenden</button>
            </form>

            <div class="owww-explorer__results" aria-live="polite">
                <?php if ($q->have_posts()) : ?>
                    <table class="owww-table">
                        <thead>
                            <tr>
                                <th><a href="<?php echo $toggle('title'); ?>">Titel</a></th>
                                <th><a href="<?php echo $toggle('owww_rating'); ?>">Rating</a></th>
                                <th><a href="<?php echo $toggle('owww_exclusivity'); ?>">Exklusivität</a></th>
                                <th>Schwierigkeit</th>
                                <th><a href="<?php echo $toggle('dur'); ?>">Dauer</a></th>
                                <th><a href="<?php echo $toggle('date'); ?>">Datum</a></th>
                                <th>Kategorien</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($q->have_posts()) : $q->the_post();
                                $pid  = get_the_ID();
                                $title = get_the_title();
                                $perma = get_permalink();
                                $rating = (int)    get_post_meta($pid, Fields::rating(),     true);
                                $excl   = (int)    get_post_meta($pid, Fields::exclusivity(), true);
                                $dur    = (int)    get_post_meta($pid, Fields::duration(),    true);
                                $diffV  = (string) get_post_meta($pid, Fields::difficulty(),  true);
                                $cats_arr = get_the_category($pid);
                                $cats_txt = $cats_arr ? implode(', ', wp_list_pluck($cats_arr, 'name')) : '—';
                            ?>
                                <tr>
                                    <td><a href="<?php echo esc_url($perma); ?>"><?php echo esc_html($title); ?></a></td>
                                    <td><?php echo Html::stars($rating); ?></td>
                                    <td><?php echo $excl ? Html::iconGroup(Icons::sun(), $excl) : '—'; ?></td>
                                    <td><?php echo esc_html(Html::difficultyLabel($diffV)); ?></td>
                                    <td><?php echo esc_html(Html::durationText($dur)); ?></td>
                                    <td><?php echo esc_html(get_the_date()); ?></td>
                                    <td><?php echo esc_html($cats_txt); ?></td>
                                </tr>
                            <?php endwhile;
                            wp_reset_postdata(); ?>
                        </tbody>
                    </table>
                    <?php echo $pager_html; ?>
                <?php else: ?>
                    <div class="owww-empty">Keine Treffer.</div>
                <?php endif; ?>
            </div>
        </div>
<?php
        return ob_get_clean();
    }
}
