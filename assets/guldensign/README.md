
## guldensign

This repository contains the source and distribution files for the guldensign website supplement.

Basically, by including a single css file into any website, the character `Ġ` is converted to a Guldencoin valuta sign.

[More info in the docs.](https://docs.guldencoin.com/guldensign)

The guldensign font is created with [fontforge](http://fontforge.org/) and [FontSquirrel's webfont tool](http://www.fontsquirrel.com/tools/webfont-generator).

Export the ttf from fontforge. Then use FontSquirrel's webfont tool to create woff, eot, svg and a new ttf.
Mostly use default settings:

 - Fix Vertical Metrics
 - Fix GASP Table
 - Do not add missing glyphs
 - Set custom subset of 1 glyph (unicode hex: 0120)
 - Use an Em Square Value of 4096
 - Enable style link to get nice css output

### License
[CC-BY-SA](http://creativecommons.org/licenses/by-sa/4.0/)