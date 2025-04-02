<?php
/**
 * Generate Update Manifest Data
 *
 * PHP Version 8.2
 *
 * @package featured-image-block-fallback
 * @author  Bob Moore <bob@bobmoore.dev>
 * @license GPL-2.0+ <http://www.gnu.org/licenses/gpl-2.0.txt>
 * @link    https://github.com/bob-moore/Featured-Image-Block-Fallback
 * @since   0.1.0
 */

namespace MarkedEffect\FeaturedImageBlockFallback\Dev;

use Composer;

class ManifestGenerator
{
    public function __construct( 
        string $root_file = '',
        string $zip_url = ''
    ) {


        $readme_file_content = file_get_contents( dirname( $root_file ) . '/readme.txt' );

        $readme = new ReadmeParser( $readme_file_content );

        $manifest = [
            'name' => $readme->getHeaderField( 'name' ),
            'new_version' => $readme->getHeaderField( 'stable_tag' ),
            'requires' => $readme->getHeaderField( 'requires_at_least' ),
            'tested' => $readme->getHeaderField( 'tested_up_to' ),
            'requires_php' => $readme->getHeaderField( 'requires_php' ),
            'added' => date('Y-m-d H:i:s'),
            'last_updated' => date('Y-m-d H:i:s'),
            'package' => $zip_url,
            'sections' => []
        ];

        foreach( $readme->getSections() as $section_name => $section_content ) {
            if ( 
                in_array( $section_name,
                    [
                        'description',
                        'installation',
                        'changelog',
                        'upgrade_notices'
                    ]
                ) && ! empty( $section_content )
            ) {
                $manifest['sections'][ $section_name ] = $section_content;
            }
        }

        file_put_contents( dirname( $root_file ) . '/manifest.json', json_encode( $manifest, JSON_PRETTY_PRINT ) );

    }
    public static function generate( Composer\Script\Event $event ) {

        $io = $event->getIO();

        $root_file = Composer\Factory::getComposerFile();

        new self( 
            $root_file,
            $io->ask( 'Download URL: ' ),
         );
    }
}