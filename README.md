# BIIGLE User Disks Module

[![Test status](https://github.com/biigle/user-disks/workflows/Tests/badge.svg)](https://github.com/biigle/user-disks/actions?query=workflow%3ATests)

This is a BIIGLE module that offers private storage disks for users.

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
