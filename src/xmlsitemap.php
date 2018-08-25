<?php
// phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps
namespace omz13;

define('XMLSITEMAP_VERSION', '0.2.1');

class XMLSitemap {

  private static $generatedat; // timestamp when sitemap generated
  private static $debug;
  private static $optionIUWSI; // include unlisted when slug is
  private static $optionXCWTI; // exclude children when template is
  private static $optionXPWTI; // exclude page when template is
  private static $optionXPWSI; // exclude page when slug is

  public static $version = XMLSITEMAP_VERSION;

  public static function ping(): string {
      return static::class . " pong " . static::$version;
  }

  public static function isEnabled(): bool {
    if (self::getConfigurationForKey("disable") == "true") {
        return false;
    }
    if (kirby()->site()->content()->xmlsitemap() == "false") {
        return false;
    }
      return true;
  }

  public static function getConfigurationForKey(string $key, $default = null) {
      $o = option('omz13.xmlsitemap');

    if (isset($o)) {
      if (array_key_exists("$key", $o)) {
        return $o["$key"];
      } else {
          return $default; // default
      }
    } else {
        return $default;
    }
  }

  public static function getStylesheet(): string {
      $f = file_get_contents(__DIR__ . "/../assets/xmlsitemap.xsl");
    if ($f == null) {
        throw new \Exception("Failed to read sitemap.xsl", 1);
    }
      return $f;
  }

  public static function getSitemap(\Kirby\Cms\Pages $p, bool $debug = false): string {
      return static::generateSitemap($p, $debug);
  }

  private static function generateSitemap(\Kirby\Cms\Pages $p, bool $debug = false): string {
      $tbeg = microtime(true);
      // set debug if the global kirby option for debug is also set
      static::$debug = $debug && kirby()->option('debug') !== null && kirby()->option('debug') == true;
      static::$optionIUWSI = static::getConfigurationForKey('includeUnlistedWhenSlugIs');
      static::$optionXCWTI = static::getConfigurationForKey('excludeChildrenWhenTemplateIs');
      static::$optionXPWTI = static::getConfigurationForKey('excludePageWhenTemplateIs');
      static::$optionXPWSI = static::getConfigurationForKey('excludePageWhenSlugIs');


      $r =
      "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n" .
      "<?xml-stylesheet type=\"text/xsl\" href=\"/sitemap.xsl\"?>\n" .
      "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\" xmlns:image=\"http://www.google.com/schemas/sitemap-image/1.1\">\n";
      static::addPagesToSitemap($p, $r);
      $r .=
      "</urlset>\n" .
      "<!-- sitemap generated using https://github.com/omz13/kirby3-xmlsitemap -->\n";

      $tend = microtime(true);
    if (static::$debug == true) {
        $elapsed = $tend - $tbeg;
        static::$generatedat = $tend;
        $r .= "<!-- v" . static::$version . " -->\n";
        $r .= "<!-- That took $elapsed microseconds -->\n";
        $r .= "<!-- Generated at " . static::$generatedat . " -->\n";
    }
      return $r;
  }

  private static function addPagesToSitemap(\Kirby\Cms\Pages $pages, string &$r) {
      $sortedpages = $pages->sortBy('url', 'asc');
    foreach ($sortedpages as $p) {
        static::addComment($r, "crunching " . $p->url() . " [t=" . $p->template()->name() . "] [s=" . $p->status() . "] [d=" . $p->depth() . "]");

        // don't include the error page
      if ($p->isErrorPage()) {
        continue;
      }

      if ($p->status() == "unlisted" && !$p->isHomePage()) {
        if (isset(static::$optionIUWSI) && in_array($p->slug(), static::$optionIUWSI)) {
          static::addComment($r, "including " . $p->url() . " because unlisted but in includeUnlistedWhenSlugIs");
        } else {
            static::addComment($r, "excluding " . $p->url() . " because unlisted");
            continue;
        }
      }

        // exclude because template used is in the exclusion list:
      if (isset(static::$optionXPWTI) && in_array($p->template()->name(), static::$optionXPWTI)) {
          static::addComment($r, "excluding " . $p->url() . " because excludePageWhenTemplateIs (" . $p->template()->name() . ")");
          continue;
      }

        // exclude because slug is in the exclusion list:
      if (isset(static::$optionXPWSI) && in_array($p->slug(), static::$optionXPWSI)) {
          static::addComment($r, "excluding " . $p->url() . " because excludePageWhenSlugIs (" . $p->template()->name() . ")");
          continue;
      }

        // exclude because page content field 'excludefromxmlsitemap':
      if ($p->content()->excludefromxmlsitemap() == "true") {
          static::addComment($r, "excluding " . $p->url() . " because excludefromxmlsitemap");
          continue;
      }

        // exclude because, if supported, the page is sunset:
      if ($p->hasMethod("issunset")) {
        if ($p->issunset()) {
            static::addComment($r, "excluding " . $p->url() . " because issunset");
            continue;
        }
      }

        // exclude because, if supported,  the page is under embargo
      if ($p->hasMethod("isunderembargo")) {
        if ($p->isunderembargo()) {
            static::addComment($r, "excluding " . $p->url() . " because isunderembargo");
            continue;
        }
      }

        // <loc>https://www.example.com/slug</loc>

        $r .= "<url>\n";
        $r .= "  <loc>" . $p->url() . /*($p->isHomePage() ? "/" : "") .*/
        "</loc>\n";

        $timestampC = strtotime($p->content()->date());
        $timestampE = strtotime($p->content()->embargo());
        $timestampM = file_exists($p->contentFile()) ? filemtime($p->contentFile()) : 0;

        // set modified date to be last date vis-a-vis when file modified /content embargo time / content date
        $r .= '  <lastmod>' . date("c", max($timestampM, $timestampE, $timestampC)) . "</lastmod>\n";

        /* don't bother with priority - we ignore those. It's essentially a bag of noise" - [ref https://twitter.com/methode/status/846796737750712320]
        if ($p->depth()==1)
        $r.="  <priority>". ($p->isHomePage() ? "1.0" : "0.9") . "</priority>\n";
        if ($p->depth()>=2)
        $r.="  <priority>0.8</priority>\n";
        */

        static::addImagesFromPageToSitemap($p, $r);

      if ($p->children() !== null) {
          // jump into the children, unless the current page's template is in the exclude-its-children set
        if (isset(static::$optionXCWTI) && in_array($p->template()->name(), static::$optionXCWTI)) {
            static::addComment($r, "ignoring children of " . $p->url() . " because excludeChildrenWhenTemplateIs (" . $p->template()->name() . ")");
            static::addImagesToSitemap($p->children(), $r);
            $r .= "</url>\n";
        } else {
            $r .= "</url>\n";
            static::addPagesToSitemap($p->children(), $r);
        }
      } else {
          $r .= "</url>\n";
      }
    }
      //    return $r;
  }

  private static function addComment(string &$r, string $m): void {
    if (static::$debug == true) {
        $r .= "<!-- " . $m . " -->\n";
    }
  }

  private static function addImagesFromPageToSitemap(\Kirby\Cms\Page $page, string &$r) {
    foreach ($page->images() as $i) {
        $r .=
        "  <image:image>\n" .
        "    <image:loc>" . $i->url() . "</image:loc>\n" .
        "  </image:image>\n";
    }
  }

  private static function addImagesToSitemap(\Kirby\Cms\Pages $pages, string &$r) {
    foreach ($pages as $p) {
        static::addComment($r, "imagining " . $p->url() . " [t=" . $p->template()->name() . "] [d=" . $p->depth() . "]");
        static::addImagesFromPageToSitemap($p, $r);
    }
  }

  public function getNameOfClass() {
      return static::class;
  }
}
