# BIIGLE User Disks Module

[![Test status](https://github.com/biigle/user-disks/workflows/Tests/badge.svg)](https://github.com/biigle/user-disks/actions?query=workflow%3ATests)

This is a BIIGLE module that offers private storage disks for users.

## Configuration

This module supports `s3`, `webdav`, `elements`, `dcache` and `aruna` storage disks but by default only S3 is enabled. Configure the enabled storage disk types as a comma-separated list with the `USER_DISKS_TYPES` environment variable (e.g. `s3,webdav`).

### Required Packages by Disk Type

Different storage disk types require additional packages to be installed:

- **S3**: No additional packages required (included by default)
- **Aruna**: No additional packages required (included by default but disabled)
- **Elements**: Requires `biigle/laravel-elements-storage`
  ```bash
  composer require biigle/laravel-elements-storage
  ```
- **WebDAV**: Requires `biigle/laravel-webdav`
  ```bash
  composer require biigle/laravel-webdav
  ```
- **dCache**: Requires both `biigle/laravel-webdav` and `biigle/laravel-socialite-haai`
  ```bash
  composer require biigle/laravel-webdav biigle/laravel-socialite-haai
  ```

Install only the packages for the disk types you plan to enable.

## Installation

1. Run `composer require biigle/user-disks`.
2. Add `Biigle\Modules\UserDisks\UserDisksServiceProvider::class` to the `providers` array in `config/app.php`.
3. Run `php artisan vendor:publish --tag=public` to refresh the public assets of the modules. Do this for every update of this module.
4. Run `php artisan migrate` to create the new database tables.

## Developing

Take a look at the [development guide](https://github.com/biigle/core/blob/master/DEVELOPING.md) of the core repository to get started with the development setup.

Want to develop a new module? Head over to the [biigle/module](https://github.com/biigle/module) template repository.

## Contributions and bug reports

Contributions to BIIGLE are always welcome. Check out the [contribution guide](https://github.com/biigle/core/blob/master/CONTRIBUTING.md) to get started.
