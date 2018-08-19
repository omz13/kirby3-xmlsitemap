<?php

Kirby::plugin('omz13/xmlsitemap', [

  'options' => [
    [
      "omz13.xmlsitemap" =>
      [
      'debugqueryvalue' => '42',
      'excludePageWhenTemplateIs' => [],
      'excludeChildrenWhenTemplateIs' => []
      ]
    ]
  ],

  'routes' => [
    [
      'pattern' => 'sitemap',
      'action' => function () {
        if (kirby()->site()->content()->xmlsitemap() == "false") {
          header('HTTP/1.0 404 Not Found');
          die;
        } else {
          return go('sitemap.xml');
        }
      }
    ],

    [
      'pattern' => 'sitemap.xml',
      'action' => function () {
        if (kirby()->site()->content()->sitemap() == "false") {
          header('HTTP/1.0 404 Not Found');
          /*                    echo page((string)site()->errorPage())->render(); */
          die;
        } else {

          $dodebug = (omz13\xmlsitemap::getConfigurationForKey('debugqueryvalue') == get('debug'));
          // if get /sitemap.xml?debug=whatever (where whatever is set by the configuration parameter debugqueryvalue)
          return new Kirby\Cms\Response(omz13\xmlsitemap::getSitemap(kirby()->site()->pages(), $dodebug), "application/xml");
        }
      }
    ],

    [
      'pattern' => 'sitemap.xsl',
      'action' => function () {
        return new Kirby\Cms\Response(omz13\xmlsitemap::getStylesheet(), "xsl");
      }
    ]
  ],
]);

require_once __DIR__ . '/classes/xmlsitemap.php';
