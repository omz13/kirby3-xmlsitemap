<?php

Kirby::plugin(
    'omz13/xmlsitemap',
    [
      'root' => dirname( __FILE__, 2 ),
      'options' => [
        'disable'                       => false,
        'cache'                         => true, // enable plugin cache facility
        'debugqueryvalue'               => '42',
        'cacheTTL'                      => 10,
        'includeUnlistedWhenSlugIs'     => [],
        'excludePageWhenTemplateIs'     => [],
        'excludePageWhenSlugIs'         => [],
        'excludeChildrenWhenTemplateIs' => [],
        'disableImages'                 => false,
      ],

      'routes'  => [
        [
          'pattern' => 'sitemap.xml',
          'action'  => function () {
            if ( omz13\XMLSitemap::isEnabled() ) {
                $dodebug = omz13\XMLSitemap::getConfigurationForKey( 'debugqueryvalue' ) == get( 'debug' );
                return new Kirby\Cms\Response( omz13\XMLSitemap::getSitemap( kirby()->site()->pages(), $dodebug ), 'application/xml' );
            } else {
                header( 'HTTP/1.0 404 Not Found' );
                echo 'This site does not have a <a href=https://www.sitemaps.org>sitemap</a>; sorry.';
                die;
            }
          },
        ],

        [
          'pattern' => 'sitemap.xsl',
          'action'  => function () {
            return new Kirby\Cms\Response( omz13\XMLSitemap::getStylesheet(), 'xsl' );
          },
        ],
      ],
    ]
);

require_once __DIR__ . '/xmlsitemap.php';
