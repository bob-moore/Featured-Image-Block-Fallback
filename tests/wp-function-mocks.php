<?php
/**
 * WordPress mock functions.
 */

if ( ! defined( 'WP_CONTENT_DIR' ) ) {
	define( 'WP_CONTENT_DIR', sys_get_temp_dir() . '/wp-content' );
}

if ( ! function_exists( 'plugin_basename' ) ) {
	function plugin_basename( $file = '' ) {
		if ( isset( $GLOBALS['wp_framework_plugin_basename'] ) ) {
			return (string) $GLOBALS['wp_framework_plugin_basename'];
		}

		return basename( (string) $file );
	}
}

if ( ! function_exists( 'plugin_dir_url' ) ) {
	function plugin_dir_url( $file = '' ) {
		if ( isset( $GLOBALS['wp_framework_plugin_dir_url'] ) ) {
			return (string) $GLOBALS['wp_framework_plugin_dir_url'];
		}

		$dir = trailingslashit( dirname( (string) $file ) );

		return 'https://example.test/' . ltrim( str_replace( DIRECTORY_SEPARATOR, '/', $dir ), '/' );
	}
}

if ( ! function_exists( 'is_admin' ) ) {
	function is_admin() {
		return (bool) ( $GLOBALS['wp_framework_is_admin'] ?? false );
	}
}

if ( ! function_exists( 'get_current_screen' ) ) {
	function get_current_screen() {
		return $GLOBALS['wp_framework_current_screen'] ?? null;
	}
}

if ( ! function_exists( 'plugin_dir_path' ) ) {
	function plugin_dir_path( $file ) {
		return trailingslashit( dirname( $file ) );
	}
}

if ( ! function_exists( 'esc_url_raw' ) ) {
	function esc_url_raw( $url ) {
		return $url;
	}
}

if ( ! function_exists( 'esc_html' ) ) {
	function esc_html( $text ) {
		return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'sanitize_key' ) ) {
	function sanitize_key( $key ) {
		return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $key ) );
	}
}

if ( ! function_exists( 'wp_normalize_path' ) ) {
	function wp_normalize_path( $path ) {
		return str_replace( '\\', '/', (string) $path );
	}
}

if ( ! function_exists( 'trailingslashit' ) ) {
	function trailingslashit( $string ) {
		return untrailingslashit( $string ) . '/';
	}
}

if ( ! function_exists( 'untrailingslashit' ) ) {
	function untrailingslashit( $value ) {
		return rtrim( $value, '/\\' );
	}
}

if ( ! function_exists( 'is_wp_error' ) ) {
	function is_wp_error( $thing ) {
		return ( $thing instanceof \WP_Error );
	}
}

if ( ! function_exists( 'wp_parse_args' ) ) {
	function wp_parse_args( $args, $defaults = [] ) {
		if ( is_object( $args ) ) {
			$r = get_object_vars( $args );
		} elseif ( is_array( $args ) ) {
			$r = &$args;
		} else {
			return $defaults;
		}

		if ( is_array( $defaults ) && $defaults ) {
			return array_merge( $defaults, $r );
		}

		return $r;
	}
}

if ( ! class_exists( 'WP_HTML_Tag_Processor' ) ) {
	/**
	 * Minimal WP_HTML_Tag_Processor for unit tests.
	 *
	 * Supports next_tag() with 'tag_name' and 'class_name' queries.
	 */
	class WP_HTML_Tag_Processor {
		private string $html;
		private string $matched_tag = '';
		private string $matched_tag_name = '';
		private array $attributes = [];

		public function __construct( string $html ) {
			$this->html = $html;
		}

		public function next_tag( array $query = [] ): bool {
			$tag_name   = isset( $query['tag_name'] ) ? strtolower( $query['tag_name'] ) : null;
			$class_name = $query['class_name'] ?? null;

			$offset  = 0;
			$pattern = '/<([a-z0-9-]+)(\s[^>]*)?\/?>/i';

			while ( preg_match( $pattern, $this->html, $matches, PREG_OFFSET_CAPTURE, $offset ) ) {
				$full_match       = $matches[0][0];
				$found_tag_name   = strtolower( $matches[1][0] );
				$attribute_string = $matches[2][0] ?? '';
				$offset           = $matches[0][1] + strlen( $full_match );

				if ( null !== $tag_name && $found_tag_name !== $tag_name ) {
					continue;
				}

				$this->matched_tag      = $full_match;
				$this->matched_tag_name = $found_tag_name;
				$this->attributes       = $this->parse_attributes( $attribute_string );

				if ( null !== $class_name ) {
					$classes = preg_split( '/\s+/', $this->attributes['class'] ?? '' );
					if ( ! in_array( $class_name, $classes, true ) ) {
						continue;
					}
				}

				return true;
			}

			return false;
		}

		public function get_attribute( string $name ): ?string {
			return $this->attributes[ $name ] ?? null;
		}

		public function set_attribute( string $name, string $value ): void {
			$this->attributes[ $name ] = $value;
		}

		public function get_updated_html(): string {
			$updated_tag = '<' . $this->matched_tag_name;

			foreach ( $this->attributes as $name => $value ) {
				$updated_tag .= sprintf( ' %s="%s"', $name, htmlspecialchars( $value, ENT_QUOTES, 'UTF-8' ) );
			}

			$updated_tag .= '>';

			return preg_replace( '/' . preg_quote( $this->matched_tag, '/' ) . '/', $updated_tag, $this->html, 1 ) ?? $this->html;
		}

		private function parse_attributes( string $attribute_html ): array {
			$attributes = [];
			preg_match_all( '/([a-z0-9_-]+)="([^"]*)"/i', $attribute_html, $matches, PREG_SET_ORDER );
			foreach ( $matches as $match ) {
				$attributes[ $match[1] ] = html_entity_decode( $match[2], ENT_QUOTES, 'UTF-8' );
			}
			return $attributes;
		}
	}
}

if ( ! class_exists( 'WP_Error' ) ) {
	class WP_Error {
		private string $code    = '';
		private string $message = '';
		private mixed $data     = '';

		public function __construct( $code = '', $message = '', $data = '' ) {
			$this->code    = $code;
			$this->message = $message;
			$this->data    = $data;
		}

		public function get_error_code() {
			return $this->code;
		}

		public function get_error_message() {
			return $this->message;
		}

		public function get_error_data() {
			return $this->data;
		}
	}
}

if ( ! class_exists( 'WP_Post' ) ) {
	class WP_Post {
		public int $ID           = 0;
		public string $post_content = '';

		public function __construct( array $data = [] ) {
			foreach ( $data as $key => $value ) {
				$this->$key = $value;
			}
		}
	}
}

if ( ! class_exists( 'WP_Block' ) ) {
	class WP_Block {
		/** @var array<string, mixed> */
		public array $context = [];

		/** @param array<string, mixed> $block */
		public function __construct( array $block = [] ) {
			$this->context = $block['context'] ?? [];
		}
	}
}
