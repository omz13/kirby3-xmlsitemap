<?php

namespace omz13;

define( 'XMLSITEMAP_VERSION', '0.4.0' );

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class XmlSitemap
{
  private static $debug;
  private static $optionCACHE = 0;    // cache TTL in *minutes*; if zero or null, no cache
  private static $optionNOIMG; // disable including image data
  private static $optionIUWSI; // include unlisted when slug is
  private static $optionXCWTI; // exclude children when template is
  private static $optionXPWTI; // exclude page when template is
  private static $optionXPWSI; // exclude page when slug is
  public static $version = XMLSITEMAP_VERSION;

  public static function ping(): string {
    return static::class . ' pong ' . static::$version;
  }//end ping()

  public static function isEnabled(): bool {
    if ( self::getConfigurationForKey( 'disable' ) == 'true' ) {
      return false;
    }

    if ( kirby()->site()->content()->xmlsitemap() == 'false' ) {
      return false;
    }

    return true;
  }//end isEnabled()

  public static function getConfigurationForKey( string $key ) {
    // Try to pick up configuration when provided in an array (vendor.plugin.array(key=>value))
    $o = option( 'omz13.xmlsitemap' );
    if ( $o != null && is_array( $o ) && array_key_exists( $key, $o ) ) {
      return $o[$key];
    }

    // try to pick up configuration as a discrete (vendor.plugin.key=>value)
    $o = option( 'omz13.xmlsitemap.' . $key );
    if ( $o != null ) {
      return $o;
    }

    // this should not be reached... because plugin should define defaults for all its options...
    return null;
  }//end getConfigurationForKey()

  public static function getStylesheet(): string {
    $f = file_get_contents( __DIR__ . '/../assets/xmlsitemap.xsl' );
    if ( $f == null ) {
      throw new \Exception( 'Failed to read sitemap.xsl', 1 );
    }

    return $f;
  }//end getStylesheet()

  /**
   * @SuppressWarnings("Complexity")
   */
  public static function getSitemap( \Kirby\Cms\Pages $p, bool $debug = false ): string {
    static::$debug       = $debug && kirby()->option( 'debug' ) !== null && kirby()->option( 'debug' ) == true;
    static::$optionCACHE = static::getConfigurationForKey( 'cacheTTL' );

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

      $cacheName = XMLSITEMAP_VERSION . '-sitemap-' . static::$optionCACHE;
      if ( $debug ) {
        $cacheName .= '-d';
      }

      $r = $cacheCache->get( $cacheName );
      if ( $r == null ) {
        $r = static::generateSitemap( $p, $debug );
        $cacheCache->set( $cacheName, $r, static::$optionCACHE );
        if ( static::$debug == true ) {
          $r .= '<!-- Freshly generated; cache for ' . static::$optionCACHE . " minute(s) for reuse -->\n";
        }
      } else {
        if ( static::$debug == true ) {
          $expiresAt       = $cacheCache->expires( $cacheName );
          $secondsToExpire = ( $expiresAt - time() );
          $r              .= '<!-- Retrieved from cache; expires in ' . $secondsToExpire . " seconds -->\n";
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

  private static function generateSitemap( \Kirby\Cms\Pages $p, bool $debug = false ): string {
    $tbeg = microtime( true );
    // set debug if the global kirby option for debug is also set
    static::$optionNOIMG = static::getConfigurationForKey( 'disableImages' );
    static::$optionIUWSI = static::getConfigurationForKey( 'includeUnlistedWhenSlugIs' );
    static::$optionXCWTI = static::getConfigurationForKey( 'excludeChildrenWhenTemplateIs' );
    static::$optionXPWTI = static::getConfigurationForKey( 'excludePageWhenTemplateIs' );
    static::$optionXPWSI = static::getConfigurationForKey( 'excludePageWhenSlugIs' );

    $r = '';

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
      $r      .= '<!-- v' . static::$version . " -->\n";
      $r      .= '<!-- Generation took ' . ( 1000 * $elapsed ) . " microseconds -->\n";
      $r      .= '<!-- Generated at ' . date( DATE_ATOM, $tend ) . " -->\n";
    }

    return $r;
  }//end generateSitemap()

  /**
   * @SuppressWarnings("Complexity")
   */
  private static function addPagesToSitemap( \Kirby\Cms\Pages $pages, string &$r ) {
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

      $timestampC = strtotime( $p->content()->date() );
      $timestampE = strtotime( $p->content()->embargo() );
      $timestampM = file_exists( $p->contentFile() ) ? filemtime( $p->contentFile() ) : 0;

      // set modified date to be last date vis-a-vis when file modified /content embargo time / content date
      $r .= '  <lastmod>' . date( 'c', max( $timestampM, $timestampE, $timestampC ) ) . "</lastmod>\n";

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

  private static function addComment( string &$r, string $m ): void {
    if ( static::$debug == true ) {
      $r .= '<!-- ' . $m . " -->\n";
    }
  }//end addComment()

  private static function addImagesFromPageToSitemap( \Kirby\Cms\Page $page, string &$r ) {
    foreach ( $page->images() as $i ) {
      $r .= "  <image:image>\n";
      $r .= '    <image:loc>' . $i->url() . "</image:loc>\n";
      $r .= "  </image:image>\n";
    }
  }//end addImagesFromPageToSitemap()

  private static function addImagesToSitemap( \Kirby\Cms\Pages $pages, string &$r ) {
    foreach ( $pages as $p ) {
      static::addComment( $r, 'imagining ' . $p->url() . ' [it=' . $p->intendedTemplate() . '] [d=' . $p->depth() . ']' );
      static::addImagesFromPageToSitemap( $p, $r );
    }
  }//end addImagesToSitemap()

  public function getNameOfClass() {
    return static::class;
  }//end getNameOfClass()
}//end class
