<?php
namespace OutdoorWww\Blocks\Render;

use OutdoorWww\Explorer\Query as ExplorerQuery;
use OutdoorWww\Support\RenderUtils;

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
    { return $i===1?'Leicht':($i===2?'Mittel':'Schwer'); }

    public static function render(array $attributes, string $content, $block): string
    {
        wp_enqueue_style('pam-explorer-style');
        // view.js kommt über block.json::viewScript → keine doppelte Enqueue hier!

        $f = ExplorerQuery::readFilters();
        [$args, $meta_key, $paged] = ExplorerQuery::buildArgs($f);

        $q = new \WP_Query($args);

        $all_cats = get_categories(['hide_empty'=>false]);
        $options = '';
        foreach ($all_cats as $c) {
            $sel = in_array((int)$c->term_id, $f['cats'], true) ? ' selected' : '';
            $options .= '<option value="'.esc_attr($c->term_id).'"'.$sel.'>'.esc_html($c->name).'</option>';
        }

        // Sortier-Links toggeln
        $sort = $f['sort'];
        $toggle = function ($key) use ($sort) {
            $pairs = [
                'title'            => ['title_asc','title_desc'],
                'date'             => ['date_asc','date_desc'],
                'star_rating'      => ['rating_asc','rating_desc'],
                'star_exclusivity' => ['beauty_asc','beauty_desc'],
                'dur'              => ['dur_asc','dur_desc'],
            ];
            $pair = $pairs[$key] ?? ['date_asc','date_desc'];
            $next = ($sort === $pair[0]) ? $pair[1] : $pair[0];
            return esc_url(add_query_arg(['star_sort'=>$next], remove_query_arg(null)));
        };

        // Paginator
        $total_pages = max(1, (int)$q->max_num_pages);
        $pager_html = '';
        if ($total_pages>1) {
            $pager_html .= '<nav class="pam-pager" role="navigation" aria-label="Seitennummerierung"><ul>';
            for ($p=1; $p<=$total_pages; $p++) {
                $cls = $p===$paged ? ' class="is-active"' : '';
                $url = esc_url(self::build_url(['star_page'=>$p]));
                $pager_html .= "<li$cls><a href=\"$url\">$p</a></li>";
            }
            $pager_html .= '</ul></nav>';
        }

        // Sort-Auswahl
        $sort_options = [
            'date_desc'   => __('Neueste zuerst','outdoor-www'),
            'date_asc'    => __('Älteste zuerst','outdoor-www'),
            'rating_desc' => __('Rating absteigend','outdoor-www'),
            'rating_asc'  => __('Rating aufsteigend','outdoor-www'),
            'beauty_desc' => __('Exklusivität absteigend','outdoor-www'),
            'beauty_asc'  => __('Exklusivität aufsteigend','outdoor-www'),
            'dur_asc'     => __('Dauer: kürzeste zuerst','outdoor-www'),
            'dur_desc'    => __('Dauer: längste zuerst','outdoor-www'),
        ];
        $sort_html = '<select name="star_sort">';
        foreach ($sort_options as $value=>$label) {
            $sel = ($f['sort'] === $value) ? ' selected' : '';
            $sort_html .= '<option value="'.esc_attr($value).'"'.$sel.'>'.esc_html($label).'</option>';
        }
        $sort_html .= '</select>';

        ob_start(); ?>
        <div class="pam-explorer pam-explorer--sliders" data-minmax>
            <form class="pam-explorer__filters" method="get" data-minmax-form>
                <label>Min. Rating
                    <input type="range" name="min_rating" min="0" max="5" step="1" value="<?php echo esc_attr($f['min_rating']); ?>" aria-label="Minimales Rating (0 bis 5)" />
                    <output data-out="min_rating"><?php echo esc_html($f['min_rating']); ?></output>
                </label>

                <label>Min. Exklusivität
                    <input type="range" name="min_beauty" min="0" max="5" step="1" value="<?php echo esc_attr($f['min_beauty']); ?>" aria-label="Minimale Exklusivität (0 bis 5)" />
                    <output data-out="min_beauty"><?php echo esc_html($f['min_beauty']); ?></output>
                </label>

                <label>Schwierigkeit (von/bis)
                    <div class="minmax" data-field="star_difficulty" data-min="1" data-max="3" data-step="1">
                        <input type="range" name="diff_from_i" min="1" max="3" step="1" value="<?php echo esc_attr($f['d_from_i']); ?>" aria-label="Schwierigkeit von (1=Leicht, 3=Schwer)" />
                        <input type="range" name="diff_to_i"   min="1" max="3" step="1" value="<?php echo esc_attr($f['d_to_i']); ?>"   aria-label="Schwierigkeit bis (1=Leicht, 3=Schwer)" />
                        <div class="track" aria-hidden="true"><div class="range"></div></div>
                    </div>
                    <div class="minmax__labels">
                        <output data-out="diff_from_i"><?php echo esc_html(self::idxLabel($f['d_from_i'])); ?></output>
                        –
                        <output data-out="diff_to_i"><?php echo esc_html(self::idxLabel($f['d_to_i'])); ?></output>
                    </div>
                </label>

                <label>Dauer (von/bis)
                    <div class="minmax" data-field="duration" data-min="0" data-max="720" data-step="10">
                        <input type="range" name="dur_from" min="0" max="720" step="10" value="<?php echo esc_attr($f['dur_from']); ?>" aria-label="Dauer von (0 bis 720 Minuten)" />
                        <input type="range" name="dur_to"   min="0" max="720" step="10" value="<?php echo esc_attr($f['dur_to']); ?>"   aria-label="Dauer bis (0 bis 720 Minuten)" />
                        <div class="track" aria-hidden="true"><div class="range"></div></div>
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

                <button type="submit" class="pam-explorer__apply">Anwenden</button>
            </form>

            <div class="pam-explorer__results" aria-live="polite">
            <?php if ($q->have_posts()) : ?>
                <table class="pam-table">
                    <thead>
                        <tr>
                            <th><a href="<?php echo $toggle('title'); ?>">Titel</a></th>
                            <th><a href="<?php echo $toggle('star_rating'); ?>">Rating</a></th>
                            <th><a href="<?php echo $toggle('star_exclusivity'); ?>">Exklusivität</a></th>
                            <th>Schwierigkeit</th>
                            <th><a href="<?php echo $toggle('dur'); ?>">Dauer</a></th>
                            <th><a href="<?php echo $toggle('date'); ?>">Datum</a></th>
                            <th>Kategorien</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($q->have_posts()) : $q->the_post();
                            $pid  = get_the_ID();
                            $title= get_the_title();
                            $perma= get_permalink();
                            $rating = (int) get_post_meta($pid,'star_rating',true);
                            $beauty = (int) get_post_meta($pid,'star_exclusivity',true);
                            $dur    = (int) get_post_meta($pid,'star_time_relaxed',true);
                            $diff   = (string) get_post_meta($pid,'star_difficulty',true);
                            $diff_lbl = $diff==='hard'?'Schwer':($diff==='medium'?'Mittel':($diff==='easy'?'Leicht':'—'));
                            $cats_arr = get_the_category($pid);
                            $cats_txt = $cats_arr ? implode(', ', wp_list_pluck($cats_arr,'name')) : '—';
                        ?>
                        <tr>
                            <td><a href="<?php echo esc_url($perma); ?>"><?php echo esc_html($title); ?></a></td>
                            <td><?php echo RenderUtils::stars_html($rating); ?></td>
                            <td><?php echo $beauty ? RenderUtils::icons_group(RenderUtils::svg_sun(), $beauty) : '—'; ?></td>
                            <td><?php echo esc_html($diff_lbl); ?></td>
                            <td><?php echo esc_html(RenderUtils::duration_text($dur)); ?></td>
                            <td><?php echo esc_html(get_the_date()); ?></td>
                            <td><?php echo esc_html($cats_txt); ?></td>
                        </tr>
                        <?php endwhile; wp_reset_postdata(); ?>
                    </tbody>
                </table>
                <?php echo $pager_html; ?>
            <?php else: ?>
                <div class="pam-empty">Keine Treffer.</div>
            <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}
