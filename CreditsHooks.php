<?php

use MediaWiki\Block\DatabaseBlock;
use MediaWiki\MediaWikiServices;
use Wikimedia\Rdbms\IDatabase;

class CreditsHooks {

	/**
	 * Setups the mediawiki hook
	 *
	 * @param Parser $parser
	 */
	public static function onParserFirstCallInit( $parser ) {
		$parser->setHook( 'credits', [ __CLASS__, 'onCreditsTag' ] );
	}

	/**
	 * Callback that replaces <credits /> wiki tag with a list of article contributors
	 *
	 * Available options:
	 *
	 * * separator string, to control the separator between contributors, defaults to ", "
	 *
	 * Sample Usage:
	 *
	 * <code>
	 * <credits separator="; " />
	 * </code>
	 *
	 * Gets the list of contributors with semicolon as separator
	 *
	 * @param string|null $text
	 * @param array $params Additional parameters passed as attributes to credits tag
	 * @param Parser $parser The Wiki Parser Object
	 * @return string
	 */
	public static function onCreditsTag( $text, array $params, Parser $parser ) {
		// Get parameters
		$separator = isset( $params['separator'] ) ? $params['separator'] : ', ';
		// Get current article title
		$title = $parser->getTitle();
		// Get article contributors
		$contributors = self::getArticleContributors( $title );
		// Build output
		$output = '<div class="credits">';
        $output .= '';
        foreach ($contributors as $contributor) {
            $userTitle = Title::makeTitle( NS_USER, $contributor );
            if ( $userTitle && $userTitle->exists() ) {
                $contributorLink = $userTitle->getFullUrl();
                $output .= "<a href=\"$contributorLink\">$contributor</a>$separator";
            } else {
                $output .= "$contributor$separator";
            }
        }
        $output = rtrim($output, $separator); // remove the trailing separator
        $output .= '</div>';
        // Return output
        return $output;
}

	/**
 * Returns an array of article contributors
 *
 * @param Title $title The article title
 * @return array An array of article contributors
 */
private static function getArticleContributors( $title ) {
    $dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnectionRef( DB_REPLICA );
$revs = $dbr->select(
    [ 'page', 'revision', 'actor' ],
    [ 'DISTINCT actor_name' ],
    [
        'rev_page = page_id',
        'actor_id = rev_actor',
        'page_title' => $title->getDBkey(),
        'page_namespace' => $title->getNamespace(),
        'rev_actor <> 0',
        'actor_user IS NOT NULL',
        'actor_name NOT LIKE "HindupediaSysop"', // exclude contributions made by IP address 
        'actor_name NOT IN ("Bilahari Akkiraju", "Redirect fixer")' 
    ],
    __METHOD__,
    [ 'ORDER BY' => 'rev_timestamp DESC' ],
    [ 'revision' => [ 'INNER JOIN', 'rev_actor = actor_id' ] ]
);

$contributors = [];
foreach ( $revs as $rev ) {
    $contributors[] = $rev->actor_name;
}
return $contributors;

}
}
