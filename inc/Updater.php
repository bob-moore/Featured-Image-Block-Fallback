<?php
/**
 * Github Updater
 *
 * PHP Version 8.2
 *
 * @package featured-image-block-fallback
 * @author  Bob Moore <bob@bobmoore.dev>
 * @license GPL-2.0+ <http://www.gnu.org/licenses/gpl-2.0.txt>
 * @link    https://github.com/bob-moore/Featured-Image-Block-Fallback
 * @since   0.1.0
 */

namespace MarkedEffect\FeaturedImageBlockFallback;

class Updater
{
    /**
     * The plugin slug.
     *
     * @var string
     */
    const SLUG = 'featured-image-block-fallback';
    /**
     * The root file of the plugin.
     *
     * @var string
     */
    protected string $root_file;
    /**
     * The plugin directory.
     *
     * @var string
     */
    protected string $dir;
    /**
     * The plugin URL.
     *
     * @var string
     */
    protected string $url;
    /**
     * The plugin slug.
     *
     * @var string
     */
    protected string $plugin_slug;
    /**
     * The plugin name.
     *
     * @var string
     */
    protected string $plugin_name;
    /**
     * The plugin version.
     *
     * @var string
     */
    protected string $plugin_version;
    /**
     * The plugin update URI.
     *
     * @var string
     */
    protected string $plugin_update_uri;
    /**
     * The plugin icon.
     *
     * @var string
     */
    protected string $plugin_icon;
    /**
     * The plugin banner small.
     *
     * @var string
     */
    protected string $plugin_banner_small;
    /**
     * The plugin banner large.
     *
     * @var string
     */
    protected string $plugin_banner_large;
    /**
     * The api slug to check for updates.
     *
     * @var string
     */
    protected string $api_slug;

    public function __construct( string $root_file)
    {
        $this->root_file = $root_file;

        add_action( 'init', [ $this, 'init' ] );
        add_filter( 'plugins_api', [ $this, 'info' ], 20, 3 );
        add_filter( 'site_transient_update_plugins', [ $this, 'update' ] );
    }

    public function init(): void
    {
        $plugin_data = get_file_data(
            $this->root_file,
            [
                'PluginURI' => 'Plugin URI',
                'Version' => 'Version',
                'TestedUpTo' => 'Tested up to',
                'UpdateURI' => 'Update URI',
            ]
        );

        $this->dir = str_replace( ABSPATH, '', dirname( $this->root_file ) );
        $this->url = trailingslashit( trailingslashit( WP_SITEURL ) . $this->dir);

        // do_action( 'qm/debug', $plugin_data );

        $this->api_slug = untrailingslashit( 
            str_replace( 'https://github.com/', '', strtolower( $plugin_data['UpdateURI'] ) )
        );

        $this->plugin_version = $plugin_data['Version'];

        // do_action( 'qm/debug', basename( dirname( $this->root_file ) ) );
        // $response = wp_remote_get( 'https://api.github.com/repos/bob-moore/featured-image-block-fallback/releases' );


        // $response_body = wp_remote_retrieve_body( $response );
    }
    /**
     * Helper function to create URLs.
     *
     * @param string $path : The path to append to the URL.
     *
     * @return string
     */
    protected function url( string $path ): string
    {
        return $this->url . ltrim( $path, '/' );
    }
    /**
     * Helper function to create paths.
     *
     * @param string $path : The path to append to the directory.
     *
     * @return string
     */
    protected function path( string $path ): string
    {
        return $this->dir . ltrim( $path, '/' );
    }

    /**
     * Filters the plugins_api() response.
     *
     * @param false|object|array $result The result object or array. Default false.
     * @param string             $action The type of information being requested from the Plugin Installation API.
     * @param object             $args Plugin API arguments.
     *
     * @return false|object|array
     */
    public function info( false|object|array $result, string $action, object $args ): false|object|array
    {
        
        if ( 'plugin_information' !== $action ) {
            return $result;
        }

        if ( empty( $args->slug ) || self::SLUG !== $args->slug ) {
            return $result;
        }

        $data = $this->request();

        if ( ! $data ) {
            return $result;
        }

        $response          = new \stdClass();
        $response->slug    = self::SLUG;
        $response->plugin  = self::SLUG . '/' . self::SLUG . '.php';
        $response->version = $data->new_version;
        $response->name    = $data->name;
        $response->banners = [
            'low' => $this->url( 'assets/banner-772x250.jpg' ),
            'high' => $this->url( 'assets/banner-1544x500.jpg' ),
        ];
        $response->sections = (array) $data->sections;

        return $response;
    }
    /**
     * Request the update information from the API.
     *
     * @return object
     */
    protected function request(): object
    {
        $manifest = json_decode( file_get_contents( dirname( $this->root_file ) . '/manifest.json' ) );
        return $manifest;
    }
    /**
     * Filters the update transient.
     *
     * @param object $transient The transient object.
     *
     * @return object
     */
    public function update( $transient ) {

        if ( empty( $transient->checked ) ) {
            return $transient;
        }
        
        $manifest = $this->request();

        if ( ! $manifest ) {
            return $transient;
        }

        if ( 
            ! version_compare( $this->plugin_version, $manifest->new_version, '<' )
            || ! version_compare( $manifest->requires, get_bloginfo( 'version' ), '<=' )
            || ! version_compare( $manifest->requires_php, PHP_VERSION, '<' )
        ) {
            return $transient;
        }

        $response               = new \stdClass();
        $response->slug         = 'featured-image-block-fallback';
        $response->plugin       = self::SLUG . '/' . self::SLUG . '.php';
        $response->new_version  = $manifest->new_version;
        $response->tested       = $manifest->tested;
        $response->requires     = $manifest->requires;
        $response->requires_php = $manifest->requires_php;
        $response->package      = $manifest->package;
        $response->added        = $manifest->added;
        $response->last_updated = $manifest->last_updated;
        $response->icons = [
            'default' => $this->url( 'assets/icon.png' ),
        ];

        $transient->response[ $response->plugin ] = $response;

        return $transient;
    }
}