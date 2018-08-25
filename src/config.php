<?php

Kirby::plugin(
    'omz13/xmlsitemap',
    [

    'options' => [
    [
      "omz13.xmlsitemap" =>
        [
          'disable' => false,
          'debugqueryvalue' => '42',
          'excludePageWhenTemplateIs' => [],
          'excludeChildrenWhenTemplateIs' => [],
          'excludePageWhenSlugIs' => [],
        ]
    ]
    ],

    'routes' => [
    /*
        [

          'pattern' => 'sitemap',
          'action' => function () {
            if (omz13\xmlsitemap::isEnabled()) {
              return go('sitemap.xml');
            } else {
              return;
            }
          }
        ],
    */

    [
      'pattern' => 'sitemap.xml',
      'action' => function () {
        if (omz13\XMLSitemap::isEnabled()) {
            $dqv=omz13\XMLSitemap::getConfigurationForKey('debugqueryvalue');
            $dodebug = (isset($dqv) && $dqv == get('debug'));
            return new Kirby\Cms\Response(omz13\XMLSitemap::getSitemap(kirby()->site()->pages(), $dodebug), "application/xml");
        } else {
            header('HTTP/1.0 404 Not Found');
            echo("This site does not have a <a href=https://www.sitemaps.org>sitemap</a>; sorry.");
            die;
        }
      }
    ],

    [
      'pattern' => 'sitemap.xsl',
      'action' => function () {
        return new Kirby\Cms\Response(omz13\XMLSitemap::getStylesheet(), "xsl");
      }
    ]
    ],
    ]
);

require_once __DIR__ . '/xmlsitemap.php';
