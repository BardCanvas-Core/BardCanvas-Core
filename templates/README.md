
# About HNG2 templates

HNG2 uses 5 different layouts for all page renderings:

* `home.php` for the main website index/multi-module output.
* `main.php` for module-based output.
* `admin.php` also for module-based output, but with some limitations.
* `embeddable.php` used for embedded documents, mostly pulled using AJAX requests.
* `popup.php` similar to the embeddable layout but suitable for popups or iframes.

## Injection points

In the module's `module_info.xml` file, the `<template_includes>` node should include all needed injection point tags:

* `pre_rendering` before outputting the first line of the document (the `<html>` tag).  
  Use cases: pre-processing variables.

* `html_head` before closing the `<head>` tag.  
  Use cases: additional JS and CSS files.

* `pre_header` inside the body wrapper, before opening the header div.  
  Use cases: additional content wrappers initialization.

* `header_top` at the top of the header div.    
  Use cases: rendering tool bars before the main menu.

* `header_menu` before rendering main menu items.    
  Use cases: main menu item definitions.

* `header_bottom` after rendering the main menu.  
  Use cases: rendering tool bars after the main menu.

* `content_top` before the main contents loop.    
  Use cases: rendering top priority elements.

* `home_content` just on the home page.   
  Use cases: render normal priority home contents.

    > **Important:** this place is where every layout other than the home is
    > called to render contents. For instance, 
    > `$template->page_contents_include` is called here.

* `content_bottom` after the main contents loop.  
  Use cases: rendering low priority elements.

* `pre_footer` before opening the footer div.  
  Use cases: rendering hidden elements that need to be out of the main contents.

* `footer_top` inside the footer div, before the footer contents.    
  Use cases: generic usage inside the footer.

* `footer_bottom` inside the footer div, after the footer contents.  
  Use cases: generic usage inside the footer.

* `post_footer` after closing the footer div.  
  Use cases: similar to `pre_footer`.

* `pre_eof` before closing the `<body>` tag.  
  Use cases: similar to `pre_footer` y `post_footer`. 

* `post_rendering` after closing the `<html>` tag.  
  Use cases: non-output related operations.

## Injections per layout reference

    tag                   | home | main | admin | embeddable | popup
    ----------------------|------|------|-------|------------|-------
    pre_rendering         |   √  |   √  |   √   |            |   √
    html_head             |   √  |   √  |   √   |            |
    pre_header            |   √  |   √  |   √   |            |
    header_top            |   √  |   √  |   √   |            |
    header_menu           |   √  |   √  |   √   |            |
    header_bottom         |   √  |   √  |   √   |            |
    content_top           |   √  |   √  |       |            |
    home_content          |   √  |      |       |            |
    content_bottom        |   √  |   √  |       |            |
    pre_footer            |   √  |   √  |       |            |
    footer_top            |   √  |   √  |       |            |
    footer_bottom         |   √  |   √  |       |            |
    post_footer           |   √  |   √  |   √   |            |
    pre_eof               |   √  |   √  |       |            |
    post_rendering        |   √  |   √  |       |            |