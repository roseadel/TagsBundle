Netgen Tags Bundle
==================

[![Build Status](https://img.shields.io/travis/netgen/TagsBundle.svg?style=flat-square)](https://travis-ci.org/netgen/TagsBundle)
[![Code Coverage](https://img.shields.io/codecov/c/github/netgen/TagsBundle.svg?style=flat-square)](https://codecov.io/gh/netgen/TagsBundle)
[![Downloads](https://img.shields.io/packagist/dt/netgen/tagsbundle.svg?style=flat-square)](https://packagist.org/packages/netgen/tagsbundle)
[![Latest stable](https://img.shields.io/packagist/v/netgen/tagsbundle.svg?style=flat-square)](https://packagist.org/packages/netgen/tagsbundle)
[![License](https://img.shields.io/packagist/l/netgen/tagsbundle.svg?style=flat-square)](https://packagist.org/packages/netgen/tagsbundle)

Netgen Tags Bundle is an eZ Publish / eZ Platform bundle for taxonomy management and easier classification of content, providing more functionality for tagging content than `ezkeyword` field type included in eZ Publish kernel.

This repository represents a rewrite of eZ Tags, the original eZ Publish 4 extension located at [https://github.com/ezsystems/eztags](https://github.com/ezsystems/eztags).

Implemented features
--------------------

* `eztags` field type
* Tags service and legacy SPI handler
* Signal slot tags service
* `/tags/id/{tagId}` and `/tags/view/{tagUrl}` pages
* `TagId` and `TagKeyword` search criteria
* Solr indexing of `eztags` field type
* Tag router and path generator
* Admin interface (standalone, as well integrated into Platform UI)
* REST interface

Credits
-------

Release 3.0 of this bundle was backed in [crowdfunding campaign](https://www.indiegogo.com/projects/netgen-tags-bundle-support-for-ez-platform-ui--3) by [Netmaking AS](https://netmaking.no), [Greater Stavanger](http://www.greaterstavanger.com) and others.

License and installation instructions
-------------------------------------

[License](LICENSE)

[Installation instructions](bundle/Resources/doc/INSTALL.md)

[Upgrade instructions](bundle/Resources/doc/UPGRADE.md)

[Changelogs](bundle/Resources/doc/)

Unit tests
----------

There are two sets of tests available, unit tests and legacy integration tests.

### Running unit tests

    $ composer install
    $ composer test

### Running legacy integration tests

    $ composer install
    $ composer test-integration
