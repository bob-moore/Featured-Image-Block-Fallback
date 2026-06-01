<?php

function customize_php_scoper_config( array $config = [] ): array {

	$config['patchers'] = array_merge(
		$config['patchers'] ?? [],
		[
			function ( string $filePath, string $prefix, string $content ): string {
				if ( strpos( $filePath, '/Compiler/' ) === false ) {
					return $content;
				}

				// PHP-Scoper rewrites namespace tokens but cannot see inside string literals
				// that PHP-DI uses to emit compiled-container code.
				return str_replace(
					[
						"return \\DI\\Definition\\StringDefinition::resolveExpression(",
						"throw new \\DI\\Definition\\Exception\\InvalidDefinition(",
						"'\\DI\\Definition\\Resolver\\ObjectCreator::",
						"'\\\\DI\\\\Definition\\\\Resolver\\\\ObjectCreator::",
					],
					[
						"return \\{$prefix}\\DI\\Definition\\StringDefinition::resolveExpression(",
						"throw new \\{$prefix}\\DI\\Definition\\Exception\\InvalidDefinition(",
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
