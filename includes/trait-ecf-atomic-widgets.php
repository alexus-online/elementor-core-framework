<?php
/**
 * Registration of Layrix-provided Elementor v4 Atomic Widgets.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

trait ECF_Framework_Atomic_Widgets_Trait {

    public function register_atomic_widgets( $elements_manager ) {
        $base_class = '\Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base';
        if ( ! class_exists( $base_class ) && defined( 'WP_PLUGIN_DIR' ) ) {
            // Newer Elementor versions live under elements/base/. Older versions
            // had the base files directly under elements/ — try both.
            $candidates = [
                WP_PLUGIN_DIR . '/elementor/modules/atomic-widgets/elements/base/atomic-element-base.php',
                WP_PLUGIN_DIR . '/elementor/modules/atomic-widgets/elements/atomic-element-base.php',
            ];
            foreach ( $candidates as $candidate ) {
                if ( file_exists( $candidate ) ) {
                    require_once $candidate;
                    break;
                }
            }
        }
        if ( ! class_exists( $base_class ) ) {
            return;
        }
        if ( ! class_exists( 'ECF_Atomic_Section' ) ) {
            $widget_file = __DIR__ . '/class-ecf-atomic-section.php';
            if ( file_exists( $widget_file ) ) {
                require_once $widget_file;
            }
        }
        if ( class_exists( 'ECF_Atomic_Section' ) ) {
            try {
                $elements_manager->register_element_type( new ECF_Atomic_Section() );
            } catch ( \Throwable $e ) {
                // Silently skip — most likely the widget is already registered.
            }
        }
    }
}
