<?php

function customize_php_scoper_config( array $config = [] ): array {
	$config['exclude-namespaces'] = array_merge(
		$config['exclude-namespaces'] ?? [],
		[ 'Composer' ]
	);

	$config['patchers'] = array_merge(
		$config['patchers'] ?? [],
		[
			function ( string $filePath, string $prefix, string $content ): string {
				if ( strpos( $filePath, 'ObjectCreationCompiler.php' ) === false ) {
					return $content;
				}

				// PHP-Scoper rewrites namespace tokens but cannot see inside sprintf() string
				// literals that emit PHP code. This literal hard-codes the ObjectCreator FQCN
				// for the private-property-injection branch of the compiled container cache.
				return str_replace(
					[
						"'\\DI\\Definition\\Resolver\\ObjectCreator::",
						"'\\\\DI\\\\Definition\\\\Resolver\\\\ObjectCreator::",
					],
					[
						"'\\{$prefix}\\DI\\Definition\\Resolver\\ObjectCreator::",
						"'\\\\" . str_replace( '\\', '\\\\', $prefix ) . "\\\\DI\\\\Definition\\\\Resolver\\\\ObjectCreator::",
					],
					$content
				);
			},
		]
	);

	return $config;
}
