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
      "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\" xmlns:xhtml=\"http://www.w3.org/1999/xhtml\">\n" .
      static::addPagesToSitemap($p) .
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

  private static function addComment(string $m): string
  {
    if (static::$debug == true)
      return "<!-- " . $m . " -->\n";
    else
      return "";
  }

  private static function addPagesToSitemap(\Kirby\Cms\Pages $pages, string $r = ""): string
  {

    foreach ($pages as $p) {
      $r .= static::addComment("crunching ".$p->url()." [t=".$p->template()->name()."] [d=". $p->depth()."]");

      // don't include the error page
      if ($p->isErrorPage()) {
        continue;
      }

      // exclude because template used is in the exclusion list:
      if (isset(static::$optionXPWTI) && in_array($p->template()->name(), static::$optionXPWTI)) {
        $r .= static::addComment("excluding " . $p->url() . " because excludePageWhenTemplateIs (" . $p->template()->name() . ")");
        continue;
      }

      // exclude because page content field 'excludefromxmlsitemap':
      if ($p->content()->excludefromxmlsitemap() == "true") {
        $r .= static::addComment("excluding " . $p->url() . " because excludefromxmlsitemap");
        continue;
      }

      // exclude because, if supported, the page is sunset:
      if ($p->hasMethod("issunset")) {
        if ($p->issunset()) {
          $r .= static::addComment("excluding " . $p->url() . " because issunset");
          continue;
        }
      }

      // exclude because, if supported,  the page is under embargo
      if ($p->hasMethod("isunderembargo")) {
        if ($p->isunderembargo()) {
          $r .= static::addComment("excluding " . $p->url() . " because isunderembargo");
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

      if ($p->depth()==1)
        $r.="  <priority>". ($p->isHomePage() ? "1.0" : "0.9") . "</priority>\n";
      if ($p->depth()>=2)
        $r.="  <priority>0.8</priority>\n";

      $r .= "</url>\n";

      if ($p->children() !== null) {
        if (!isset(static::$optionXCWTI))
        {
          // no exclusions set, so jump into the children
          $r .= static::addPagesToSitemap($p->children(), "");
        }
        else
        {
          // jump in, unless the template used is in the exclusion set
          if (!in_array($p->template()->name(), static::$optionXCWTI)) {
            $r .= static::addPagesToSitemap($p->children(), "");
          } else {
            $r .= static::addComment("ignoring children of " . $p->url() . " because excludeChildrenWhenTemplateIs (" . $p->template()->name() . ")");
          }
        }
      }
    }
    return $r;
  }
}
