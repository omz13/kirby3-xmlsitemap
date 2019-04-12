<?php

Kirby::plugin(
    'omz13/xmlsitemap',
    [
      'root'        => dirname( __FILE__, 2 ),
      'options'     => [
        'disable'                       => false,
        'cache'                         => true, // enable plugin cache facility
        'debugqueryvalue'               => '42',
        'cacheTTL'                      => 10,
        'includeUnlistedWhenSlugIs'     => [],
        'includeUnlistedWhenTemplateIs' => [],
        'excludePageWhenTemplateIs'     => [],
        'excludePageWhenSlugIs'         => [],
        'excludeChildrenWhenTemplateIs' => [],
        'disableImages'                 => false,
        'hideuntranslated'              => false,
        'addPages'                      => null,
        'x-shimHomepage'                => false,
      ],

      'pageMethods' => [
        'headLinkAlternates' => function () {
          $r = "<!-- headLinkAlternates -->" . PHP_EOL;
          if ( kirby()->multilang() ) {
            foreach ( kirby()->languages() as $language ) {
              // phpcs:ignore PHPCompatibility.PHP.NewClosure.ThisFoundOutsideClass
              if ( $language->code() == kirby()->language() && ! $this->translation( $language->code() )->exists() ) {
                continue;
              }
              // phpcs:ignore PHPCompatibility.PHP.NewClosure.ThisFoundOutsideClass
              $r .= '<link rel="alternate" hreflang="' . $language->code() . '" href="' . $this->url( $language->code() ) . '" />' . PHP_EOL;
            }
          } else {
            $r = "<!-- NA because SL -->" . PHP_EOL;
          }
          if ( kirby()->option( 'debug' ) !== null && kirby()->option( 'debug' ) == true ) {
            return $r;
          } else {
            return '';
          }
        },
      ],

      'routes'      => [
        [
          'pattern' => 'sitemap.xml',
          'action'  => function () {
            if ( omz13\XMLSitemap::isEnabled() ) {
                $dodebug = omz13\XMLSitemap::getConfigurationForKey( 'debugqueryvalue' ) == get( 'debug' );
                $nocache = get( 'nocache' );
                return new Kirby\Cms\Response( omz13\XMLSitemap::getSitemap( kirby()->site()->pages(), $dodebug, $nocache ), 'application/xml' );
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
