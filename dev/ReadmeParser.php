<?php
/**
 * Parser to use readme.txt files
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

class ReadmeParser
{
    /**
     * @var string The raw readme content
     */
    private string $content;
    
    /**
     * @var array The parsed header metadata
     */
    private array $header = [];
    
    /**
     * @var array The parsed sections
     */
    private array $sections = [];
    
    /**
     * Constructor
     *
     * @param string $content The readme.txt content to parse
     */
    public function __construct( string $content )
    {
        $this->content = $content;
        $this->parse();
    }

    protected function parseHeaders(): void
    {
        // Extract the header (content between === and ==)
         if ( preg_match( '/===(.+?)===\s*\n(.*?)(?=\n==\s|\z)/s', 
            $this->content, 
            $matches
        ) ) {
            $name = trim( $matches[1] );
            $header_content = trim( $matches[2] );
            $short_description = '';

            /**
             * Extract all header fields
             */
            $lines = explode("\n", $header_content );
            
            foreach ( $lines as $line ) {
                $line = trim( $line );

                if ( empty( $line ) ) {
                    continue;
                }

                /**
                 * Check if this is a header field
                 */
                if ( preg_match('/^([^:]+?):\s*(.+)$/', $line, $header_matches ) ) {
                    $key = trim( $header_matches[1] );
                    $value = trim( $header_matches[2] );
                    $this->header[ strtolower( str_replace( ' ', '_', $key ) ) ] = $value;
                } 
                /**
                 * If not a header field, it's part of the short description
                 */
                else {
                    if ( ! empty( $short_description ) ) {
                        $short_description .= "\n";
                    }
                    $short_description .= $line;
                }
            }

            $this->header['name'] = $name;
            $this->header['short_description'] = $short_description;
        }
    }
    /**
     * Parse the readme.txt content
     *
     * @return void
     */
    private function parse(): void
    {
        $this->parseHeaders();

        $markdown_parser = new \Parsedown();
        
        if ( preg_match_all(
            '/==\s*([^=]+?)\s*==\s*\n(.*?)(?=\n==\s|\z)/s',
            $this->content,
            $matches,
            PREG_SET_ORDER
        )) {
            foreach ( $matches as $match ) {
                $this->sections[ strtolower( trim( $match[1] ) ) ] = $markdown_parser->text( trim( $match[2] ) );
            }
        }
    }
    
    /**
     * Get the parsed header metadata
     *
     * @return array
     */
    public function getHeader(): array
    {
        return $this->header;
    }
    
    /**
     * Get a specific header field
     *
     * @param string $field The header field to retrieve
     * @param mixed $default Default value if field doesn't exist
     * @return mixed
     */
    public function getHeaderField(string $field, string $default = ''): string
    {
        return $this->header[ $field ] ?? $default;
    }
    
    /**
     * Get all parsed sections
     *
     * @return array
     */
    public function getSections(): array
    {
        return $this->sections;
    }
    
    /**
     * Get a specific section by name
     *
     * @param string $name The section name to retrieve.
     * @param mixed  $default Default value if section doesn't exist.
     * @return string
     */
    public function getSection( string $name, $default = '' ): string
    {
        return $this->sections[$name] ?? $default;
    }
}