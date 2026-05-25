<?php
/**
 * PHP-DI service definitions.
 *
 * @package Bmd\FeaturedImageBlockFallback
 * @author  Bob Moore <bob@bobmoore.dev>
 * @license GPL-2.0-or-later https://www.gnu.org/licenses/gpl-2.0.html
 * @link    https://github.com/bob-moore/Featured-Image-Block-Fallback
 */

namespace Bmd\FeaturedImageBlockFallback;

return [
	Controller::class                => \DI\autowire(),
	Services\FilePathResolver::class => \DI\autowire(),
	Services\UrlResolver::class      => \DI\autowire(),
	Services\ScriptLoader::class     => \DI\autowire(),
	Services\StyleLoader::class      => \DI\autowire(),
	Providers\Assets::class          => \DI\autowire(),
	Transformers\Fallback::class     => \DI\autowire(),
];
