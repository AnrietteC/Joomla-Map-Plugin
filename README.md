# vixelmap
A plugin that facilitates easy inclusion of Google Maps into any content piece.

## Requirements
* Google API key enabled for the specific Maps API you wish to include

## Compatibility
* Joomla 3.6.x
* Google Maps API v3

## Usage

    {vixelmap address="Some address, Some city" type="js|embed" lat="nn.nn" lng="nn.nn" zoom="n" height="npx|n%"}
    
Where `n` equals to any number

### Required attributes

**bold** - set options  
*italic* - user-defined values

* address
    * *plain-text string*
* type (choose one)
    * **js**
    * **embed**
* lat
    * *floating-point value*
* lng
    * *floating-point value*

### Optional attributes
* zoom
    * *integer value*
* width
    * *percentage*
    * *integer value in px*
* height
    * *percentage*
    * *integer value in px*
