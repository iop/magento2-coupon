# Magento Module Iop_Coupon

## Tested on Version

* Magento 2.3.5

## Main Functionalities
* Apply coupon from project url:   https://project.url?cpn=COUPON_CODE

##### NOTE: Screencast (1min): https://www.screencast.com/t/OZaMVkJs7IZ   If screencast issue then try use other browser.

## Installation 

#### With Composer
Use the following commands to install this module into Magento 2:

    composer require iop/magento2-coupon
    bin/magento module:enable Iop_Coupon
    bin/magento setup:upgrade && c:f
       
#### Manual (without composer)
These are the steps:
* Upload the files in the folder `app/code/Iop/Coupon` of your site
* Run `php -f bin/magento module:enable Iop_Coupon`
* Run `php -f bin/magento setup:upgrade`
* Flush the Magento cache
* Done

