# Kirby3 xmlsitemap

 ![Kirby Version](https://img.shields.io/badge/Kirby-3black.svg) [![Issues](https://img.shields.io/github/issues/omz13/kirby3-xmlsitemap.svg)](https://github.com/omz13/kirby3-xmlsitemap/issues)

**Requirement:** Kirby 3.0.0-beta-6.16 or better

## Documentation

### Purpose

For a kirby3 site, this plugin (_omz13/xmlsitemap_) automatically generates an xml-based sitemap at `/sitemap.xml` and provides a prettyfier (`/sitemap.xsl`) for humans.

- Generates a [sitemap](https://www.sitemaps.org); [valid](https://webmaster.yandex.com/tools/sitemap/) too.
- Works with both single language and multilanguage sites.
- For multilingual sites, a `<url>` is generated for each language, and each `<loc>` will also include:
  - Bidirectional [hreflang](https://support.google.com/webmasters/answer/189077) links
  - An [x-default](https://webmasters.googleblog.com/2013/04/x-default-hreflang-for-international-pages.html) link per the site's default language.
- The generated page can be cached for a determined amount of time, c.f. `cacheTTL` in _Configuration_. This not only improves the response time if it can be retrieved from the cache.
- For all pages, `<loc>` and `<lastmod>` are given; `<priority>` is not given because "its a bag of noise"; `<changefreq>` is also not given because it does not affect ranking.
- `<lastmod`> is calculated using the date in a page's field called `updatedat`, or if not present then from the field `date`; if neither were found, it is based on the modification time for the page's content file.
- When a page is included in the xml-sitemap, information for images (`<image:loc>`) on each page is inclued unless this is disabled; c.f. `disableImages` in _Configuration_.
- The generated `sitemap.xml` has an accompanying `sitemap.xsl` to produce a prettified page for human consumption.
- Only pages that have a status of "published" are included (everything else, `drafts` and `unlisted`, are excluded).
- Pages or their children can be excluded based on the following criteria, and in the following order:
  - The homepage is always included.
  - The error page is always excluded.
  - Only pages that have a status of "published" are included, i.e. those with "draft" or "unpublished" are excluded.
  - Unpublished pages can be explicitly included based on their slugname; c.f. `includeUnlistedWhenSlugIs` in _Configuration_.
  - Pages made using certain templates can be excluded; c.f. `excludePageWhenTemplateIs` in _Configuration_.
  - Pages with certain slugnames can be excluded; c.f. `excludePageWhenSlugIs` in _Configuration_.
  - Pages with a content field `excludefromxmlsitemap` that is `true` are excluded.
  - Pages with a method `issunset` that returns `true` are excluded.
  - Pages with a method `isunderembargo` that returns `true` are excluded.
  - For use with "one-pagers", children of pages made using certain templates can be excluded as sub-ordinate links (c.f. `excludeChildrenWhenTemplateIs` in _Configuration_) but any _images_ in those children *will* be included and listed as normal (which is how one-pagers are expected to work).
- For debugging purposes, the generated sitemap can include additional information as xml comments; c.f. `debugqueryvalue` in _Configuration_.

#### Related plugins

For a user-oriented html-based sitemp, kindly see [omz13/kirby3-htmlsitemap](https://github.com/omz13/kirby3-htmlsitemap).

For a plugin that provides the methods `issunset` and `isunderembargo`, kindly see [omz13/kirby3-suncyclepages](https://github.com/omz13/kirby3-suncyclepages).

#### Caveat

Kirby3 is under beta, therefore this plugin, and indeed kirby3 itself, may or may not play nicely with each other, or indeed work at all: use it for testing purposes only; if you use it in production then you should be aware of the risks and know what you are doing.

#### Roadmap

For 1.0, the non-binding list of planned features and implementation notes are:

- [x] MVP (`loc` and `lastmod`) **done 0.1**
- [ ] ~~`<priority>`~~
- [ ] ~~`<changefreq>`~~
- [x] Respect page status **done 0.2**
- [x] Allow specific unlisted pages to be included **done 0.2** c.f.  `includeUnlistedWhenSlugIs`
- [x] One-pager support **done 0.1** c.f. `excludeChildrenWhenTemplateIs`
- [x] Include [image sitemap]((https://support.google.com/webmasters/answer/178636?hl=en)) `<image:image>`
- [x] `<image:loc>` **done 0.2**
- [ ] `<image:caption>`
- [ ] `<image:title>`
- [ ] `<image:license>`
- [x] Exclude image sitemap; c.f. `disableImages` **done 0.3**
- [x] Exclusion of individual pages – **done 0.2** c.f. `excludePageWhenSlugIs`
- [x] Exclusion of pages by template – **done 0.1** c.f. `excludePageWhenTemplateIs`
- [x] Better heuristics for `<lastmod>` (e.g. `modifiedat` field?)
- [ ] ~~Overriding of stylesheet~~
- [ ] Disable stylesheet
- [ ] Remove credits
- [ ] robots.txt
- [x] Cache **done 0.4** c.f. `cacheTTL`
- [ ] Automate GitHub release – [gothub](https://github.com/itchio/gothub)? [github-release-notes](https://github.com/github-tools/github-release-notes)?
- [ ] Inform search engine crawlers when map changes
- [ ] Guard 50,000 URLs limit
- [ ] Guard 50MB limit
- [ ] [Sitemap Index files](https://www.sitemaps.org/protocol.html#index)
- [ ] [Video sitemap](https://support.google.com/webmasters/answer/80471?hl=en&ref_topic=4581190) `<video:video>`
- [x] Support Multilingual sites - **done 0.5** **REQUIRES kirby 3.0.0-beta-6.16 or better**
- [x] `x-default` in ML sitemap **done 0.5**

### Installation

#### via composer

If your kirby3-based site is managed using-composer, simply invoke `composer require --no-dev omz13/kirby3-xmlsitemap`, or add `omz13/kirby3-xmlsitemap` to the "require" component of your site's `composer.json` as necessary, e.g. to be on the bleeding-edge:

```yaml
"require": {
  ...
  "omz13/kirby3-xmlsitemap": "@dev",
  ...
}
```

#### via git

Clone github.com/omz13/kirby3-xmlsitemap into your `site/plugins` and then in `site/plugins/kirby3-xmlsitemap` invoke ``composer update --no-dev`` to generate the `vendor` folder and the magic within.

```sh
$ git clone github.com/omz13/kirby3-xmlsitemap site/plugins/kirby3-xmlsitemap
$ cd site/plugins/kirby3-xmlsitemap
$ composer update --no-dev
```

If your project itself is under git, then you need to add the plugin as a submodule and possibly automate the composer update; it is assumed if you are doing this that you know what to do.

#### via zip

So you want everything in a zip file you can simply expand into `site/plugins/kirby3-xmlsitemap`? Not yet. Sorry.

### Configuration

The following mechanisms can be used to modify the plugin's behaviour.

#### via `config.php`

In your site's `site/config/config.php` the following entries prefixed with `omz13.xmlsitemap.` can be used:

- `disable` : a boolean which, if true, to disable the xmlsitemap functionality (c.f. `xmlsitemap` in _via `site.txt`_).
- `cacheTTL` : the number of minutes that the xml-sitemap should be cached before being regenerated; if explicitly set to zero, the cache is disabled. If not specified a default of 10 minutes is assumed.
- `debugqueryvalue` : a string to be as the value for the query parameter `debug` to return the xml-sitemap with debugging information (as comment nodes within the xml stream). The global kirby `debug` configuration must also be true for this to work. The url must be to `/sitemap.xml?debug=debugqueryvalue` and not `/sitemap?debug=_debugqueryvalue_` (i.e. the `.xls` part is important). Be aware that the debugging information will show, if applicable, details of any pages that have been excluded (so if you are using this in production and you don't want things to leak, set `debugqueryvalue` to something random). Furthermore, the site debug flag needs to be set too (i.e. the `debug` flag in `site/config.php`).
- `includeUnlistedWhenSlugIs` : an array of slugnames whose pages are to be included if their status is unlisted.
- `excludePageWhenTemplateIs` : an array of templates names whose pages are to be excluded from the xml-sitemap.
- `excludePageWhenSlugIs` : an array of slug names whose pages are to be excluded from the xml-sitemap.
- `excludeChildrenWhenTemplateIs` : an array of templates names whose children are to be ignored (but pages associated with the template is to be included); this is used for one-pagers (where the principal page will be included and all the 'virtual' children ignored).
- `disableImages` : a boolean which, if true, disables including data for images related to pages included in the xml-sitemap.

For example, for the [Kirby Starter Kit](https://github.com/k-next/starterkit), the following would be applicable:

```php
<?php

return [
  'omz13.xmlsitemap.cacheTTL' => 60,
  'omz13.xmlsitemap.includeUnlistedWhenSlugIs' => [ ],
  'omz13.xmlsitemap.excludePageWhenTemplateIs' => [ 'contact','sandbox' ],
  'omz13.xmlsitemap.excludePageWhenSlugIs' => [ 'form' ],
  'omz13.xmlsitemap.excludeChildrenWhenTemplateIs' => [ 'events','one-pager','shop','team','testimonials' ],
  ],
];
```

_For experimental purposes this plugin implements a single-level pseudo-namespace. You can mix discrete vs array options, but try not to, and be aware that priority is given to the array variant. The above discrete configuration would therefore become:_

```php
<?php

return [
  'omz13.xmlsitemap' => [
    'cacheTTL' => 60,
    'includeUnlistedWhenSlugIs' => [ 'about' ],
    'excludePageWhenTemplateIs' => ['contact','sandbox'],
    'excludePageWhenSlugIs' => [ 'form' ],
    'excludeChildrenWhenTemplateIs' => [ 'events','one-pager','shop','team','testimonials' ],
    'disableImages' => false,
  ],
];
```

See Kirby3's [issue #761](https://github.com/k-next/kirby/issues/761) for more about namespaced options.


And to have a debugged sitemap returned  at `/sitemap.xml?debug=wombat`, it would be:

```php
<?php

return [
  'debug'  => true,

  'omz13.xmlsitemap' => [
    'omz13.xmlsitemap.debugqueryvalue' => 'wombat,'
    'omz13.xmlsitemap.includeUnlistedWhenSlugIs' => [ ],
    'omz13.xmlsitemap.excludeChildrenWhenTemplateIs' => ['events','one-pager','shop','team','testimonials'],
    'omz13.xmlsitemap.excludePageWhenTemplateIs' => ['contact','sandbox'],
    'omz13.xmlsitemap.excludePageWhenSlugIs' => [ 'form' ],
  ],
];
```

#### via `site.txt`

The plugin can be explicitly disabled in `content\site.txt` by having an entry for `xmlsitemap` and setting this to `false`. This could be achieved through the panel by adding the following into `site/blueprints/site.yml`:

```yaml
type:          fields
fields:
  xmlsitemap:
    label:     XML sitemap
    type:      toggle
    default:   off
    text:
      - disabled
      - enabled
```
#### via content fields

If a page's content has a field called `excludefromxmlsitemap` and this is set to `true`, then that page (and any children, if present) will be excluded. Similarly to `site.txt`, this can be easily achieved in a blueprint.

```yaml
type:          fields
fields:
  excludefromxmlsitemap:
    label:     Exclude from sitemap.xml
    type:      toggle
    default:   off
    text:
      - include implicitly
      - explicitly exclude
```

As pages are implicitly included within a sitemap, this mechanism should only be used when you have a reason to explcitly exclude a page when it is not possible to do otherwise (e.g. using `excludePageWhenTemplateIs`).

## --dev

To develop on this plugin, `composer update --dev` will pull in the necessary packages and whatnots.

The `composer` package include scripts that are useful during development:

- `composer lint` to lint the source (using [parallel-lint](https://packagist.org/packages/jakub-onderka/php-parallel-lint))
- `composer style` to check style issues (using  `phpcs` from [PHP_CodeSniffer](https://packagist.org/packages/squizlabs/php_codesniffer)) against coding standard `omz13-k3p` in omz13/coding-standard).
- `composer fix` to fix code style issues (using `phpcbf` from [PHP_CodeSniffer](https://packagist.org/packages/squizlabs/php_codesniffer))
- `composer mess` to check for potential messes (using `phpmd` from the [PHP Mess Detector](https://packagist.org/packages/phpmd/phpmd))
- `composer stan` to do static analysis of the source (using `phpstan` from the [PHP Static Analysis Tool](https://packagist.org/packages/phpstan/phpstan))
- `composer sanity` to run all the above in sequence. If no errors are shown, this is good.

A `makefile` is provided because:

- `make sanity` to check the validity of the composer file and run the sanity script (and its less keys than typing `composer sanity`, and also because I keep mistyping it as `compsoer sanity`).
- `make release` to relase an updated version using [liip/rmt](https://packagist.org/packages/liip/rmt).

## Disclaimer

This plugin is provided "as is" with no guarantee. Use it at your own risk and always test it yourself before using it in a production environment. If you find any issues, please [create a new issue](https://github.com/omz13/kirby3-xmlsitemap/issues/new).

## License

[MIT](https://opensource.org/licenses/MIT)

You are prohibited from using this plugin in any project that promotes racism, sexism, homophobia, animal abuse, violence or any other form of hate speech.

### Buy Me A Coffee

To show your support for this project you are welcome to [buy me a coffee](https://buymeacoff.ee/omz13).

<!-- If you are using this plugin on a kirby3 site that has a Personal licence, to show your support for this project you are welcome to [buy me a coffee](https://buymeacoff.ee/omz13).

If you are using this plugin with a kirby3 site that has a Pro licence, to show your support for this project you are greatly encouraged to [buy me a coffee](https://buymeacoff.ee/omz13).
-->
