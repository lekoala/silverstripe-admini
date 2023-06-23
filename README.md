# SilverStripe admini module

[![Build Status](https://travis-ci.com/lekoala/silverstripe-admini.svg?branch=master)](https://travis-ci.com/lekoala/silverstripe-admini/)
[![scrutinizer](https://scrutinizer-ci.com/g/lekoala/silverstripe-admini/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/lekoala/silverstripe-admini/)
[![Code coverage](https://codecov.io/gh/lekoala/silverstripe-admini/branch/master/graph/badge.svg)](https://codecov.io/gh/lekoala/silverstripe-admini)

## Introduction

This is an alternative admin module for SilverStripe built on top of [admini](https://github.com/lekoala/admini). It is mostly standalone and only needs the base framework to run.
GridFields are replaced by the powerful [Tabulator implementation](https://github.com/lekoala/silverstripe-tabulator).

Forms are powered by custom elements and loaded on request using [Form Elements](https://github.com/lekoala/silverstripe-form-elements).

The goal is to provide a very fast, yet flexible ui. For example, a cached page load can use as little as 7 database queries.

## TODO

- Dashboard
- Fluent

## Compatibility

Tested with SilverStripe 4.13^

Working mostly out of the box with my other modules:
- cms-actions
- pure-modal

## Maintainer

LeKoala - thomas@lekoala.be
