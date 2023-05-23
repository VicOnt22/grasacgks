# INTRODUCTION

Provides a simple integration with Zooming. Image zoom that makes sense.

## REQUIREMENTS
* Zooming library:
  + Download Zooming archive from
    [Zooming](https://github.com/kingdido999/zooming)
  + Extract it as is, rename **zooming-master** to **zooming"**, so the
    asset is available at:  
    **/libraries/zooming/build/zooming.min.js**

    Be sure to verify this is installed at **/admin/reports/status**

* [Blazy](https://drupal.org/project/blazy) (>= Aplha2).    


## INSTALLATION
Install the module as usual, more info can be found on:

[Installing Drupal 8 Modules](https://drupal.org/node/1897420)


## CONFIGURATION
Enable this module and its dependency, core image and Blazy modules.

### FIELD FORMATTERS
* **/admin/config/people/accounts/fields**, or **/admin/structure/types**,
  or any fieldable entity, click **Manage display**.
* Under **Format**, choose blazy-related formatters:
  **Blazy**, **Slick carousel**, etc. for image field.
* Click the **Configure** icon.
* Under **Media switcher**, choose **Image to Zooming**. Adjust the rest.

### BLAZY FILTER
* **/admin/config/content/formats/full_html**, etc.
* Enable **Blazy Filter**.
* Under **Media switcher**, choose **Image to Zooming**.


## FEATURES
* Has no formatter, instead integrated into **Media switcher** option as seen at
  Blazy/ Slick formatters, including Blazy Views fields for File Entity and
  Media, and also Blazy Filter for inline images.
* Inline video if Media module is installed.


## KNOWN ISSUES / LIMITATIONS
* Working with Blazy Grid, Blazy Filter, but not Slick Carousel, yet.
* The library only works with IMG, not CSS background.
* For the best result, the hi-res image should have the exact same aspect ratio
  as your regular image. Reasonable as it zooms in and out images must stick to
  a single aspect ratio, otherwise squeezing.
* The zoomed image requires its parent containers to not have CSS rule
  `overflow: hidden` as otherwise it is cropped/ hidden when being zoomed in.

  **Solution**:
  Use the provided `is-zooming` class (on body element) to temporarily override.
  See `css/zooming.css` for samples with Blazy grids.


## SIMILAR MODULES
[Intense](https://drupal.org/project/intense)


## MAINTAINERS
* [Gaus Surahman](https://drupal.org/user/159062)
* [Contributors](https://www.drupal.org/node/3031940/committers)
* The CHANGELOG.txt for more helpful souls with suggestions, and bug reports.


## READ MORE
See the project page on drupal.org:

[Zooming module](http://drupal.org/project/zooming)

See the Zooming docs at:

* [Zooming at Github](https://github.com/kingdido999/zooming)
* [Zooming website](https://desmonding.me/zooming)
