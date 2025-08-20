<?php
/**
 * Plugin Name: Post Attributes Meta (Explorer + Summary)
 * Description: Zusatzinfos (Rating, Schwierigkeit, Schönheit, Dauer) + Gutenberg-Blöcke (Summary, Sterne, Explorer mit Filtern).
 * Version: 1.10.2
 * Author: w3star & ChatGPT
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class PostAttributesMeta {

    function __construct() {
        add_action( 'init', [ $this, 'register_meta' ] );
        add_action( 'enqueue_block_editor_assets', [ $this, 'editor_assets' ] );
        add_action( 'init', [ $this, 'register_blocks' ] );
    }

    // 🔹 1. Register Meta
    function register_meta() {
        $fields = [
            'rating'        => [ 'type' => 'integer', 'single' => true, 'default' => 0 ],
            'difficulty'    => [ 'type' => 'string',  'single' => true, 'default' => '' ],
            'beauty'        => [ 'type' => 'integer', 'single' => true, 'default' => 0 ],
            'duration_min'  => [ 'type' => 'integer', 'single' => true, 'default' => 0 ],
        ];

        foreach ( ['post','page'] as $ptype ) {
            foreach ( $fields as $key => $args ) {
                register_post_meta( $ptype, "pam_$key", array_merge( $args, [
                    'show_in_rest'      => true,
                    // Wenn du Sanitisierer hast, trag sie hier ein. Sonst NULL:
                    'sanitize_callback' => null,
                    // WICHTIG: korrekte Signatur => $post_id kommt garantiert an
                    'auth_callback'     => function( $allowed, $meta_key, $post_id /* , $user_id, $cap, $caps */ ) {
                        return current_user_can( 'edit_post', (int) $post_id );
                    },
                ]));
            }
        }
    }

    // 🔹 2. Editor Assets
    function editor_assets() {
        wp_enqueue_script(
            'pam-editor',
            plugins_url( 'blocks/editor.js', __FILE__ ),
            [ 'wp-blocks','wp-element','wp-components','wp-editor','wp-data' ],
            '1.10.2',
            true
        );
        wp_enqueue_style(
            'pam-editor-css',
            plugins_url( 'blocks/editor.css', __FILE__ ),
            [ 'wp-edit-blocks' ],
            '1.10.2'
        );
    }

    // 🔹 3. Register Blocks
    function register_blocks() {
        // Summary block
        register_block_type( __DIR__ . '/blocks/pam-summary' );
        // Stars block
        register_block_type( __DIR__ . '/blocks/pam-stars' );
        // Explorer block
        register_block_type( __DIR__ . '/blocks/pam-explorer', [
            'render_callback' => [ $this, 'render_block_explorer' ]
        ] );
    }

    // Helper Icons
    function svg_star(){ return '<svg width="16" height="16" viewBox="0 0 24 24" fill="black"><path d="M12 .587l3.668 7.568L24 9.75l-6 5.857 1.417 8.262L12 18.896l-7.417 4.973L6 15.607 0 9.75l8.332-1.595z"/></svg>'; }
    function svg_sun(){ return '<svg width="16" height="16" viewBox="0 0 24 24" fill="black"><circle cx="12" cy="12" r="5"/><path d="M12 1v2m0 18v2m11-11h-2M3 12H1m16.95-6.95l-1.414 1.414M6.464 17.536l-1.414 1.414m0-13.95l1.414 1.414M17.536 17.536l1.414 1.414"/></svg>'; }
    function svg_mountain(){ return '<svg width="16" height="16" viewBox="0 0 24 24" fill="black"><path d="M3 20h18L12 4z"/></svg>'; }
    function svg_clock(){ return '<svg width="16" height="16" viewBox="0 0 24 24" fill="black"><circle cx="12" cy="12" r="10" stroke="black" stroke-width="2" fill="none"/><path d="M12 6v6l4 2"/></svg>'; }

    function stars_html($n){ return str_repeat($this->svg_star(), $n); }
    function icons_group($svg,$n){ return str_repeat($svg, $n); }
    function duration_text($min){ return $min ? floor($min/60).'h '.($min%60).'m' : '—'; }

    // 🔹 4. Explorer Block Render
    function render_block_explorer($atts) {
        $sort = isset($_GET['pam_sort']) ? sanitize_text_field($_GET['pam_sort']) : 'date_desc';
        $view = isset($_GET['pam_view']) ? sanitize_text_field($_GET['pam_view']) : 'list';

        // Sort order
        switch($sort){
            case 'date_asc':  $args=['orderby'=>'date','order'=>'ASC']; break;
            case 'date_desc': $args=['orderby'=>'date','order'=>'DESC']; break;
            case 'title_asc': $args=['orderby'=>'title','order'=>'ASC']; break;
            case 'title_desc':$args=['orderby'=>'title','order'=>'DESC']; break;
            default: $args=['orderby'=>'date','order'=>'DESC'];
        }

        $q=new WP_Query(array_merge([
            'post_type'=>'post',
            'posts_per_page'=>10,
            'paged'=>max(1,get_query_var('paged')?:1),
        ],$args));

        ob_start();
        if($q->have_posts()):
            if($view==='list'): ?>
                <table class="pam-table">
                  <thead><tr>
                    <th><a href="?pam_sort=<?=$sort==='title_asc'?'title_desc':'title_asc'?>">Titel</a></th>
                    <th>Rating</th><th>Schönheit</th><th>Schwierigkeit</th><th>Dauer</th><th><a href="?pam_sort=<?=$sort==='date_asc'?'date_desc':'date_asc'?>">Datum</a></th>
                  </tr></thead><tbody>
                  <?php while($q->have_posts()):$q->the_post();
                    $id=get_the_ID();
                    $rating=(int)get_post_meta($id,'pam_rating',true);
                    $beauty=(int)get_post_meta($id,'pam_beauty',true);
                    $diff=get_post_meta($id,'pam_difficulty',true);
                    $dur=(int)get_post_meta($id,'pam_duration_min',true);
                  ?>
                    <tr>
                      <td><a href="<?php the_permalink();?>"><?php the_title();?></a></td>
                      <td><?=$this->stars_html($rating)?></td>
                      <td><?=$beauty?$this->icons_group($this->svg_sun(),$beauty):'—'?></td>
                      <td><?=$diff?:'—'?></td>
                      <td><?=$this->duration_text($dur)?></td>
                      <td><?=get_the_date()?></td>
                    </tr>
                  <?php endwhile; ?>
                  </tbody>
                </table>
            <?php else: ?>
                <div class="pam-cards">
                  <?php while($q->have_posts()):$q->the_post(); ?>
                    <div class="pam-card">
                      <h3><a href="<?php the_permalink();?>"><?php the_title();?></a></h3>
                    </div>
                  <?php endwhile; ?>
                </div>
            <?php endif;
        else:
            echo "<div class='pam-empty'>Keine Treffer.</div>";
        endif;
        wp_reset_postdata();
        return ob_get_clean();
    }
}

new PostAttributesMeta();
