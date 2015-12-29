# LVM Visualiser Webapp
This is a PHP-based Web-app to display the layout/structure of a [Logical Volume Manager][lvm] set-up in a graphical form so that one can easily understand how the logical volumes are laid out across the physical volumes.

## Support
Because I've created this for my own use, I have no plans for this app to support every single feature of LVM, nor for it to necessarily support Linux distributions besides [Ubuntu][] (whichever versions of which I'm using myself - currently `15.10` and `14.04`) or any other systems on which LVM may run. The same applies for physical volumes besides the standard `/dev/sd?` devices.

That said, I'll be happy to accept pull requests that add support for additional platforms/features, provided those pull requests don't interfere with existing behaviour.

My development & target environment is as follows:

- Ubuntu Server 15.10 ("wily")
- [Apache/2.4.12][apache]
- [PHP 5.6.11 x64][php]
- [Chrome 48.0.2564.48 x64][chrome]
- [LVM 2.02.122][lvm], utilising:
    - A single volume group (VG)
    - 11 physical volumes (PVs); both SATA and USB; HDD and SSD
    - Linear logical volumes (LVs)
    - LVs spanning multiple PVs
    - Mirrored LVs (the legacy `mirror` type; **not** using any `raid1` mirrors)
    - `disk`-based mirror logs (I may later ensure support for `mirrored` and `core` logs)
    - Snapshots

So, in theory, this webapp should run on any system vaguely similar to that, but I make no promises.

## Installation
*(As-yet unwritten! It'll probably involve something like cloning/downloading the repository into a subdirectory of your machine's web root and running `composer install`, but who knows!)*

## Contributions
As noted above, I welcome pull requests to add support for LVM features or platforms that aren't already supported, as well as for fixing bugs or otherwise just improving things. Just stick to these guidelines, if you can:

- Adhere to the [PHP-FIG][] [PSRs][]!
- Follow existing code styles ([PSR-1][], [PSR-2][], *[PSR-5][], [PSR-12][] (draft)*).
- Pull requests that change existing behaviour should ideally keep that behaviour behind some kind of configuration option.
- Try to avoid adding additional dependencies (besides [`composer`][composer] packages). If possible, keep extra dependencies behind opt-in configuration options.
- And remember that, at the end of the day, this is my project, so I have the ultimate control over things. I'm not going to be a dick about this, but, likewise, if you start being unpleasant in any way, you will be shown the door.

## Licence
Undecided! Probably something [LGPL][]-ish, I guess?

[lvm]:        https://en.wikipedia.org/wiki/Logical_Volume_Manager_(Linux)  "Logical Volume Manager"
[ubuntu]:     http://www.ubuntu.com/  "Ubuntu"
[apache]:     https://httpd.apache.org/  "Apache HTTP Server"
[php]:        http://www.php.net/  "PHP: Hypertext Preprocessor"
[chrome]:     https://www.google.com/chrome/  "Google Chrome"
[php-fig]:    http://www.php-fig.org/  "PHP Framework Interop Group"
[psrs]:       http://www.php-fig.org/psr/ "PHP Standards Recommendations"
[psr-1]:      http://www.php-fig.org/psr/psr-1/  "PSR-1: Basic Coding Standard"
[psr-2]:      http://www.php-fig.org/psr/psr-2/  "PSR-2: Coding Style Guide"
[psr-5]:      https://github.com/phpDocumentor/fig-standards/blob/master/proposed/phpdoc.md  "PSR-5: PHPDoc Standard"
[psr-12]:     https://github.com/php-fig/fig-standards/blob/master/proposed/extended-coding-style-guide.md  "PSR-12: Extended Coding Style Guide"
[composer]:   https://getcomposer.org/  "Composer"
[lgpl]:       https://en.wikipedia.org/wiki/GNU_Lesser_General_Public_License  "GNU Lesser General Public License"
