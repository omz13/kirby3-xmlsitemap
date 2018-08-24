# Kirby3 xmlsitemap

 ![License](https://img.shields.io/github/license/mashape/apistatus.svg) ![Kirby Version](https://img.shields.io/badge/Kirby-3%2B-black.svg) [![Issues](https://img.shields.io/github/issues/omz13/kirby3-xmlsitemap.svg)](https://github.com/omz13/kirby3-xmlsitemap/issues)

**Requirement:** Kirby 3

## Documentation

### Purpose

For a kirby3 site, this plugin (_omz13/xmlsitemap_) automatically generates `/sitemap.xml` and provides a prettyfier (`/sitemap.xsl`)for humans.

#### Implementation details/features

- Generates a [sitemap](https://www.sitemaps.org); [valid](https://webmaster.yandex.com/tools/sitemap/) too.
- For all pages, `<loc>` and `<lastmod>` are given; `<priority>` is not given because "its a bag of noise"; `<changefreq>` is also not given because it does not affect ranking.
- For images their location is given in `<image:loc>`.
- The generated `sitemap.xls` has an accompanying `sitemap.xsl` to produce a prettified page for human consumption.
- Only pages that have a status of "published" are included (everything else, `drafts` and `unlisted`, are excluded).
<!-- - If a page has the methods `isunderembargo`[^ https://github.com/omz13/kirby3-sunset] or `issunet` [^ https://github.com/omz13/kirby3-sunset] these are respected vis-à-vis inclusion or exclusion from the xmlsitemap. -->
- Pages or their children can be excluded based on the following criteria, and in the following priority:
  - The homepage is always included.
  - The error page is always excluded.
  - Only pages that have a status of "published" are included, i.e. those with "draft" or "unpublished" are excluded.
  - Unpublished pages can be explicitly included based on their slugname; c.f. `includeUnlistedWhenSlugIs` is _Configuration_.
	- Pages made using certain templates can be excluded; c.f. the use of `excludePageWhenTemplateIs` in _Configuration_.
	- Pages with certain slugnames can be excluded; c.f. the use of `excludePageWhenSlugIs` in _Configuration_.
	- The children of pages made using certain templates can be excluded; c.f. the use of `excludeChildrenWhenTemplateIs` in _Configuration_.
- For debugging purposes, the generated sitemap can include additional information as xml comments; c.f. the use of `debugqueryvalue` in _Configuration_.

#### Caveat

Kirby3 is under beta, therefore this plugin, and indeed kirby3 itself, may or may not play nicely with each other, or indeed work at all: use it for testing purposes only; if you use it in production then you should be aware of the risks.

### Installation

#### via composer

If your kirby3-based site is managed-using-composer, simply invoke `composer require omz13/kirby3-xmlsitemap:'@dev'`, or manually add `omz13/kirby3-xmlsitemap` as an item into the 'require' component of your site's `composer.json`:

```yaml
"require": {
  ...
  "omz13/kirby3-xmlsitemap": "@dev",
  ...
}
```

Remember to invoke `composer update --no-dev` as applicable.

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

In your site's `site/config/config.php` the following entries under the key `omz13.xmlsitemap` can be used:

- `disable` : a boolean which, if true, to disable the xmlsitemap functionality (c.f. `xmlsitemap` in _via `site.txt`_).
- `debugqueryvalue` : a string to be as the value for the query parameter `debug` to return the xml-sitemap with debugging information. The global kirby `debug` configuration must also be true for this to work. The url must be to `/sitemap.xml?debug=debugqueryvalue` and not `/sitemap?debug=_debugqueryvalue_` (i.e. the `.xls` part is important). Be aware that the debugging information will show, if applicable, details of any pages that have been excluded (so if you are using this in production and you don't want things to leak, set `debugqueryvalue` to something random). Furthermore, the site debug flag needs to be set too (i.e. the `debug` flag in `site/config.php`).

- `includeUnlistedWhenSlugIs` : an array of slugnames whose pages are to be included if their status is unlisted.
- `excludePageWhenTemplateIs` : an array of templates names whose pages are to be excluded from the xml-sitemap.
- `excludePageWhenSlugIs` : an array of slug names whose pages are to be excluded from the xml-sitemap.
- `excludeChildrenWhenTemplateIs` : an array of templates names whose children are to be ignored (but pages associated with the template is to be included); this is used for one-pagers (where the principal page will be included and all the 'virtual' children ignored).

For example, for the (Kirby Starter Kit)[https://github.com/k-next/starterkit], the following would be applicable:

```php
<?php

return [
  'omz13.xmlsitemap' => [
    'disable' => false,
    'includeUnlistedWhenSlugIs' => [ ],
    'excludeChildrenWhenTemplateIs' => ['events','one-pager','shop','team','testimonials'],
    'excludePageWhenTemplateIs' => ['contact','sandbox'],
    'excludePageWhenSlugIs' => [ 'form' ]
  ],
];
```

And to have a debugged sitemap returned  at `/sitemap.xml?debug=wombat`, it would be:

```php
<?php

return [
  'debug'  => true,

  'omz13.xmlsitemap' => [
    'disable' => false,
    'debugqueryvalue' => 'wombat',
    'includeUnlistedWhenSlugIs' => [ ],
    'excludeChildrenWhenTemplateIs' => ['events','one-pager','shop','team','testimonials'],
    'excludePageWhenTemplateIs' => ['contact','sandbox'],
    'excludePageWhenSlugIs' => [ 'form' ]
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
    test:
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

As pages are implicitly included within a sitemap, this mechanism should only be used when you have a reason to explcitly exclude a page  when it is not possible to do otherwise (c.f. excludePageWhenTemplateIs).

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
