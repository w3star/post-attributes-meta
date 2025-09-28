<?php

namespace OutdoorWww\Meta;

/**
 * Einheitliche Meta-Registrierung + Sanitizer
 * - kapselt register_post_meta
 * - clamp/validiert Werte zentral
 */
class Registrar
{
    /** @var array<string,array> Meta-Definitionen (deine Outdoor_www::META) */
    private array $defs;

    /** @var string[] Post Types */
    private array $postTypes;


    /**
     * @param array<string,array> $defs Meta-Definitionen (deine Outdoor_www::META)
     * @param string[] $postTypes Post Types (default: ['post','page'])
     */
    public function __construct(array $defs, array $postTypes = ['post', 'page'])
    {
        $this->defs = $defs;
        $this->postTypes = $postTypes;

        // auf init registrieren
        add_action('init', [$this, 'register']);
    }


    /** Registriere alle Metas */
    public function register(): void
    {
        foreach ($this->postTypes as $ptype) {
            foreach ($this->defs as $key => $args) {
                $type    = $args['type']   ?? 'string';
                $single  = $args['single'] ?? true;
                $default = $args['default'] ?? null;

                register_post_meta($ptype, $key, [
                    'type'              => $type,
                    'single'            => $single,
                    'default'           => $default,
                    'show_in_rest'      => true, // Gutenberg/REST
                    'sanitize_callback' => function ($value) use ($key, $type) {
                        return self::sanitizeForKey($key, $type, $value);
                    },
                    'auth_callback'     => function ($allowed, $meta_key, $post_id) {
                        return current_user_can('edit_post', (int)$post_id);
                    },
                ]);
            }
        }
    }


    
    /** ---------------- Sanitizer: zentral & wiederverwendbar ---------------- */
    public static function sanitizeForKey(string $key, string $type, $value)
    {
        // Grundsanitizer nach Typ
        if ($type === 'integer') {
            $value = is_numeric($value) ? (int)$value : 0;
        } else {
            $value = is_scalar($value) ? sanitize_text_field((string)$value) : '';
        }

        // Feinschliff per Schlüssel
        switch ($key) {
            case 'owww_rating':        // 0..5
            case 'owww_exclusivity':   // 0..5
                return max(0, min(5, (int)$value));

            case 'owww_time_relaxed':
            case 'owww_time_steady':
            case 'owww_time_moderate':
            case 'owww_time_fast':
            case 'owww_time_veryfast':
                // Minuten >= 0, optional harte Obergrenze (hier 10080 = 7 Tage)
                $v = (int)$value;
                return $v < 0 ? 0 : min($v, 10080);

            case 'owww_difficulty_hiking':
                $v = (string)$value;
                $allowed = ['', 'T1', 'T2', 'T3', 'T4', 'T5', 'T6'];
                return in_array($v, $allowed, true) ? $v : '';

            default:
                return $value;
        }
    }
}
