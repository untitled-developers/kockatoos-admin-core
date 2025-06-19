# Kockatoos Admin Core

> **Note:** This package is a work in progress.

## Index

- [Installation](#installation)
- [Development Setup](#development-setup)
- [Useful Commands](#useful-commands)
- [Stub Generation](#stub-generation)

## Installation

To install this package in your Laravel project:

1. Add the repository to your `composer.json`:

```json
{
  "repositories": [
    {
      "type": "vcs",
      "url": "https://github.com/untitled-developers/kockatoos-admin-core"
    }
  ]
}
```

2. Require the package (replace the version with the latest tag):

```json
{
  "require": {
    "untitled-developers/kockatoos-admin-core": "^1.0"
  }
}
```

Then run:

```bash
composer update
```

## Development Setup

Since this is a standalone package, you can't run it directly. To develop it locally:

1. Create a Laravel project if you don’t already have one.
2. In that Laravel project’s `composer.json`, add a local path repository:

```json
{
  "repositories": [
    {
      "type": "path",
      "url": "../kockatoos-admin-core"
    }
  ]
}
```

3. Then require the local package in development mode:

```json
{
  "require": {
    "untitled-developers/kockatoos-admin-core": "@dev"
  }
}
```

4. Run:

```bash
composer update
```

This allows you to work on the package locally without needing to commit or push changes.

## Useful Commands

### `composer parse-stubs`

This command will scan the package's `stubs/` directory and generate PHP classes for each `.stub` file.  
The generated classes contain static string properties for each template variable found in the stubs.

Example:

```php
public static string $VARIABLE_NAME = 'VARIABLE_NAME';
```

You can run this from the root of your package:

```bash
composer parse-stubs
```
