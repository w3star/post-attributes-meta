<?php

namespace OutdoorWww\Core;

class Lifecycle
{
    private const OPTION_VERSION = 'outdoor_www_version';
    private const MIN_PHP = '7.4';
    private const MIN_WP  = '5.8';


    /** Aktivierung des Plugins
     * - Mindestanforderungen prüfen (PHP, WP)
     * - Version setzen & evtl. Migrationen ausführen
     * - Rewrites (falls du später CPT/Rewrite nutzt)
     */
    public static function activate(): void
    {
        // 1) Mindestanforderungen prüfen
        if (version_compare(PHP_VERSION, self::MIN_PHP, '<')) {
            if (defined('OUTDOOR_WWW_FILE')) {
                deactivate_plugins(plugin_basename(OUTDOOR_WWW_FILE));
            }
            wp_die(
                sprintf(
                    'Dieses Plugin benötigt PHP %s oder höher. Aktuell: %s.',
                    self::MIN_PHP,
                    PHP_VERSION
                ),
                'Outdoor www – Aktivierung fehlgeschlagen',
                ['back_link' => true]
            );
        }

        global $wp_version;
        if (isset($wp_version) && version_compare($wp_version, self::MIN_WP, '<')) {
            deactivate_plugins(plugin_basename(dirname(__DIR__, 2) . '/outdoor-www.php'));
            wp_die(
                sprintf(
                    'Dieses Plugin benötigt WordPress %s oder höher. Aktuell: %s.',
                    self::MIN_WP,
                    $wp_version
                ),
                'Outdoor www – Aktivierung fehlgeschlagen',
                ['back_link' => true]
            );
        }

        // 2) Version setzen & evtl. Migrationen ausführen
        self::maybeMigrate();

        // 3) Rewrites (falls du später CPT/Rewrite nutzt)
        flush_rewrite_rules();
    }


    /** Deaktivierung des Plugins
     * - Nur das Nötigste, keine Daten löschen!
     */
    public static function deactivate(): void
    {
        // Hier nur, was beim Deaktivieren nötig ist (keine Daten löschen!)
        flush_rewrite_rules();
    }


    /** Deinstallation des Plugins
     * - Inhalte (Post-Meta) NICHT löschen.
     * - Plugin-Optionen entfernen
     */
    public static function uninstall(): void
    {
        // ❗ Inhalte (Post-Meta) NICHT löschen – das wolltest du behalten.
        // Sauber aufräumen: Plugin-Optionen entfernen.
        delete_option(self::OPTION_VERSION);

        // Wenn du irgendwann eigene Optionen anlegst:
        // delete_option('outdoor_www_settings');
    }


    /** Evtl. Migrationen ausführen, wenn die Plugin-Version sich geändert hat */
    private static function maybeMigrate(): void
    {
        $current  = defined('OUTDOOR_WWW_VERSION') ? OUTDOOR_WWW_VERSION : '0.0.0';
        $installed = get_option(self::OPTION_VERSION, '0.0.0');

        if ($installed === $current) {
            return; // alles aktuell
        }

        // Beispiel für spätere, versionierte Migrationen:
        // if (version_compare($installed, '1.2.0', '<')) {
        //     // Migration von <1.2.0 → 1.2.0 (z.B. neue Optionen initialisieren)
        // }

        update_option(self::OPTION_VERSION, $current);
    }
}
