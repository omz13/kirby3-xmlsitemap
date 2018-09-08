<?php

namespace omz13;

use Kirby\Cms\Page;
use Kirby\Cms\Pages;
use Kirby\Exception\LogicException;

use const CONFIGURATION_PREFIX;
use const DATE_ATOM;
use const XMLSITEMAP_VERSION;

use function array_key_exists;
use function date;
use function define;
use function file_exists;
use function file_get_contents;
use function filemtime;
use function in_array;
use function is_array;
use function json_encode;
use function kirby;
use function md5;
use function microtime;
use function strtotime;
use function time;

define( 'XMLSITEMAP_VERSION', '0.4.3' );
define( 'CONFIGURATION_PREFIX', 'omz13.xmlsitemap' );

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class XMLSitemap
{
  private static $debug;
  private static $optionCACHE; // cache TTL in *minutes*; if zero or null, no cache
  private static $optionNOIMG; // disable including image data
  private static $optionIUWSI; // include unlisted when slug is
  private static $optionXCWTI; // exclude children when template is
  private static $optionXPWTI; // exclude page when template is
  private static $optionXPWSI; // exclude page when slug is
  public static $version = XMLSITEMAP_VERSION;

  public static function ping() : string {
    return static::class . ' pong ' . static::$version;
  }//end ping()

  public static function isEnabled() : bool {
    if ( self::getConfigurationForKey( 'disable' ) == 'true' ) {
      return false;
    }

    if ( kirby()->site()->content()->get( 'xmlsitemap' ) == 'false' ) {
      return false;
    }

    return true;
  }//end isEnabled()

  public static function getArrayConfigurationForKey( string $key ) : ?array {
    // Try to pick up configuration when provided in an array (vendor.plugin.array(key=>value))
    $o = kirby()->option( CONFIGURATION_PREFIX );
    if ( $o != null && is_array( $o ) && array_key_exists( $key, $o ) ) {
      return $o[$key];
    }

    // try to pick up configuration as a discrete (vendor.plugin.key=>value)
    $o = kirby()->option( CONFIGURATION_PREFIX . '.' . $key );
    if ( $o != null ) {
      return $o;
    }

    // this should not be reached... because plugin should define defaults for all its options...
    return null;
  }//end getArrayConfigurationForKey()

  public static function getConfigurationForKey( string $key ) : string {
    // Try to pick up configuration when provided in an array (vendor.plugin.array(key=>value))
    $o = kirby()->option( CONFIGURATION_PREFIX );
    if ( $o != null && is_array( $o ) && array_key_exists( $key, $o ) ) {
      return $o[$key];
    }

    // try to pick up configuration as a discrete (vendor.plugin.key=>value)
    $o = kirby()->option( CONFIGURATION_PREFIX . '.' . $key );
    if ( $o != null ) {
      return $o;
    }

    // this should not be reached... because plugin should define defaults for all its options...
    return "";
  }//end getConfigurationForKey()

  public static function getStylesheet() : string {
    $f = file_get_contents( __DIR__ . '/../assets/xmlsitemap.xsl' );
    if ( $f == null ) {
      throw new LogicException( 'Failed to read sitemap.xsl' );
    }

    return $f;
  }//end getStylesheet()

  private static function pickupOptions() : void {
    static::$optionCACHE = static::getConfigurationForKey( 'cacheTTL' );
    static::$optionNOIMG = static::getConfigurationForKey( 'disableImages' );
    static::$optionIUWSI = static::getArrayConfigurationForKey( 'includeUnlistedWhenSlugIs' );
    static::$optionXCWTI = static::getArrayConfigurationForKey( 'excludeChildrenWhenTemplateIs' );
    static::$optionXPWTI = static::getArrayConfigurationForKey( 'excludePageWhenTemplateIs' );
    static::$optionXPWSI = static::getArrayConfigurationForKey( 'excludePageWhenSlugIs' );
  }//end pickupOptions()

  /**
   * @SuppressWarnings("Complexity")
   */
  public static function getSitemap( Pages $p, bool $debug = false ) : string {
    static::$debug = $debug && kirby()->option( 'debug' ) !== null && kirby()->option( 'debug' ) == true;
    static::pickupOptions();

    $tbeg = microtime( true );

    // if cacheTTL disabled...
    if ( empty( static::$optionCACHE ) ) {
      $r = static::generateSitemap( $p, $debug );
      if ( static::$debug == true ) {
        $r .= "<!-- Freshly generated; not cached for reuse -->\n";
      }
    } else {
      // try to read from cache; generate if expired
      $cacheCache = kirby()->cache( 'omz13.xmlsitemap' );

      // build list of options
      $ops  = json_encode( static::$optionCACHE );
      $ops .= '-' . json_encode( static::$optionNOIMG );
      $ops .= '-' . json_encode( static::$optionIUWSI );
      $ops .= '-' . json_encode( static::$optionXCWTI );
      $ops .= '-' . json_encode( static::$optionXPWSI );
      $ops .= '-' . json_encode( static::$optionXPWTI );
      $ops .= '-' . json_encode( $debug );

      $cacheName = XMLSITEMAP_VERSION . '-sitemap-' . md5( $ops );

      $r = $cacheCache->get( $cacheName );
      if ( $r == null ) {
        $r = static::generateSitemap( $p, $debug );
        $cacheCache->set( $cacheName, $r, static::$optionCACHE );
        if ( static::$debug == true ) {
          $r .= '<!-- Freshly generated; cached into ' . md5( $ops ) . ' for ' . static::$optionCACHE . " minute(s) for reuse -->\n";
        }
      } else {
        if ( static::$debug == true ) {
          $expiresAt       = $cacheCache->expires( $cacheName );
          $secondsToExpire = ( $expiresAt - time() );

          $r .= '<!-- Retrieved as ' . md5( $ops ) . ' from cache ; expires in ' . $secondsToExpire . " seconds -->\n";
        }
      }
    }//end if

    $tend = microtime( true );
    if ( static::$debug == true ) {
      $elapsed = ( $tend - $tbeg );
      $r      .= '<!-- That all took ' . ( 1000 * $elapsed ) . " microseconds -->\n";
    }

    return $r;
  }//end getSitemap()

  private static function generateSitemap( Pages $p, bool $debug = false ) : string {
    static::pickupOptions();
    $tbeg = microtime( true );

    $r  = '';
    $r .= "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
    $r .= "<?xml-stylesheet type=\"text/xsl\" href=\"/sitemap.xsl\"?>\n";
    $r .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" ';

    if ( static::$optionNOIMG != true ) {
      $r .= ' xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"';
    }

    $r .= ">\n";

    if ( $debug == true ) {
      $r .= '<!--                 disableImages = ' . json_encode( static::$optionNOIMG ) . " -->\n";
      $r .= '<!--     includeUnlistedWhenSlugIs = ' . json_encode( static::$optionIUWSI ) . " -->\n";
      $r .= '<!-- excludeChildrenWhenTemplateIs = ' . json_encode( static::$optionXCWTI ) . " -->\n";
      $r .= '<!--     excludePageWhenTemplateIs = ' . json_encode( static::$optionXPWTI ) . " -->\n";
      $r .= '<!--         excludePageWhenSlugIs = ' . json_encode( static::$optionXPWSI ) . " -->\n";
    }

    static::addPagesToSitemap( $p, $r );
    $r .= "</urlset>\n";
    $r .= "<!-- Sitemap generated using https://github.com/omz13/kirby3-xmlsitemap -->\n";

    $tend = microtime( true );
    if ( $debug == true ) {
      $elapsed = ( $tend - $tbeg );

      $r .= '<!-- v' . static::$version . " -->\n";
      $r .= '<!-- Generation took ' . ( 1000 * $elapsed ) . " microseconds -->\n";
      $r .= '<!-- Generated at ' . date( DATE_ATOM, (int) $tend ) . " -->\n";
    }

    return $r;
  }//end generateSitemap()

  /**
  * @SuppressWarnings(PHPMD.CyclomaticComplexity)
  * @SuppressWarnings(PHPMD.NPathComplexity)
  * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
   */
  private static function addPagesToSitemap( Pages $pages, string &$r ) : void {
    $sortedpages = $pages->sortBy( 'url', 'asc' );
    foreach ( $sortedpages as $p ) {
      static::addComment( $r, 'crunching ' . $p->url() . ' [it=' . $p->intendedTemplate() . '] [s=' . $p->status() . '] [d=' . $p->depth() . ']' );

      // don't include the error page
      if ( $p->isErrorPage() ) {
        continue;
      }

      if ( $p->status() == 'unlisted' && ! $p->isHomePage() ) {
        if ( isset( static::$optionIUWSI ) && in_array( $p->slug(), static::$optionIUWSI ) ) {
          static::addComment( $r, 'including ' . $p->url() . ' because unlisted but in includeUnlistedWhenSlugIs' );
        } else {
          static::addComment( $r, 'excluding ' . $p->url() . ' because unlisted' );
          continue;
        }
      }

      // exclude because template used is in the exclusion list:
      if ( isset( static::$optionXPWTI ) && in_array( $p->intendedTemplate(), static::$optionXPWTI ) ) {
        static::addComment( $r, 'excluding ' . $p->url() . ' because excludePageWhenTemplateIs (' . $p->intendedTemplate() . ')' );
        continue;
      }

      // exclude because slug is in the exclusion list:
      if ( isset( static::$optionXPWSI ) && in_array( $p->slug(), static::$optionXPWSI ) ) {
        static::addComment( $r, 'excluding ' . $p->url() . ' because excludePageWhenSlugIs (' . $p->slug() . ')' );
        continue;
      }

      // exclude because page content field 'excludefromxmlsitemap':
      if ( $p->content()->excludefromxmlsitemap() == 'true' ) {
        static::addComment( $r, 'excluding ' . $p->url() . ' because excludeFromXMLSitemap' );
        continue;
      }

      // exclude because, if supported, the page is sunset:
      if ( $p->hasMethod( 'issunset' ) ) {
        if ( $p->issunset() ) {
          static::addComment( $r, 'excluding ' . $p->url() . ' because isSunset' );
          continue;
        }
      }

      // exclude because, if supported,  the page is under embargo
      if ( $p->hasMethod( 'isunderembargo' ) ) {
        if ( $p->isunderembargo() ) {
          static::addComment( $r, 'excluding ' . $p->url() . ' because isUnderembargo' );
          continue;
        }
      }

      // <loc>https://www.example.com/slug</loc>

      $r .= "<url>\n";
      $r .= '  <loc>' . $p->url() . // ($p->isHomePage() ? "/" : "") .

      "</loc>\n";

      // priority for determining the last modified date: updatedat, then date, then filestamp
      $lastmod = 0; // default to unix epoch (jan-1-1970)
      if ( $p->content()->has( 'updatedat' ) ) {
        $t       = $p->content()->get( 'updatedat' );
        $lastmod = strtotime( $t );
      } else {
        if ( $p->content()->has( 'date' ) ) {
          $t       = $p->content()->get( 'date' );
          $lastmod = strtotime( $t );
        } else {
          if ( file_exists( $p->contentFile() ) ) {
            $lastmod = filemtime( $p->contentFile() );
          }
        }
      }//end if

      // phpstan picked up that Parameter #2 $timestamp of function date expects int, int|false given.
      // this might happen if strtotime or filemtime above fails.
      // so a big thumbs-up to phpstan.
      if ( $lastmod == false ) {
        $lastmod = 0;
      }

      // set modified date to be last date vis-a-vis when file modified /content embargo time / content date
      $r .= '  <lastmod>' . date( 'c', $lastmod ) . "</lastmod>\n";

      /*
          Don't bother with priority - we ignore those. It's essentially a bag of noise" - [ref https://twitter.com/methode/status/846796737750712320]
          if ($p->depth()==1)
          $r.="  <priority>". ($p->isHomePage() ? "1.0" : "0.9") . "</priority>\n";
          if ($p->depth()>=2)
          $r.="  <priority>0.8</priority>\n";
      */

      if ( static::$optionNOIMG != true ) {
        static::addImagesFromPageToSitemap( $p, $r );
      }

      if ( $p->children() !== null ) {
        // jump into the children, unless the current page's template is in the exclude-its-children set
        if ( isset( static::$optionXCWTI ) && in_array( $p->intendedTemplate(), static::$optionXCWTI ) ) {
          static::addComment( $r, 'ignoring children of ' . $p->url() . ' because excludeChildrenWhenTemplateIs (' . $p->intendedTemplate() . ')' );
          if ( static::$optionNOIMG != true ) {
            static::addImagesToSitemap( $p->children(), $r );
          }

          $r .= "</url>\n";
        } else {
          $r .= "</url>\n";
          static::addPagesToSitemap( $p->children(), $r );
        }
      } else {
        $r .= "</url>\n";
      }//end if
    }//end foreach
  }//end addPagesToSitemap()

  private static function addComment( string &$r, string $m ) : void {
    if ( static::$debug == true ) {
      $r .= '<!-- ' . $m . " -->\n";
    }
  }//end addComment()

  private static function addImagesFromPageToSitemap( Page $page, string &$r ) : void {
    foreach ( $page->images() as $i ) {
      $r .= "  <image:image>\n";
      $r .= '    <image:loc>' . $i->url() . "</image:loc>\n";
      $r .= "  </image:image>\n";
    }
  }//end addImagesFromPageToSitemap()

  private static function addImagesToSitemap( Pages $pages, string &$r ) : void {
    foreach ( $pages as $p ) {
      static::addComment( $r, 'imagining ' . $p->url() . ' [it=' . $p->intendedTemplate() . '] [d=' . $p->depth() . ']' );
      static::addImagesFromPageToSitemap( $p, $r );
    }
  }//end addImagesToSitemap()

  public function getNameOfClass() : string {
    return static::class;
  }//end getNameOfClass()
}//end class
