<?php

namespace omz13;

class xmlsitemap
{
  static $generatedat; // timestamp when sitemap generated
  static $debug;
  static $optionXCWTI;
  static $optionXPWTI;

  // helper
  public function getNameOfClass()
  {
    return static::class;
  }

  // because
  public static function ping(): string
  {
    return static::class . " pong";
  }

  public static function getConfigurationForKey(string $key, $default = null)
  {
    $o = option('omz13.xmlsitemap');

    if (!empty($o))
      if (array_key_exists("$key",$o))
        return $o["$key"];
      else
        return $default; // default
    else
      return $default;
  }

  public static function isEnabled(): bool
  {
    if (self::getConfigurationForKey("disable")=="true")
      return false;
    if (kirby()->site()->content()->xmlsitemap() == "false")
      return false;
    return true;
  }

  public static function getStylesheet(): string
  {
      $f = file_get_contents(__DIR__ . "/../assets/xmlsitemap.xsl");
      if ($f == null)
        throw new \Exception("Failed to read sitemap.xsl", 1);
    return $f;
  }

  public static function getSitemap(\Kirby\Cms\Pages $p, bool $debug = false): string
  {
    return static::generateSitemap($p, $debug);
  }

  private static function generateSitemap(\Kirby\Cms\Pages $p, bool $debug = false): string
  {
    $tbeg = microtime(true);
    // set debug if the global kirby option for debug is also set
    static::$debug = $debug && kirby()->option('debug') !== null &&  kirby()->option('debug')==true;
    static::$optionXCWTI = static::getConfigurationForKey('excludeChildrenWhenTemplateIs');
    static::$optionXPWTI = static::getConfigurationForKey('excludePageWhenTemplateIs');

    $r =
      "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n" .
      "<?xml-stylesheet type=\"text/xsl\" href=\"/sitemap.xsl\"?>\n" .
      "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\" xmlns:image=\"http://www.google.com/schemas/sitemap-image/1.1\">\n";
    static::addPagesToSitemap($p, $r);
    $r.=
      "</urlset>\n" .
      "<!-- sitemap generated using https://github.com/omz13/kirby3-xmlsitemap -->\n";

    $tend = microtime(true);
    if (static::$debug == true) {
      $elapsed = $tend - $tbeg;
      static::$generatedat = $tend;
      $r .= "<!-- That took $elapsed microseconds -->\n";
      $r .= "<!-- Generated at " . static::$generatedat . " -->\n";
    }
    return $r;
  }

  private static function addComment(string &$r, string $m): void
  {
    if (static::$debug == true)
      $r.= "<!-- " . $m . " -->\n";
  }

  private static function addImagesFromPageToSitemap(\Kirby\Cms\Page $page, string &$r)
  {
    foreach($page->images() as $i)
    {
      $r .=
        "  <image:image>\n" .
        "    <image:loc>" . $i->url() . "</image:loc>\n" .
        "  </image:image>\n" ;
    }
  }

  private static function addImagesToSitemap(\Kirby\Cms\Pages $pages, string &$r)
  {
    foreach ($pages as $p) {
      static::addComment($r, "imagining ".$p->url()." [t=".$p->template()->name()."] [d=". $p->depth()."]");
      static::addImagesFromPageToSitemap($p, $r);
    }
  }

  private static function addPagesToSitemap(\Kirby\Cms\Pages $pages, string &$r)
  {
    foreach ($pages as $p) {
      static::addComment($r, "crunching ".$p->url()." [t=".$p->template()->name()."] [d=". $p->depth()."]");

      // don't include the error page
      if ($p->isErrorPage()) {
        continue;
      }

      // exclude because template used is in the exclusion list:
      if (isset(static::$optionXPWTI) && in_array($p->template()->name(), static::$optionXPWTI)) {
        static::addComment($r, "excluding " . $p->url() . " because excludePageWhenTemplateIs (" . $p->template()->name() . ")");
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
      // for the homepage, ensure we end the URL with a /
      $r .= "  <loc>" . $p->url() . ($p->isHomePage() ? "/" : "") . "</loc>\n";

      $timestamp_c = strtotime($p->content()->date());
      $timestamp_e = strtotime($p->content()->embargo());
      $timestamp_m = file_exists($p->contentFile()) ? filemtime($p->contentFile()) : 0;

      // set modified date to be last date vis-a-vis when file modified /content embargo time / content date
      $r .= '  <lastmod>' . date("c", max($timestamp_m, $timestamp_e, $timestamp_c)) . "</lastmod>\n";

/* don't bother with priority - we ignore those. It's essentially a bag of noise" - [ref https://twitter.com/methode/status/846796737750712320]
      if ($p->depth()==1)
        $r.="  <priority>". ($p->isHomePage() ? "1.0" : "0.9") . "</priority>\n";
      if ($p->depth()>=2)
        $r.="  <priority>0.8</priority>\n";
*/

      static::addImagesFromPageToSitemap($p, $r);

      if ($p->children() !== null) {
        // jump into the children, unless the current page's template is in the exclude-its-children set
        if (!in_array($p->template()->name(), static::$optionXCWTI)) {
          $r .= "</url>\n";
          static::addPagesToSitemap($p->children(), $r);
        } else {
          static::addComment($r, "ignoring children of " . $p->url() . " because excludeChildrenWhenTemplateIs (" . $p->template()->name() . ")");
          static::addImagesToSitemap($p->children(), $r);
          $r .= "</url>\n";
        }
      }
      else {
        $r .= "</url>\n";
      }
    }
//    return $r;
  }
}
