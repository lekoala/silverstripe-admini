# SilverStripe admini module

![Build Status](https://github.com/lekoala/silverstripe-admini/actions/workflows/ci.yml/badge.svg)
[![scrutinizer](https://scrutinizer-ci.com/g/lekoala/silverstripe-admini/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/lekoala/silverstripe-admini/)
[![Code coverage](https://codecov.io/gh/lekoala/silverstripe-admini/branch/master/graph/badge.svg)](https://codecov.io/gh/lekoala/silverstripe-admini)

## Introduction

This is an alternative admin module for SilverStripe built on top of [admini](https://github.com/lekoala/admini). It is mostly standalone and only needs the base framework to run.
GridFields are replaced by the powerful [Tabulator implementation](https://github.com/lekoala/silverstripe-tabulator).

Forms are powered by custom elements and loaded on request using [Form Elements](https://github.com/lekoala/silverstripe-form-elements).

The goal is to provide a very fast, yet flexible ui. For example, a cached page load can use as little as 7 database queries.

## Frontend validation

This module support validation on the frontend for your forms. This means you can expect regular HTML5 validation rules to work properly, even accross tabs.
Other validation features from [bs-companion](https://github.com/lekoala/bs-companion) are also supported.

## TODO

- Dashboard
- Fluent

## Compatibility

Tested with SilverStripe 5^

Working mostly out of the box with my other modules:
- cms-actions
- pure-modal

## Maintainer

LeKoala - thomas@lekoala.be
