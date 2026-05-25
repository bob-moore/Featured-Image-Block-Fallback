<?php
/**
 * Application entry point — builds the DI container and mounts all services.
 *
 * @package Bmd\FeaturedImageBlockFallback
 * @author  Bob Moore <bob@bobmoore.dev>
 * @license GPL-2.0-or-later https://www.gnu.org/licenses/gpl-2.0.html
 * @link    https://github.com/bob-moore/Featured-Image-Block-Fallback
 */

namespace Bmd\FeaturedImageBlockFallback;

/**
 * Owns the DI container, builds it once, then delegates hook registration
 * to Controller via PHP-DI method injection.
 */
class Main
{
	/**
	 * Default compiled container class name.
	 */
	protected const DEFAULT_CONTAINER_CLASS = 'FeaturedImageBlockFallbackContainer';

	/**
	 * Shared service container, built once per request.
	 *
	 * @var \DI\Container|null
	 */
	protected static ?\DI\Container $services = null;

	/**
	 * Constructor.
	 *
	 * @param array<string, mixed> $config Configuration overrides.
	 */
	public function __construct( protected array $config = [] )
	{
		$this->setConfig( $config );
	}

	/**
	 * Merge provided config with defaults.
	 *
	 * @param array<string, mixed> $config Configuration overrides.
	 *
	 * @return void
	 */
	public function setConfig( array $config ): void
	{
		$this->config = array_merge(
			[
				'package'         => 'featured_image_block_fallback',
				'path'            => Utilities::getPath(),
				'url'             => Utilities::getUrl(),
				'cache'           => 'production' === wp_get_environment_type(),
				'cache_dir'       => dirname( __DIR__ ) . '/cache',
				'container_class' => self::DEFAULT_CONTAINER_CLASS,
			],
			$config
		);
	}

	/**
	 * Determine whether the compiled container can be used or generated.
	 *
	 * @param string $cache_dir Cache directory.
	 * @param string $class     Compiled container class name.
	 *
	 * @return bool
	 */
	protected function canUseCompiledContainer( string $cache_dir, string $class ): bool
	{
		$compiled_file = trailingslashit( $cache_dir ) . "{$class}.php";

		if ( is_readable( $compiled_file ) ) {
			return true;
		}

		return is_dir( $cache_dir )
			? is_writable( $cache_dir )
			: wp_mkdir_p( $cache_dir ) && is_writable( $cache_dir );
	}

	/**
	 * Build the DI container.
	 *
	 * @return void
	 */
	protected function initContainer(): void
	{
		$builder = new \DI\ContainerBuilder();
		$builder->useAttributes( true );

		if ( $this->config['cache'] ) {
			$cache_dir       = (string) $this->config['cache_dir'];
			$container_class = (string) $this->config['container_class'];

			if ( $this->canUseCompiledContainer( $cache_dir, $container_class ) ) {
				$builder->enableCompilation( $cache_dir, $container_class );
			}
		}

		$builder->addDefinitions( __DIR__ . '/definitions.php' );
		$builder->addDefinitions( [ 'package' => $this->config['package'] ] );

		self::$services = $builder->build();

		// Late setup of dynamic properties.
		self::$services->set( 'path', $this->config['path'] );
		self::$services->set( 'url', $this->config['url'] );
	}

	/**
	 * Initialize the container (if needed) then mount the controller.
	 *
	 * @return void
	 */
	public function mount(): void
	{
		if ( ! self::$services instanceof \DI\Container ) {
			$this->initContainer();
		}

		self::$services->get( Controller::class );

		do_action( "{$this->config['package']}_loaded" );
	}

	/**
	 * Set or replace a service in the built container.
	 *
	 * @param string $key   Service entry key.
	 * @param mixed  $value Service instance or value.
	 *
	 * @return void
	 * @throws \LogicException When the container has not been built.
	 */
	public static function setInstance( string $key, mixed $value ): void
	{
		if ( ! self::$services instanceof \DI\Container ) {
			throw new \LogicException( 'Cannot set service before container is built.' );
		}

		self::$services->set( $key, $value );
	}

	/**
	 * Get a service instance from the container.
	 *
	 * @param string $service Fully-qualified class name or container entry key.
	 *
	 * @return object|null The service, or null if the container is not yet built.
	 */
	public static function getInstance( string $service ): ?object
	{
		return self::$services instanceof \DI\Container && self::$services->has( $service )
			? self::$services->get( $service )
			: null;
	}
}
